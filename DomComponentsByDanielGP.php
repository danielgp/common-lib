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
trait DomComponentsByDanielGP
{

    private function calculateSelectOptionsSize($aElements, $features_array = [])
    {
        if (!is_array($aElements)) {
            return '';
        } else {
            if (in_array('size', array_keys($features_array))) {
                if ($features_array['size'] == 0) {
                    $selectSize = count($aElements);
                } else {
                    $selectSize = min(count($aElements), $features_array['size']);
                }
            } else {
                $selectSize = 1;
            }
            if ((in_array('include_null', $features_array)) && ($selectSize != '1')) {
                $selectSize++;
            }
            return $selectSize;
        }
    }

    protected function checkIpIsPrivate($ip)
    {
        $ipType = 'unkown';
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE)) {
                $ipType = 'private';
            } else {
                $ipType = 'public';
            }
        } else {
            $ipType = 'invalid';
        }
        return $ipType;
    }

    protected function checkIpIsV4OrV6($ip)
    {
        $ipType = 'unkown';
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ipType = 'V4';
            } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $ipType = 'V6';
            }
        } else {
            $ipType = 'invalid';
        }
        return $ipType;
    }

    protected function checkIpIsInRange($ip, $ipStart, $ipEnd)
    {
        $sReturn = 'out';
        if (filter_var($ipStart, FILTER_VALIDATE_IP)) {
            if (filter_var($ipStart, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ips     = explode('\.', $ipStart);
                $startNo = $ips[3] + $ips[2] * 256 + $ips[1] * 65536 + $ips[0] * 16777216;
            } elseif (filter_var($ipStart, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $startNo = inet_ntop($ipStart);
            }
        } else {
            $sReturn .= '; start IP is invalid';
        }
        if (filter_var($ipEnd, FILTER_VALIDATE_IP)) {
            if (filter_var($ipEnd, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ips   = explode('\.', $ipEnd);
                $endNo = $ips[3] + $ips[2] * 256 + $ips[1] * 65536 + $ips[0] * 16777216;
            } elseif (filter_var($ipEnd, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $endNo = inet_ntop($ipEnd);
            }
        } else {
            $sReturn .= '; end IP is invalid';
        }
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ips         = explode('\.', $ip);
                $evaluatedNo = $ips[3] + $ips[2] * 256 + $ips[1] * 65536 + $ips[0] * 16777216;
            } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $evaluatedNo = inet_ntop($ip);
            }
        } else {
            $sReturn .= '; evaluated IP is invalid';
        }
        if ($sReturn == 'out') {
            if (($evaluatedNo >= $startNo) && ($evaluatedNo <= $endNo)) {
                $sReturn = 'in';
            }
        }
        return $sReturn;
    }

    /**
     * Returns the IP of the client
     *
     * @return string
     */
    protected function getClientRealIpAddress()
    {
        //check ip from share internet
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //to check ip is pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    protected function getUserAgent()
    {
        if (filter_has_var(INPUT_SERVER, "HTTP_USER_AGENT")) {
            $crtUserAgent = filter_input(INPUT_SERVER, "HTTP_USER_AGENT", FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
        } elseif (isset($_SERVER["HTTP_USER_AGENT"])) {
            $crtUserAgent = filter_var($_SERVER["HTTP_USER_AGENT"], FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
        } else {
            $crtUserAgent = null;
        }
        return $crtUserAgent;
    }

    /**
     * Builds a <select> based on a given array
     *
     * @version 20080618
     * @param array $aElements
     * @param string/array $sDefaultValue
     * @param string $select_name
     * @param array $features_array
     * @return string
     */
    protected function setArray2Select($aElements, $sDefaultValue, $select_name, $features_array = null)
    {
        if (!is_array($aElements)) {
            return '';
        }
        $select_id = '" id="' . str_replace(['[', ']'], ['', ''], $select_name)
                . (isset($features_array['id_no']) ? $features_array['id_no'] : '');
        if (isset($features_array['readonly'])) {
            return $this->setStringIntoShortTag('input', [
                        'name'     => $select_name,
                        'id'       => $select_id,
                        'readonly' => 'readonly',
                        'class'    => 'input_readonly',
                        'value'    => $sDefaultValue,
                    ]) . $aElements[$sDefaultValue];
        }
        if (isset($features_array['id_no'])) {
            unset($features_array['id_no']);
        }
        $string2return = '<select name="' . $select_name . $select_id
                . '" size="' . $this->calculateSelectOptionsSize($aElements, $features_array) . '"';
        if (is_array($features_array)) {
            if (in_array('additional_javascript_action', array_keys($features_array))) {
                $temporary_string = $features_array['additional_javascript_action'];
            } else {
                $temporary_string = '';
            }
            if (in_array('autosubmit', $features_array)) {
                $string2return .= ' onchange="javascript:' . $temporary_string . 'submit();"';
            } else {
                if ($temporary_string != '') {
                    $string2return .= ' onchange="javascript:' . $temporary_string . '"';
                }
            }
            if (in_array('disabled', $features_array)) {
                $string2return .= ' disabled="disabled"';
            }
            if (in_array('hidden', $features_array)) {
                $string2return .= ' style="visibility: hidden;"';
            }
            if (in_array('multiselect', $features_array)) {
                $string2return .= ' multiple="multiple"';
            }
        }
        $string2return .= '>'
                . $this->setOptionsForSelect($aElements, $sDefaultValue, $features_array)
                . '</select>';
        return $string2return;
    }

    /**
     * Converts an array to string
     *
     * @version 20141217
     * @param string $sSeparator
     * @param array $aElements
     * @return string
     */
    protected function setArray2String4Url($sSeparator, $aElements, $aExceptedElements = [''])
    {
        if (!is_array($aElements)) {
            return '';
        }
        $sReturn = [];
        reset($aElements);
        foreach ($aElements as $key => $value) {
            if (!in_array($key, $aExceptedElements)) {
                if (is_array($aElements[$key])) {
                    $aCounter = count($aElements[$key]);
                    for ($counter2 = 0; $counter2 < $aCounter; $counter2++) {
                        if ($value[$counter2] != '') {
                            $sReturn[] = $key . '[]=' . $value[$counter2];
                        }
                    }
                } else {
                    if ($value != '') {
                        $sReturn[] = $key . '=' . $value;
                    }
                }
            }
        }
        return implode($sSeparator, $sReturn);
    }

    protected function setCleanUrl($urlString)
    {
        $arrayToReplace = [
            '&#038;'    => '&amp;',
            '&'         => '&amp;',
            '&amp;amp;' => '&amp;',
            ' '         => '%20',
        ];
        $k              = array_keys($arrayToReplace);
        $v              = array_values($arrayToReplace);
        return str_replace($k, $v, filter_var($urlString, FILTER_SANITIZE_URL));
    }

    /**
     * Returns a div tag that clear any float
     *
     * @param integer $height
     */
    protected function setClearBoth1px($height = 1)
    {
        return $this->setStringIntoTag('&nbsp;', 'div', [
                    'style' => implode('', [
                        'height:' . $height . 'px;',
                        'line-height:' . $height . 'px;',
                        'float:none;',
                        'clear:both;',
                        'margin:0px;'
                    ])
        ]);
    }

    /**
     * Returns css codes
     *
     * @param string $cssContent
     * @param array $optionalFlags
     * @return string
     */
    protected function setCssContent($cssContent, $optionalFlags = null)
    {
        if (is_null($optionalFlags)) {
            $attr['media'] = 'all';
        } else {
            $knownAttributes = ['media'];
            foreach ($knownAttributes as $value) {
                if (in_array($value, array_keys($optionalFlags))) {
                    $attr[$value] = $optionalFlags[$value];
                }
            }
        }
        return '<style type="text/css" media="' . $attr['media'] . '">'
                . $cssContent
                . '</style>';
    }

    /**
     * Returns css link to a given file
     *
     * @param string $cssFile
     * @return string
     */
    protected function setCssFile($cssFileName)
    {
        return '<link rel="stylesheet" type="text/css" href="'
                . filter_var($cssFileName, FILTER_SANITIZE_STRING)
                . '" />';
    }

    /**
     * Outputs an HTML footer
     *
     * @param array $footerInjected
     * @return string
     */
    protected function setFooterCommon($footerInjected = null)
    {
        $sReturn = [];
        if (!is_null($footerInjected)) {
            if (is_array($footerInjected)) {
                $sReturn[] = implode('', $footerInjected);
            } else {
                $sReturn[] = $footerInjected;
            }
        }
        $sReturn[] = '</body>';
        $sReturn[] = '</html>';
        return implode('', $sReturn);
    }

    /**
     * Sets the gzip footer for HTML
     */
    protected function setFooterGZiped()
    {
        if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            if (strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
                if (extension_loaded('zlib')) {
                    $gzip_contents = ob_get_contents();
                    ob_end_clean();
                    $gzip_size     = strlen($gzip_contents);
                    $gzip_crc      = crc32($gzip_contents);
                    $gzip_contents = gzcompress($gzip_contents, 9);
                    $gzip_contents = substr($gzip_contents, 0, strlen($gzip_contents) - 4);
                    echo "\x1f\x8b\x08\x00\x00\x00\x00\x00";
                    echo $gzip_contents;
                    echo pack('V', $gzip_crc);
                    echo pack('V', $gzip_size);
                }
            }
        }
    }

    /**
     * Outputs an HTML header
     *
     * @param array $headerFeatures
     * @return string
     */
    protected function setHeaderCommon($headerFeatures = null)
    {
        $fixedHeaderElements = [
            'start'    => '<!DOCTYPE html>',
            'lang'     => '<html lang="en-US">',
            'head'     => '<head>',
            'charset'  => '<meta charset="utf-8" />',
            'viewport' => '<meta name="viewport" content="width=device-width" />',
        ];
        $sReturn             = [];
        if (!is_null($headerFeatures)) {
            if (is_array($headerFeatures)) {
                $aFeatures = [];
                foreach ($headerFeatures as $key => $value) {
                    switch ($key) {
                        case 'css':
                            if (is_array($value)) {
                                foreach ($value as $value2) {
                                    $aFeatures[] = $this->setCssFile(filter_var($value2, FILTER_SANITIZE_URL));
                                }
                            } else {
                                $aFeatures[] = $this->setCssFile(filter_var($value, FILTER_SANITIZE_URL));
                            }
                            break;
                        case 'javascript':
                            if (is_array($value)) {
                                foreach ($value as $value2) {
                                    $aFeatures[] = $this->setJavascriptFile(filter_var($value2, FILTER_SANITIZE_URL));
                                }
                            } else {
                                $aFeatures[] = $this->setJavascriptFile(filter_var($value, FILTER_SANITIZE_URL));
                            }
                            break;
                        case 'lang':
                            $fixedHeaderElements['lang'] = '<html lang="'
                                    . filter_var($value, FILTER_SANITIZE_STRING) . '">';
                            break;
                        case 'title':
                            $aFeatures[]                 = '<title>'
                                    . filter_var($value, FILTER_SANITIZE_STRING) . '</title>';
                            break;
                    }
                }
                $sReturn[] = implode('', $fixedHeaderElements)
                        . implode('', $aFeatures);
            } else {
                $sReturn = implode('', $fixedHeaderElements)
                        . '</head>'
                        . '<body>'
                        . '<p style="background-color:red;color:#FFF;">The parameter sent to '
                        . __FUNCTION__ . ' must be an array</p>'
                        . $this->setFooterCommon();
                throw new \Exception($sReturn);
            }
        }
        return implode('', $sReturn)
                . '</head>'
                . '<body>';
    }

    /**
     * Sets the gzip header for HTML
     */
    protected function setHeaderGZiped()
    {
        if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            if (strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
                if (extension_loaded('zlib')) {
                    ob_start();
                    ob_implicit_flush(0);
                    header('Content-Encoding: gzip');
                }
            }
        }
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
     * Returns javascript link to a given file
     *
     * @param string $jsFileName
     * @return string
     */
    protected function setJavascriptFile($jsFileName, $hostsWithoutCDNrequired = null)
    {
        $sReturn           = null;
        $clientAddressType = $this->checkIpIsPrivate($this->getClientRealIpAddress());
        if ($clientAddressType == 'private') {
            $sReturn = '<script type="text/javascript" src="' . $jsFileName . '"></script>';
        } else {
            $patternFound = $this->setJavascriptFileCDN($jsFileName, $hostsWithoutCDNrequired);
            $sReturn      = '<script type="text/javascript" src="' . $patternFound[1] . '"></script>'
                    . $patternFound[2];
        }
        return $sReturn;
    }

    private function setJavascriptFileCDN($jsFileName, $hostsWithoutCDNrequired)
    {
        $patternFound = [
            false,
            filter_var($jsFileName, FILTER_SANITIZE_STRING),
            '',
        ];
        /**
         * if within local network makes no sense to use CDNs
         */
        if (!in_array($_SERVER['REMOTE_ADDR'], $hostsWithoutCDNrequired)) {
            if (strpos($jsFileName, 'jquery-') !== false) {
                $patternFound = $this->setJavascriptFileCDNjQuery($jsFileName);
            } elseif (strpos($jsFileName, 'jquery.placeholder.min.js') !== false) {
                $patternFound = $this->setJavascriptFileCDNjQueryLibs($jsFileName);
            } elseif (strpos($jsFileName, 'jquery.easing.1.3.min.js') !== false) {
                $patternFound = $this->setJavascriptFileCDNjQueryLibs($jsFileName);
            } elseif (strpos($jsFileName, 'highcharts-') !== false) {
                $patternFound = $this->setJavascriptFileCDNforHighCharts($jsFileName);
            } elseif (strpos($jsFileName, 'exporting-') !== false) {
                $patternFound = $this->setJavascriptFileCDNforHighChartsExporting($jsFileName);
            }
        }
        return $patternFound;
    }

    private function setJavascriptFileCDNforHighCharts($jsFileName)
    {
        $jQueryPosition = strpos($jsFileName, 'highcharts');
        if ($jQueryPosition !== false) {
            $patternFound = [
                true,
                implode('', [
                    '//cdnjs.cloudflare.com/ajax/libs/highcharts/',
                    str_replace(['highcharts-', '.min.js'], '', pathinfo($jsFileName)['basename']),
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

    private function setJavascriptFileCDNforHighChartsExporting($jsFileName)
    {
        $jQueryPosition = strpos($jsFileName, 'exporting');
        if ($jQueryPosition !== false) {
            $patternFound = [
                true,
                implode('', [
                    '//code.highcharts.com/',
                    str_replace(['exporting-', '.min.js'], '', pathinfo($jsFileName)['basename']),
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

    private function setJavascriptFileCDNjQuery($jsFileName)
    {
        $jQueryPosition = strpos($jsFileName, 'jquery-');
        if (($jQueryPosition !== false) && (substr($jsFileName, -7) == '.min.js')) {
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

    private function setJavascriptFileCDNjQueryLibs($jsFileName)
    {
        $version  = null;
        $justFile = pathinfo($jsFileName)['basename'];
        switch ($justFile) {
            case 'jquery.placeholder.min.js':
                $version                   = 'jquery-placeholder/2.0.8/';
                $functionExistanceToVerify = 'jQuery.placeholder';
                break;
            case 'jquery.easing.1.3.min.js':
                $version                   = 'jquery-easing/1.3/';
                $functionExistanceToVerify = 'jQuery.easing["jswing"]';
                $justFile                  = str_replace('.1.3', '', $justFile);
                break;
        }
        if (!is_null($version)) {
            $patternFound = [
                true,
                implode('', [
                    '//cdnjs.cloudflare.com/ajax/libs/',
                    $version,
                    $justFile,
                ]),
                implode('', [
                    '<script>' . $functionExistanceToVerify . ' || document.write(\'<script src="',
                    filter_var($jsFileName, FILTER_SANITIZE_STRING),
                    '">\x3C/script>\')</script>'
                ])
            ];
        }
        return $patternFound;
    }

    /**
     * Returns javascript codes from given file
     *
     * @param string $jsFileName
     * @return string
     */
    protected function setJavascriptFileContent($jsFileName)
    {
        $sReturn[] = '<script type="text/javascript"><!-- ';
        $sReturn[] = $this->getExternalFileContent($jsFileName);
        $sReturn[] = ' //--></script>';
        return implode('', $sReturn);
    }

    private function setOptionsForSelect($aElements, $sDefaultValue, $features_array = [])
    {
        $string2return = '';
        if (is_array($features_array)) {
            if (in_array('include_null', $features_array)) {
                $string2return .= '<option value="">&nbsp;</option>';
            }
            if (isset($features_array['defaultValue_isSubstring'])) {
                $default_value_array = explode($features_array['defaultValue_isSubstring'], $sDefaultValue);
            }
        }
        $current_group = null;
        foreach ($aElements as $key => $value) {
            if (isset($features_array['grouping'])) {
                $temporary_string = substr($value, 0, strpos($value, $features_array['grouping']) + 1);
                if ($current_group != $temporary_string) {
                    if ($current_group != '') {
                        $string2return .= '</optgroup>';
                    }
                    $current_group = $temporary_string;
                    $string2return .= '<optgroup label="'
                            . str_replace($features_array['grouping'], '', $current_group) . '">';
                }
            } else {
                $current_group = '';
            }
            $string2return .= '<option value="' . $key . '"';
            if (is_array($sDefaultValue)) {
                if (in_array($key, $sDefaultValue)) {
                    $string2return .= ' selected="selected"';
                }
            } else {
                if (strcasecmp($key, $sDefaultValue) === 0) {
                    $string2return .= ' selected="selected"';
                }
                if (isset($default_value_array) && is_array($default_value_array)) {
                    if (in_array($key, $default_value_array)) {
                        $string2return .= ' selected="selected"';
                    }
                }
            }
            $string2return .= '>' . str_replace(['&', $current_group], ['&amp;', ''], $value) . '</option>';
        }
        if (isset($features_array['grouping'])) {
            if ($current_group != '') {
                $string2return .= '</optgroup>';
            }
        }
        return $string2return;
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
        $attributes = '';
        if (!is_null($features)) {
            foreach ($features as $key => $value) {
                if ($key != 'dont_close') {
                    $attributes .= ' ' . $key . '="';
                    if (is_array($value)) {
                        foreach ($value as $key2 => $value2) {
                            $attributes .= $key2 . ':' . $value2 . ';';
                        }
                    } else {
                        $attributes .= str_replace('"', '\'', $value);
                    }
                    $attributes .= '"';
                }
            }
        }
        if (isset($features['dont_close'])) {
            $sReturn = '<' . $sTag . $attributes . '>';
        } else {
            $sReturn = '<' . $sTag . $attributes . ' />';
        }
        return $sReturn;
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
        $attributes = '';
        if (!is_null($features)) {
            foreach ($features as $key => $value) {
                $attributes .= ' ' . $key . '="';
                if (is_array($value)) {
                    foreach ($value as $key2 => $value2) {
                        $attributes .= $key2 . ':' . $value2 . ';';
                    }
                } else {
                    $attributes .= $value;
                }
                $attributes .= '"';
            }
        }
        return '<' . $sTag . $attributes . '>' . $sString . '</' . $sTag . '>';
    }
}
