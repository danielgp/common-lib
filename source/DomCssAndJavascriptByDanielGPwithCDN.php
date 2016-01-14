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

    /**
     * Manages all known CSS that can be handled through CDNs
     *
     * @param string $cssFileName
     * @return array
     */
    protected function setCssFileCDN($cssFileName)
    {
        $patternFound = null;
        if (strpos(pathinfo($cssFileName)['basename'], 'font-awesome-') !== false) {
            $patternFound = $this->setCssFileCDNforFontAwesome($cssFileName);
        }
        if (is_null($patternFound)) {
            $patternFound = [false, filter_var($cssFileName, FILTER_SANITIZE_STRING)];
        }
        return $patternFound;
    }

    /**
     * Returns css link to a given file
     * Returns an array with CDN call of a known Font-websome css
     *
     * @param string $cssFileName
     * @return string
     */
    private function setCssFileCDNforFontAwesome($cssFileName)
    {
        $patternFound = [
            true,
            implode('', [
                '//cdnjs.cloudflare.com/ajax/libs/font-awesome/',
                str_replace(['font-awesome-', '.min.css'], '', pathinfo($cssFileName)['basename']),
                '/css/font-awesome.min.css',
            ])
        ];
        return $patternFound;
    }

    /**
     * Manages all known Javascript that can be handled through CDNs
     *
     * @param string $jsFileName
     * @return array
     */
    protected function setJavascriptFileCDN($jsFileName)
    {
        $onlyFileName = pathinfo($jsFileName)['basename'];
        // if within local network makes no sense to use CDNs
        $patternFound = null;
        if (strpos($onlyFileName, 'jquery-') !== false) {
            $patternFound = $this->setJavascriptFileCDNjQuery($jsFileName);
        } elseif (strpos($onlyFileName, 'jquery.placeholder.min.js') !== false) {
            $patternFound = $this->setJavascriptFileCDNjQueryLibs($jsFileName);
        } elseif (strpos($onlyFileName, 'jquery.easing.1.3.min.js') !== false) {
            $patternFound = $this->setJavascriptFileCDNjQueryLibs($jsFileName);
        } elseif (strpos($onlyFileName, 'highcharts-') !== false) {
            $patternFound = $this->setJavascriptFileCDNforHighCharts($jsFileName);
        } elseif (strpos($onlyFileName, 'exporting-') !== false) {
            $patternFound = $this->setJavascriptFileCDNforHighChartsExporting($jsFileName);
        }
        if (is_null($patternFound)) {
            $patternFound = [false, filter_var($jsFileName, FILTER_SANITIZE_STRING), ''];
        }
        return $patternFound;
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
        $patternFound = null;
        if (strpos($jsFileName, 'highcharts') !== false) {
            $patternFound = [
                true,
                implode('', [
                    '//cdnjs.cloudflare.com/ajax/libs/highcharts/',
                    str_replace(['highcharts-', '.js'], '', pathinfo($jsFileName)['basename']),
                    '/highcharts.js',
                ]),
                implode('', [
                    '<script>!window.Highcharts && document.write(\'<script src="',
                    filter_var($jsFileName, FILTER_SANITIZE_STRING),
                    '">\x3C/script>\')</script>'
                ])
            ];
        }
        return $patternFound;
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
        $patternFound   = null;
        $jQueryPosition = strpos($jsFileName, 'exporting');
        if ($jQueryPosition !== false) {
            $patternFound = [
                true,
                implode('', [
                    '//cdnjs.cloudflare.com/ajax/libs/highcharts/',
                    str_replace(['exporting-', '.js'], '', pathinfo($jsFileName)['basename']),
                    '/modules/exporting.js',
                ]),
                implode('', [
                    '<script>!window.Highcharts.post && document.write(\'<script src="',
                    filter_var($jsFileName, FILTER_SANITIZE_STRING),
                    '">\x3C/script>\')</script>'
                ])
            ];
        }
        return $patternFound;
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
        $patternFound       = null;
        $jQueryPosition     = strpos($jsFileName, 'jquery-');
        $jQueryMajorVersion = substr($jsFileName, 7, 1);
        if (($jQueryPosition !== false) && is_numeric($jQueryMajorVersion) && (substr($jsFileName, -7) == '.min.js')) {
            $patternFound = [
                true,
                implode('', [
                    '//cdnjs.cloudflare.com/ajax/libs/jquery/',
                    str_replace(['jquery-', '.min.js'], '', pathinfo($jsFileName)['basename']),
                    '/jquery.min.js',
                ]),
                implode('', [
                    '<script>window.jQuery || document.write(\'<script src="',
                    filter_var($jsFileName, FILTER_SANITIZE_STRING),
                    '">\x3C/script>\')</script>'
                ])
            ];
        }
        return $patternFound;
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
        $patternFound = null;
        $eArray       = $this->knownCloudFlareJavascript(filter_var($jsFileName, FILTER_SANITIZE_STRING));
        if (!is_null($eArray['version'])) {
            $patternFound = [
                true,
                implode('', [
                    '//cdnjs.cloudflare.com/ajax/libs/',
                    $eArray['version'],
                    $eArray['justFile'],
                ]),
                implode('', [
                    '<script>' . $eArray['eVerify'] . ' || document.write(\'<script src="',
                    filter_var($jsFileName, FILTER_SANITIZE_STRING),
                    '">\x3C/script>\')</script>'
                ])
            ];
        }
        return $patternFound;
    }
}