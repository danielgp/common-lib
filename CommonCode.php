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

trait CommonCode
{

    use RomanianHolidays;

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

    protected function getContentFromUrlThroughCurl($fullURL, $features = null)
    {
        if (!function_exists('curl_init')) {
            $aReturn['info']     = 'CURL extension is not available...'
                . 'therefore the informations to be obtained by funtion named '
                . __FUNCTION__ . ' from ' . __FILE__
                . ' could not be obtained!';
            $aReturn['response'] = '';
        }
        if (!filter_var($fullURL, FILTER_VALIDATE_URL)) {
            $aReturn['info']     = 'URL is not valid...';
            $aReturn['response'] = '';
        }
        $aReturn          = [];
        $ch               = curl_init();
        $currentUserAgent = filter_input(INPUT_SERVER, $_SERVER['HTTP_USER_AGENT'], FILTER_SANITIZE_STRING);
        curl_setopt($ch, CURLOPT_USERAGENT, $currentUserAgent);
        if ((strpos($fullURL, "https") !== false) || (isset($features['forceSSLverification']))) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        curl_setopt($ch, CURLOPT_URL, $fullURL);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true); //avoid a cached response
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        $responseJsonFromClientOriginal = curl_exec($ch);
        if (curl_errno($ch)) {
            $aReturn['info']     = [
                '#'           => curl_errno($ch),
                'description' => curl_error($ch)
            ];
            $aReturn['response'] = '';
        } else {
            $aReturn['info']     = curl_getinfo($ch);
            $aReturn['response'] = (json_decode($responseJsonFromClientOriginal, true));
            if (is_array($aReturn['response'])) {
                ksort($aReturn['response']);
            }
            $aReturn['info']['error_JSON_encode'] = $this->setJsonErrorInPlainEnglish();
            ksort($aReturn['info']);
        }
        curl_close($ch);
        return $aReturn;
    }

    protected function getTimestamp()
    {
        $dt          = microtime(true);
        $miliSeconds = floor((gettimeofday()['usec'] / 1000));
        $l           = strlen($miliSeconds);
        if ($l < 3) {
            $miliSeconds = str_repeat('0', (3 - $l)) . $miliSeconds;
        }
        return ('<span style="color:black!important;font-weight:bold;">['
            . date('Y-m-d H:i:s.', $dt) . $miliSeconds . ']</span> ');
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

    /**
     * Returns css codes
     *
     * @param string $cssContent
     * @return string
     */
    protected function setCssContent($cssContent)
    {
        return '<style>' . $cssContent . '</style>';
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
    protected function setJavascriptFile($jsFileName)
    {
        return '<script type="text/javascript" src="'
            . filter_var($jsFileName, FILTER_SANITIZE_STRING)
            . '"></script>';
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
        return implode(PHP_EOL, $sReturn);
    }

    private function setJsonErrorInPlainEnglish()
    {
        $knownErrors  = [
            JSON_ERROR_NONE           => null,
            JSON_ERROR_DEPTH          => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR      => 'Unexpected control character found',
            JSON_ERROR_SYNTAX         => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8           => 'Malformed UTF-8 characters, possibly incorrectly encoded',
        ];
        $currentError = json_last_error();
        if (in_array($currentError, $knownErrors)) {
            $sReturn = $knownErrors[$currentError];
        } else {
            $sReturn = null;
        }
        return $sReturn;
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
