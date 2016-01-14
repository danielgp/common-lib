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
trait DomBasicComponentsByDanielGP
{

    use CommonLibLocale;

    private function buildAttributesForTag($features)
    {
        if (!is_array($features)) {
            return '';
        }
        $attributes = '';
        foreach ($features as $key => $value) {
            $val = $this->buildAttributesForTagValueArray($value);
            $attributes .= ' ' . $key . '="' . $val . '"';
        }
        return $attributes;
    }

    private function buildAttributesForTagValueArray($value)
    {
        $val = $value;
        if (is_array($value)) {
            $valA = [];
            foreach ($value as $key2 => $value2) {
                $valA[] = $key2 . ':' . $value2;
            }
            $val = implode(';', $valA) . ';';
        }
        return $val;
    }

    /**
     * Capatalize first letter of each word
     * AND filters only letters and numbers
     *
     * @param string $givenString
     * @return string
     */
    protected function cleanStringForId($givenString)
    {
        return preg_replace("/[^a-zA-Z0-9]/", '', ucwords($givenString));
    }

    /**
     * Cleans a string for certain internal rules
     *
     * @param type $urlString
     * @return type
     */
    protected function setCleanUrl($urlString)
    {
        $arrayToReplace = [
            '&#038;'    => '&amp;',
            '&'         => '&amp;',
            '&amp;amp;' => '&amp;',
            ' '         => '%20',
        ];
        $kys            = array_keys($arrayToReplace);
        $vls            = array_values($arrayToReplace);
        return str_replace($kys, $vls, filter_var($urlString, FILTER_SANITIZE_URL));
    }

    /**
     * Returns a div tag that clear any float
     *
     * @param integer $height
     */
    protected function setClearBoth1px($height = 1)
    {
        $divStyle = implode('', [
            'height:' . $height . 'px;',
            'line-height:' . $height . 'px;',
            'float:none;',
            'clear:both;',
            'margin:0px;'
        ]);
        return $this->setStringIntoTag('&nbsp;', 'div', ['style' => $divStyle]);
    }

    /**
     * Builds a structured modern message
     *
     * @param string $sType
     * @param string $sTitle
     * @param string $sMsg
     * @param boolean $skipBr
     */
    protected function setFeedbackModern($sType, $sTitle, $sMsg, $skipBr = false)
    {
        if ($sTitle == 'light') {
            return $sMsg;
        }
        $legend = $this->setStringIntoTag($sTitle, 'legend', ['style' => $this->setFeedbackStyleTitle($sType)]);
        return implode('', [
            ($skipBr ? '' : '<br/>'),
            $this->setStringIntoTag($legend . $sMsg, 'fieldset', ['style' => $this->setFeedbackStyleMessage($sType)]),
        ]);
    }

    private function setFeedbackStyleTitle($sType)
    {
        $formatTitle = 'margin-top:-5px;margin-right:20px;padding:5px;';
        $styleByTypeForTitle = [
            'alert' => 'border:medium solid orange;background-color:orange;color:navy;',
            'check' => 'border:medium solid green;background-color:green;color:white;',
            'error' => 'border:medium solid red;background-color:red;color:white;',
            'info'  => 'border:medium solid black;background-color:black;color:white;font-weight:bold;',
        ];
        return $formatTitle . $styleByTypeForTitle[$sType];
    }

    private function setFeedbackStyleMessage($sType)
    {
        $formatMessage = 'display:inline;padding-right:5px;padding-bottom:5px;';
        $styleByTypeMsg   = [
            'alert' => 'background-color:navy;color:orange;border:medium solid orange;',
            'check' => 'background-color:yellow;color:green;border:medium solid green;',
            'error' => 'background-color:yellow;color:red;border:medium solid red;',
            'info'  => 'background-color: white; color: black;border:medium solid black;',
        ];
        return $formatMessage . $styleByTypeMsg[$sType];
    }

    /**
     * Sets the gzip footer for HTML
     */
    protected function setFooterGZiped()
    {
        if (extension_loaded('zlib')) {
            return $this->setGZipedUnsafe('Footer');
        }
        return '';
    }

    private function setGZipedUnsafe($outputType)
    {
        $this->initializeSprGlbAndSession();
        if (!is_null($this->tCmnRequest->server->get('HTTP_ACCEPT_ENCODING'))) {
            return '';
        } elseif (strstr($this->tCmnRequest->server->get('HTTP_ACCEPT_ENCODING'), 'gzip')) {
            switch ($outputType) {
                case 'Footer':
                    $gzipCntntOriginal = ob_get_contents();
                    ob_end_clean();
                    $gzipCntnt         = gzcompress($gzipCntntOriginal, 9);
                    echo "\x1f\x8b\x08\x00\x00\x00\x00\x00" . substr($gzipCntnt, 0, strlen($gzipCntnt) - 4)
                    . pack('V', crc32($gzipCntntOriginal)) . pack('V', strlen($gzipCntntOriginal));
                    break;
                case 'Header':
                    ob_start();
                    ob_implicit_flush(0);
                    header('Content-Encoding: gzip');
                    break;
            }
        }
    }

    /**
     * Sets the gzip header for HTML
     */
    protected function setHeaderGZiped()
    {
        if (extension_loaded('zlib')) {
            return $this->setGZipedUnsafe('Header');
        }
        return '';
    }

    /**
     * Sets the no-cache header
     */
    protected function setHeaderNoCache($contentType = 'application/json')
    {
        header("Content-Type: " . $contentType . "; charset=utf-8");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }

    /**
     * Puts a given string into a specific short tag
     *
     * @param string $sTag
     * @param array $features
     * @return string
     */
    protected function setStringIntoShortTag($sTag, $features = null)
    {
        return '<' . $sTag . $this->buildAttributesForTag($features)
                . (isset($features['dont_close']) ? '' : '/') . '>';
    }

    /**
     * Puts a given string into a specific tag
     *
     * @param string $sString
     * @param string $sTag
     * @param array $features
     * @return string
     */
    protected function setStringIntoTag($sString, $sTag, $features = null)
    {
        return '<' . $sTag . $this->buildAttributesForTag($features) . '>' . $sString . '</' . $sTag . '>';
    }

    protected function setViewModernLinkAdd($identifier, $ftrs = null)
    {
        $btnText     = '<i class="fa fa-plus-square">&nbsp;</i>' . '&nbsp;' . $this->lclMsgCmn('i18n_AddNewRecord');
        $tagFeatures = [
            'href'  => $this->setViewModernLinkAddUrl($identifier, $ftrs),
            'style' => 'margin: 5px 0px 10px 0px; display: inline-block;',
        ];
        return $this->setStringIntoTag($btnText, 'a', $tagFeatures);
    }

    protected function setViewModernLinkAddInjectedArguments($ftrs = null)
    {
        $sArgmnts = '';
        if (isset($ftrs['injectAddArguments'])) {
            foreach ($ftrs['injectAddArguments'] as $key => $value) {
                $sArgmnts .= '&amp;' . $key . '=' . $value;
            }
        }
        return $sArgmnts;
    }

    protected function setViewModernLinkAddUrl($identifier, $ftrs = null)
    {
        $sArgmnts  = $this->setViewModernLinkAddInjectedArguments($ftrs);
        $this->initializeSprGlbAndSession();
        $addingUrl = $this->tCmnRequest->server->get('PHP_SELF') . '?view=add_' . $identifier . $sArgmnts;
        if (!isset($ftrs['NoAjax'])) {
            $addingUrl = 'javascript:loadAE(\'' . $addingUrl . '\');';
        }
        return $addingUrl;
    }
}
