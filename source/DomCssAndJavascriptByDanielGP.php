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
trait DomCssAndJavascriptByDanielGP
{

    use \danielgp\browser_agent_info\BrowserAgentInfosByDanielGP,
        DomBasicComponentsByDanielGP,
        DomCssAndJavascriptByDanielGPwithCDN;

    /**
     * Returns css codes
     *
     * @param string $cssContent
     * @param array $optionalFlags
     * @return string
     */
    protected function setCssContent($cssContent, $optionalFlags = null)
    {
        $attr = [];
        if (is_null($optionalFlags)) {
            $attr['media'] = 'all';
        } else {
            $knownAttributes = ['media'];
            foreach ($knownAttributes as $value) {
                if (array_key_exists($value, $optionalFlags)) {
                    $attr[$value] = $optionalFlags[$value];
                }
            }
        }
        return '<style type="text/css" media="' . $attr['media'] . '">'
                . $cssContent . '</style>';
    }

    /**
     * Returns css link to a given file
     *
     * @param string $cssFileName
     * @return string
     */
    protected function setCssFile($cssFileName, $hostsWithoutCDNrq = null)
    {
        if (is_null($hostsWithoutCDNrq)) {
            $hostsWithoutCDNrq = [];
        }
        if (in_array($this->getClientRealIpAddress(), $hostsWithoutCDNrq)) {
            return '<link rel="stylesheet" type="text/css" href="'
                    . filter_var($cssFileName, FILTER_SANITIZE_STRING) . '" />';
        }
        $patternFound = $this->setCssFileCDN($cssFileName);
        return '<link rel="stylesheet" type="text/css" href="'
                . filter_var($patternFound[1], FILTER_SANITIZE_STRING) . '" />';
    }

    /**
     * Returns javascript function to support Add or Edit through Ajax
     *
     * @return string
     */
    protected function setJavascriptAddEditByAjax($tabName = 'tabStandard')
    {
        return $this->setJavascriptContent(implode('', [
                    'function loadAE(action) {',
                    'document.getElementById("' . $tabName . '").tabber.tabShow(1);',
                    '$("#DynamicAddEditSpacer").load(action',
                    '+"&specialHook[]=noHeader"',
                    '+"&specialHook[]=noMenu"',
                    '+"&specialHook[]=noContainer"',
                    '+"&specialHook[]=noFooter"',
                    ');',
                    '}',
        ]));
    }

    /**
     * Returns javascript codes
     *
     * @param string $javascriptContent
     * @return string
     */
    protected function setJavascriptContent($javascriptContent)
    {
        return '<script type="text/javascript">' . $javascriptContent . '</script>';
    }

    /**
     * Builds up a confirmation dialog and return delection if Yes
     *
     * @return string
     */
    protected function setJavascriptDeleteWithConfirmation()
    {
        return $this->setJavascriptContent('function setQuest(a, b) { '
                        . 'c = a.indexOf("_"); switch(a.slice(0, c)) { '
                        . 'case \'delete\': '
                        . 'if (confirm(\'' . $this->lclMsgCmn('i18n_ActionDelete_ConfirmationQuestion') . '\')) { '
                        . 'window.location = document.location.protocol + "//" + '
                        . 'document.location.host + document.location.pathname + '
                        . '"?view=" + a + "&" + b; } break; } }');
    }

    /**
     * Returns javascript link to a given file
     *
     * @param string $jsFileName
     * @return string
     */
    protected function setJavascriptFile($jsFileName, $hostsWithoutCDNrq = null)
    {
        if (is_null($hostsWithoutCDNrq)) {
            $hostsWithoutCDNrq = [];
        }
        if (in_array($this->getClientRealIpAddress(), $hostsWithoutCDNrq)) {
            return '<script type="text/javascript" src="' . $jsFileName . '"></script>';
        }
        $patternFound = $this->setJavascriptFileCDN($jsFileName);
        return '<script type="text/javascript" src="' . $patternFound[1] . '"></script>' . $patternFound[2];
    }

    /**
     * Returns javascript codes from given file
     *
     * @param string $jsFileName
     * @return string
     */
    protected function setJavascriptFileContent($jsFileName)
    {
        return '<script type="text/javascript">' . file_get_contents($jsFileName, true) . '</script>';
    }

    protected function updateDivTitleName($rememberGroupVal, $groupCounter)
    {
        $jsContent = '$(document).ready(function() { $("#tab_'
                . $this->cleanStringForId($rememberGroupVal) . '").attr("title", "'
                . $rememberGroupVal . ' (' . $groupCounter . ')"); });';
        return $this->setJavascriptContent($jsContent);
    }
}
