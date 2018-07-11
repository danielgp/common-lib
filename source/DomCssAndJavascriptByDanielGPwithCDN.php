<?php

/**
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Daniel Popiniuc
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */

namespace danielgp\common_lib;

/**
 * DOM component functions
 *
 * @author Daniel Popiniuc
 */
trait DomCssAndJavascriptByDanielGPwithCDN
{

    private $sCloundFlareUrl = '//cdnjs.cloudflare.com/ajax/libs/';

    private function getCmpltVers($sFileName, $rootFileName)
    {
        return str_replace([$rootFileName, '.min.js'], '', pathinfo($sFileName)['basename']);
    }

    private function knownCloudFlareJavascript($jsFileName)
    {
        $justFile = pathinfo($jsFileName)['basename'];
        switch ($justFile) {
            case 'jquery.placeholder.min.js':
                return [
                    'justFile' => $justFile,
                    'version'  => 'jquery-placeholder/2.0.8/',
                    'eVerify'  => 'jQuery.placeholder',
                ];
            // intentionally left blank
            case 'jquery.easing.1.3.min.js':
                return [
                    'justFile' => str_replace('.1.3', '', $justFile),
                    'version'  => 'jquery-easing/1.3/',
                    'eVerify'  => 'jQuery.easing["jswing"]',
                ];
            // intentionally left blank
        }
    }

    private function sanitizeString($sFileName)
    {
        return filter_var($sFileName, FILTER_SANITIZE_STRING);
    }

    /**
     * Manages all known CSS that can be handled through CDNs
     *
     * @param string $cssFileName
     * @return array|string
     */
    protected function setCssFileCDN($cssFileName)
    {
        $patternFound = null;
        if (strpos(pathinfo($cssFileName)['basename'], 'font-awesome-') !== false) {
            $patternFound = $this->setCssFileCDNforFontAwesome($cssFileName);
        }
        if (is_null($patternFound)) {
            $patternFound = [false, $this->sanitizeString($cssFileName)];
        }
        return $patternFound;
    }

    /**
     * Returns css link to a given file
     * Returns an array with CDN call of a known Font-websome css
     *
     * @param string $cssFileName
     * @return array
     */
    private function setCssFileCDNforFontAwesome($cssFileName)
    {
        return [
            true,
            $this->sCloundFlareUrl . 'font-awesome/' . $this->getCmpltVers($cssFileName, 'font-awesome-')
            . '/css/font-awesome.min.css',
        ];
    }

    /**
     * Manages all known Javascript that can be handled through CDNs
     * (if within local network makes no sense to use CDNs)
     *
     * @param string $jsFileName
     * @return array|string
     */
    protected function setJavascriptFileCDN($jsFileName)
    {
        $onlyFileName = pathinfo($jsFileName)['basename'];
        $patternFound = null;
        if (in_array($onlyFileName, ['jquery.placeholder.min.js', 'jquery.easing.1.3.min.js'])) {
            $patternFound = $this->setJavascriptFileCDNjQueryLibs($jsFileName);
        } elseif (strpos($onlyFileName, '-') !== false) {
            $patternFound = $this->setJavascriptFileCDNbyPattern($jsFileName);
        }
        if (is_null($patternFound)) {
            $patternFound = [false, $this->sanitizeString($jsFileName), ''];
        }
        return $patternFound;
    }

    private function setJavascriptFileCDNbyPattern($jsFileName)
    {
        $sFileParts = explode('-', $jsFileName);
        $knownFNs   = [
            'jquery'     => 'setJavascriptFileCDNjQuery',
            'highcharts' => 'setJavascriptFileCDNforHighCharts',
            'exporting'  => 'setJavascriptFileCDNforHighChartsExporting',
        ];
        $rootFN     = pathinfo($sFileParts[0])['basename'];
        if (array_key_exists($rootFN, $knownFNs)) {
            return call_user_func([$this, $knownFNs[$rootFN]], pathinfo($jsFileName)['basename']);
        }
        return null;
    }

    /**
     * Returns an array with CDN call of a known Javascript library
     * and fall-back line that points to local cache of it
     * specific for HighCharts
     *
     * @param string $jsFileName
     * @return array
     */
    private function setJavascriptFileCDNforHighCharts($jsFileName)
    {
        return $this->setJavascriptFileCDNforHighChartsMain($jsFileName, 'highcharts');
    }

    /**
     * Returns an array with CDN call of a known Javascript library
     * and fall-back line that points to local cache of it
     * specific for HighCharts Exporting feature
     *
     * @param string $jsFileName
     * @return array
     */
    private function setJavascriptFileCDNforHighChartsExporting($jsFileName)
    {
        return $this->setJavascriptFileCDNforHighChartsMain($jsFileName, 'exporting');
    }

    /**
     * Returns an array with CDN call of a known Javascript library
     * and fall-back line that points to local cache of it
     * specific for HighCharts
     *
     * @param string $jsFileName
     * @param string $libName
     * @return array
     */
    private function setJavascriptFileCDNforHighChartsMain($jsFileName, $libName)
    {
        $jsFN            = $this->sanitizeString($jsFileName);
        $jsVersionlessFN = str_replace([$libName . '-', '.js'], '', pathinfo($jsFileName)['basename'])
            . ($libName === 'exporting' ? '/modules' : '');
        if (strpos($jsFileName, $libName) !== false) {
            return [
                true,
                $this->sCloundFlareUrl . 'highcharts/' . $jsVersionlessFN . '/' . $libName . '.js',
                '<script>!window.Highcharts && document.write(\'<script src="' . $jsFN . '">\x3C/script>\')</script>',
            ];
        }
        return null;
    }

    /**
     * Returns an array with CDN call of a known Javascript library
     * and fall-back line that points to local cache of it
     * specific for jQuery
     *
     * @param string $jsFileName
     * @return array
     */
    private function setJavascriptFileCDNjQuery($jsFileName)
    {
        $jQueryPosition     = strpos($jsFileName, 'jquery-');
        $jQueryMajorVersion = substr($jsFileName, 7, 1);
        if (($jQueryPosition !== false) && is_numeric($jQueryMajorVersion) && (substr($jsFileName, -7) == '.min.js')) {
            return [
                true,
                $this->sCloundFlareUrl . 'jquery/' . $this->getCmpltVers($jsFileName, 'jquery-') . '/jquery.min.js',
                '<script>window.jQuery || document.write(\'<script src="' . $this->sanitizeString($jsFileName)
                . '">\x3C/script>\')</script>',
            ];
        }
        return null;
    }

    /**
     * Returns an array with CDN call of a known Javascript library
     * and fall-back line that points to local cache of it
     * specific for jQuery Libraries
     *
     * @param string $jsFileName
     * @return array
     */
    private function setJavascriptFileCDNjQueryLibs($jsFileName)
    {
        $sFN    = $this->sanitizeString($jsFileName);
        $eArray = $this->knownCloudFlareJavascript($sFN);
        if (!is_null($eArray['version'])) {
            return [
                true,
                $this->sCloundFlareUrl . $eArray['version'] . $eArray['justFile'],
                '<script>' . $eArray['eVerify'] . ' || document.write(\'<script src="' . $sFN
                . '">\x3C/script>\')</script>',
            ];
        }
        return null;
    }
}
