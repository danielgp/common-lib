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

    /**
     * Calculate the optimal for all options within a select tag
     *
     * @param array $aElements
     * @param array $aFeatures
     * @return string|int
     */
    private function calculateSelectOptionsSize($aElements, $aFeatures = [])
    {
        if (is_null($aFeatures)) {
            $aFeatures = [];
        }
        if (is_array($aElements)) {
            if (isset($aFeatures['size'])) {
                if ($aFeatures['size'] == 0) {
                    $selectSize = count($aElements);
                } else {
                    $selectSize = min(count($aElements), $aFeatures['size']);
                }
            } else {
                $selectSize = 1;
            }
            if ((in_array('include_null', $aFeatures)) && ($selectSize != '1')) {
                $selectSize++;
            }
            return $selectSize;
        } else {
            return '';
        }
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
    protected function setArrayToSelect($aElements, $sDefaultValue, $select_name, $features_array = null)
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
            if (isset($features_array['additional_javascript_action'])) {
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
    protected function setArrayToStringForUrl($sSeparator, $aElements, $aExceptedElements = [''])
    {
        if (is_array($aElements)) {
            if (count($aElements) == 0) {
                return '';
            }
        } else {
            return '';
        }
        $sReturn = [];
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
    protected function setCssFile($cssFileName, $hostsWithoutCDNrequired = null)
    {
        $sReturn = null;
        if (is_null($hostsWithoutCDNrequired)) {
            $hostsWithoutCDNrequired = [];
        }
        if (in_array($this->getClientRealIpAddress(), $hostsWithoutCDNrequired)) {
            $sReturn = '<link rel="stylesheet" type="text/css" href="'
                    . filter_var($cssFileName, FILTER_SANITIZE_STRING)
                    . '" />';
        } else {
            $patternFound = $this->setCssFileCDN($cssFileName);
            $sReturn      = '<link rel="stylesheet" type="text/css" href="'
                    . filter_var($patternFound[1], FILTER_SANITIZE_STRING)
                    . '" />';
        }
        return $sReturn;
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
            'viewport' => '<meta name="viewport" content="width=device-width, initial-scale=1" />',
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
        $sReturn = null;
        if (is_null($hostsWithoutCDNrequired)) {
            $hostsWithoutCDNrequired = [];
        }
        if (in_array($this->getClientRealIpAddress(), $hostsWithoutCDNrequired)) {
            $sReturn = '<script type="text/javascript" src="' . $jsFileName . '"></script>';
        } else {
            $patternFound = $this->setJavascriptFileCDN($jsFileName);
            $sReturn      = '<script type="text/javascript" src="' . $patternFound[1] . '"></script>'
                    . $patternFound[2];
        }
        return $sReturn;
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

    /**
     * Creates all the child tags required to populate a SELECT tag
     *
     * @param array $aElements
     * @param string|array $sDefaultValue
     * @param array $features_array
     * @return string
     */
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
