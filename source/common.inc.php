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

    protected function getContentFromUrlThroughCurl($fullURL, $features = null)
    {
        if (!function_exists('curl_init')) {
            return 'CURL extension is not available... therefore the informations to be obtained by funtion named ' . __FUNCTION__ . ' from ' . __FILE__ . ' could not be obtained!';
        }
        $aReturn = [];
        $ch      = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
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
            $aReturn['error_CURL'] = ['#' => curl_errno($ch), 'description' => curl_error($ch)];
            $aReturn['response']   = [''];
            $aReturn['info']       = [''];
        } else {
            $aReturn['response'] = (json_decode($responseJsonFromClientOriginal, true));
            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    break;
                case JSON_ERROR_DEPTH:
                    $aReturn['error_JSON_encode'] = 'Maximum stack depth exceeded';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $aReturn['error_JSON_encode'] = 'Underflow or the modes mismatch';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $aReturn['error_JSON_encode'] = 'Unexpected control character found';
                    break;
                case JSON_ERROR_SYNTAX:
                    $aReturn['error_JSON_encode'] = 'Syntax error, malformed JSON';
                    break;
                case JSON_ERROR_UTF8:
                    $aReturn['error_JSON_encode'] = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                    break;
                default:
                    $aReturn['error_JSON_encode'] = 'Unknown error';
                    break;
            }
            if (is_array($aReturn['response'])) {
                ksort($aReturn['response']);
            }
            $aReturn['info'] = curl_getinfo($ch);
            ksort($aReturn['info']);
        }
        curl_close($ch);
        return $aReturn;
    }

    /**
     * Returns the IP of the client
     *
     * @return string
     */
    protected function getRealIpAddress()
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
}
