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
 * usefull functions to get quick results
 *
 * @author Daniel Popiniuc
 */
trait CommonCode
{

    use CommonBasic,
        DomComponentsByDanielGP,
        MySQLiByDanielGPqueries,
        MySQLiByDanielGP;

    protected function arrayDiffAssocRecursive($array1, $array2)
    {
        $difference = [];
        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!isset($array2[$key]) || !is_array($array2[$key])) {
                    $difference[$key] = $value;
                } else {
                    $workingDiff = $this->arrayDiffAssocRecursive($value, $array2[$key]);
                    if (!empty($workingDiff)) {
                        $difference[$key] = $workingDiff;
                    }
                }
            } elseif (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
                $difference[$key] = $value;
            }
        }
        return $difference;
    }

    private function buildArrayForCurlInterogation($chanel, $rspJsonFromClient)
    {
        if (curl_errno($chanel)) {
            return [
                'info'     => $this->setArrayToJson(['#' => curl_errno($chanel), 'description' => curl_error($chanel)]),
                'response' => '',
            ];
        }
        return [
            'info'     => $this->setArrayToJson(curl_getinfo($chanel)),
            'response' => $rspJsonFromClient,
        ];
    }

    /**
     * Reads the content of a remote file through CURL extension
     *
     * @param string $fullURL
     * @param array $features
     * @return blob
     */
    protected function getContentFromUrlThroughCurl($fullURL, $features = null)
    {
        if (!function_exists('curl_init')) {
            $aReturn = [
                'info'     => $this->lclMsgCmn('i18n_Error_ExtensionNotLoaded'),
                'response' => '',
            ];
            return $this->setArrayToJson($aReturn);
        }
        if (!filter_var($fullURL, FILTER_VALIDATE_URL)) {
            $aReturn = [
                'info'     => $this->lclMsgCmn('i18n_Error_GivenUrlIsNotValid'),
                'response' => '',
            ];
            return $this->setArrayToJson($aReturn);
        }
        $aReturn = $this->getContentFromUrlThroughCurlRawArray($fullURL, $features);
        return '{ ' . $this->packIntoJson($aReturn, 'info') . ', ' . $this->packIntoJson($aReturn, 'response') . ' }';
    }

    /**
     * Reads the content of a remote file through CURL extension
     *
     * @param string $fullURL
     * @param array $features
     * @return blob
     */
    protected function getContentFromUrlThroughCurlAsArrayIfJson($fullURL, $features = null)
    {
        $result = $this->setJsonToArray($this->getContentFromUrlThroughCurl($fullURL, $features));
        if (is_array($result['info'])) {
            ksort($result['info']);
        }
        if (is_array($result['response'])) {
            ksort($result['response']);
        }
        return $result;
    }

    protected function getContentFromUrlThroughCurlRawArray($fullURL, $features = null)
    {
        $chanel = curl_init();
        curl_setopt($chanel, CURLOPT_USERAGENT, $this->getUserAgentByCommonLib());
        if ((strpos($fullURL, 'https') !== false) || (isset($features['forceSSLverification']))) {
            curl_setopt($chanel, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($chanel, CURLOPT_SSL_VERIFYPEER, false);
        }
        curl_setopt($chanel, CURLOPT_URL, $fullURL);
        curl_setopt($chanel, CURLOPT_HEADER, false);
        curl_setopt($chanel, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chanel, CURLOPT_FRESH_CONNECT, true); //avoid a cached response
        curl_setopt($chanel, CURLOPT_FAILONERROR, true);
        $rspJsonFromClient = curl_exec($chanel);
        $aReturn           = $this->buildArrayForCurlInterogation($chanel, $rspJsonFromClient);
        curl_close($chanel);
        return $aReturn;
    }

    protected function getFeedbackMySQLAffectedRecords()
    {
        if (is_null($this->mySQLconnection)) {
            $message = 'No MySQL';
        } else {
            $afRows  = $this->mySQLconnection->affected_rows;
            $message = sprintf($this->lclMsgCmnNumber('i18n_Record', 'i18n_Records', $afRows), $afRows);
        }
        return '<div>' . $message . '</div>';
    }

    /**
     * returns the details about Communicator (current) file
     *
     * @param string $fileGiven
     * @return array
     */
    protected function getFileDetails($fileGiven)
    {
        if (!file_exists($fileGiven)) {
            return ['error' => sprintf($this->lclMsgCmn('i18n_Error_GivenFileDoesNotExist'), $fileGiven)];
        }
        return $this->getFileDetailsRaw($fileGiven);
    }

    /**
     * returns a multi-dimensional array with list of file details within a given path
     * (by using Symfony/Finder package)
     *
     * @param  string $pathAnalised
     * @return array
     */
    protected function getListOfFiles($pathAnalised)
    {
        if (realpath($pathAnalised) === false) {
            return ['error' => sprintf($this->lclMsgCmn('i18n_Error_GivenPathIsNotValid'), $pathAnalised)];
        } elseif (!is_dir($pathAnalised)) {
            return ['error' => $this->lclMsgCmn('i18n_Error_GivenPathIsNotFolder')];
        }
        $finder   = new \Symfony\Component\Finder\Finder();
        $iterator = $finder->files()->sortByName()->in($pathAnalised);
        $aFiles   = null;
        foreach ($iterator as $file) {
            $aFiles[$file->getRealPath()] = $this->getFileDetails($file);
        }
        return $aFiles;
    }

    /**
     * Returns server Timestamp into various formats
     *
     * @param string $returnType
     * @return string
     */
    protected function getTimestamp($returnType = 'string')
    {
        $crtTime          = gettimeofday();
        $knownReturnTypes = ['array', 'float', 'string'];
        if (in_array($returnType, $knownReturnTypes)) {
            return call_user_func([$this, 'getTimestamp' . ucfirst($returnType)], $crtTime);
        }
        return sprintf($this->lclMsgCmn('i18n_Error_UnknownReturnType'), $returnType);
    }

    private function getTimestampArray($crtTime)
    {
        return [
            'float'  => $this->getTimestampFloat($crtTime),
            'string' => $this->getTimestampString($crtTime),
        ];
    }

    private function getTimestampFloat($crtTime)
    {
        return ($crtTime['sec'] + $crtTime['usec'] / pow(10, 6));
    }

    private function getTimestampString($crtTime)
    {
        return implode('', [
            '<span style="color:black!important;font-weight:bold;">[',
            date('Y-m-d H:i:s.', $crtTime['sec']),
            substr(round($crtTime['usec'], -3), 0, 3),
            ']</span> '
        ]);
    }

    /**
     * Tests if given string has a valid Json format
     *
     * @param string $inputJson
     * @return boolean|string
     */
    protected function isJsonByDanielGP($inputJson)
    {
        if (is_string($inputJson)) {
            json_decode($inputJson);
            return (json_last_error() == JSON_ERROR_NONE);
        }
        return $this->lclMsgCmn('i18n_Error_GivenInputIsNotJson');
    }

    private function packIntoJson($aReturn, $keyToWorkWith)
    {
        if ($this->isJsonByDanielGP($aReturn[$keyToWorkWith])) {
            return '"' . $keyToWorkWith . '": ' . $aReturn[$keyToWorkWith];
        }
        return '"' . $keyToWorkWith . '": {' . $aReturn[$keyToWorkWith] . ' }';
    }

    /**
     * Send an array of parameters like a form through a POST action
     *
     * @param string $urlToSendTo
     * @param array $params
     * @throws \Exception
     * @throws \UnexpectedValueException
     */
    protected function sendBackgroundEncodedFormElementsByPost($urlToSendTo, $params = [])
    {
        $postingUrl = filter_var($urlToSendTo, FILTER_VALIDATE_URL);
        if ($postingUrl === false) {
            throw new \Exception('Invalid URL in ' . __FUNCTION__);
        }
        if (is_array($params)) {
            $postingString = $this->setArrayToStringForUrl('&', $params);
            $pUrlParts     = parse_url($postingUrl);
            $postingPort   = (isset($pUrlParts['port']) ? $pUrlParts['port'] : 80);
            $flPointer     = fsockopen($pUrlParts['host'], $postingPort, $errNo, $errorMessage, 30);
            if ($flPointer === false) {
                throw new \UnexpectedValueException($this->lclMsgCmn('i18n_Error_FailedToConnect') . ': '
                . $errNo . ' (' . $errorMessage . ')');
            }
            fwrite($flPointer, $this->sendBackgroundPrepareData($pUrlParts, $postingString));
            fclose($flPointer);
            return '';
        }
        throw new \UnexpectedValueException($this->lclMsgCmn('i18n_Error_GivenParameterIsNotAnArray'));
    }

    protected function sendBackgroundPrepareData($pUrlParts, $postingString)
    {
        $this->initializeSprGlbAndSession();
        $out   = [];
        $out[] = 'POST ' . $pUrlParts['path'] . ' ' . $this->tCmnSuperGlobals->server->get['SERVER_PROTOCOL'];
        $out[] = 'Host: ' . $pUrlParts['host'];
        if (is_null($this->tCmnSuperGlobals->server->get('HTTP_USER_AGENT'))) {
            $out[] = 'User-Agent: ' . $this->tCmnSuperGlobals->server->get('HTTP_USER_AGENT');
        }
        $out[] = 'Content-Type: application/x-www-form-urlencoded';
        $out[] = 'Content-Length: ' . strlen($postingString);
        $out[] = 'Connection: Close' . "\r\n";
        $out[] = $postingString;
        return implode("\r\n", $out);
    }

    /**
     * Returns proper result from a mathematical division in order to avoid Zero division erorr or Infinite results
     *
     * @param float $fAbove
     * @param float $fBelow
     * @param mixed $mArguments
     * @return decimal
     */
    protected function setDividedResult($fAbove, $fBelow, $mArguments = 0)
    {
        if (($fAbove == 0) || ($fBelow == 0)) { // prevent infinite result AND division by 0
            return 0;
        }
        if (is_array($mArguments)) {
            $frMinMax = [
                'MinFractionDigits' => $mArguments[1],
                'MaxFractionDigits' => $mArguments[1],
            ];
            return $this->setNumberFormat(($fAbove / $fBelow), $frMinMax);
        }
        return $this->setNumberFormat(round(($fAbove / $fBelow), $mArguments));
    }

    /**
     * Converts a JSON string into an Array
     *
     * @param string $inputJson
     * @return array
     */
    protected function setJsonToArray($inputJson)
    {
        if (!$this->isJsonByDanielGP($inputJson)) {
            return ['error' => $this->lclMsgCmn('i18n_Error_GivenInputIsNotJson')];
        }
        $sReturn   = (json_decode($inputJson, true));
        $jsonError = $this->setJsonErrorInPlainEnglish();
        if (is_null($jsonError)) {
            return $sReturn;
        } else {
            return ['error' => $jsonError];
        }
    }
}
