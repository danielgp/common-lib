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
 * Useful functions to get quick results
 *
 * @author Daniel Popiniuc
 */
trait CommonCode
{

    use CommonViews,
        MySQLiByDanielGPtypes;

    /**
     * Reads the content of a remote file through CURL extension
     *
     * @param string $fullURL
     * @param array $features
     * @return string
     */
    public function getContentFromUrlThroughCurl($fullURL, $features = null)
    {
        if (!function_exists('curl_init')) {
            $aReturn = ['info' => $this->lclMsgCmn('i18n_Error_ExtensionNotLoaded'), 'response' => ''];
            return $this->setArrayToJson($aReturn);
        }
        if (!filter_var($fullURL, FILTER_VALIDATE_URL)) {
            $aReturn = ['info' => $this->lclMsgCmn('i18n_Error_GivenUrlIsNotValid'), 'response' => ''];
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
     * @return array
     */
    public function getContentFromUrlThroughCurlAsArrayIfJson($fullURL, $features = null)
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

    protected function getContentFromUrlThroughCurlRaw($fullURL, $features = null)
    {
        $chanel = curl_init();
        curl_setopt($chanel, CURLOPT_USERAGENT, $this->getUserAgentByCommonLib());
        if ((strpos($fullURL, 'https') !== false)) {
            $chk = false;
            if (isset($features['forceSSLverification'])) {
                $chk = true;
            }
            curl_setopt($chanel, CURLOPT_SSL_VERIFYHOST, $chk);
            curl_setopt($chanel, CURLOPT_SSL_VERIFYPEER, $chk);
        }
        curl_setopt($chanel, CURLOPT_URL, $fullURL);
        curl_setopt($chanel, CURLOPT_HEADER, false);
        curl_setopt($chanel, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chanel, CURLOPT_FRESH_CONNECT, true); //avoid a cached response
        curl_setopt($chanel, CURLOPT_FAILONERROR, true);
        $aReturn = [curl_exec($chanel), curl_getinfo($chanel), curl_errno($chanel), curl_error($chanel)];
        curl_close($chanel);
        return ['response' => $aReturn[0], 'info' => $aReturn[1], 'errNo' => $aReturn[2], 'errMsg' => $aReturn[3]];
    }

    protected function getContentFromUrlThroughCurlRawArray($fullURL, $features = null)
    {
        $curlFeedback = $this->getContentFromUrlThroughCurlRaw($fullURL, $features);
        if ($curlFeedback['errNo'] !== 0) {
            $info = $this->setArrayToJson(['#' => $curlFeedback['errNo'], 'description' => $curlFeedback['errMsg']]);
            return ['info' => $info, 'response' => ''];
        }
        return ['info' => $this->setArrayToJson($curlFeedback['info']), 'response' => $curlFeedback['response']];
    }

    protected function getFeedbackMySQLAffectedRecords()
    {
        if (is_null($this->mySQLconnection)) {
            return '<div>No MySQL connection detected</div>';
        }
        $afRows = $this->mySQLconnection->affected_rows;
        return '<div>' . sprintf($this->lclMsgCmnNumber('i18n_Record', 'i18n_Records', $afRows), $afRows) . '</div>';
    }

    /**
     * returns the details about Communicator (current) file
     *
     * @param string $fileGiven
     * @return array
     */
    public function getFileDetails($fileGiven)
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
    public function getListOfFiles($pathAnalised)
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
    public function getTimestamp($returnType = 'string')
    {
        if (in_array($returnType, ['array', 'float', 'string'])) {
            return $this->getTimestampRaw($returnType);
        }
        return sprintf($this->lclMsgCmn('i18n_Error_UnknownReturnType'), $returnType);
    }

    /**
     * Tests if given string has a valid Json format
     *
     * @param string|null|array $inputJson
     * @return boolean|string
     */
    public function isJsonByDanielGP($inputJson)
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
     */
    protected function sendBackgroundEncodedFormElementsByPost($urlToSendTo, $params = [])
    {
        $postingUrl = filter_var($urlToSendTo, FILTER_VALIDATE_URL);
        if ($postingUrl === false) {
            throw new \Exception('Invalid URL in ' . __FUNCTION__);
        }
        if ($params !== []) {
            $cntFailErrMsg = $this->lclMsgCmn('i18n_Error_FailedToConnect');
            $this->sendBackgroundPostData($postingUrl, $params, $cntFailErrMsg);
            return '';
        }
        throw new \Exception($this->lclMsgCmn('i18n_Error_GivenParameterIsNotAnArray'));
    }

    private function sendBackgroundPostData($postingUrl, $params, $cntFailErrMsg)
    {
        $postingString = $this->setArrayToStringForUrl('&', $params);
        $pUrlParts     = parse_url($postingUrl);
        $postingPort   = 80;
        if (isset($pUrlParts['port'])) {
            $postingPort = $pUrlParts['port'];
        }
        $flPointer = fsockopen($pUrlParts['host'], $postingPort, $erN, $erM, 30);
        if ($flPointer === false) {
            throw new \Exception($cntFailErrMsg . ': ' . $erN . ' (' . $erM . ')');
        }
        fwrite($flPointer, $this->sendBackgroundPrepareData($pUrlParts, $postingString));
        fclose($flPointer);
    }

    private function sendBackgroundPrepareData($pUrlParts, $postingString)
    {
        $this->initializeSprGlbAndSession();
        $out   = [];
        $out[] = 'POST ' . $pUrlParts['path'] . ' ' . $this->tCmnSuperGlobals->server->get['SERVER_PROTOCOL'];
        $out[] = 'Host: ' . $pUrlParts['host'];
        $out[] = 'User-Agent: ' . $this->getUserAgentByCommonLib();
        $out[] = 'Content-Type: application/x-www-form-urlencoded';
        $out[] = 'Content-Length: ' . strlen($postingString);
        $out[] = 'Connection: Close' . "\r\n";
        $out[] = $postingString;
        return implode("\r\n", $out);
    }

    /**
     * Converts a JSON string into an Array
     *
     * @param string $inputJson
     * @return array
     */
    public function setJsonToArray($inputJson)
    {
        if (!$this->isJsonByDanielGP($inputJson)) {
            return ['error' => $this->lclMsgCmn('i18n_Error_GivenInputIsNotJson')];
        }
        $sReturn   = (json_decode($inputJson, true));
        $jsonError = $this->setJsonErrorInPlainEnglish();
        if ($jsonError == '') {
            return $sReturn;
        }
        return ['error' => $jsonError];
    }
}
