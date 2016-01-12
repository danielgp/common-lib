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

    use CommonLibLocale,
        CommonPermissions,
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

    /**
     * Reads the content of a remote file through CURL extension
     *
     * @param string $fullURL
     * @param array $features
     * @return blob
     */
    protected function getContentFromUrlThroughCurl($fullURL, $features = null)
    {
        $aReturn = [];
        if (!function_exists('curl_init')) {
            $aReturn['info']     = $this->lclMsgCmn('i18n_Error_ExtensionNotLoaded');
            $aReturn['response'] = '';
        }
        if (!filter_var($fullURL, FILTER_VALIDATE_URL)) {
            $aReturn['info']     = $this->lclMsgCmn('i18n_Error_GivenUrlIsNotValid');
            $aReturn['response'] = '';
        }
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
        if (curl_errno($chanel)) {
            $aReturn['info']     = $this->setArrayToJson([
                '#'           => curl_errno($chanel),
                'description' => curl_error($chanel)
            ]);
            $aReturn['response'] = '';
        } else {
            $aReturn['info']     = $this->setArrayToJson(curl_getinfo($chanel));
            $aReturn['response'] = $rspJsonFromClient;
        }
        curl_close($chanel);
        $sReturn = '';
        if ($this->isJsonByDanielGP($aReturn['info'])) {
            $sReturn = '"info": ' . $aReturn['info'];
        } else {
            $sReturn = '"info": {' . $aReturn['info'] . ' }';
        }
        $sReturn .= ', ';
        if ($this->isJsonByDanielGP($aReturn['response'])) {
            $sReturn .= '"response": ' . $aReturn['response'];
        } else {
            $sReturn .= '"response": { ' . $aReturn['response'] . ' }';
        }
        return '{ ' . $sReturn . ' }';
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
        if (isset($result['info'])) {
            if (is_array($result['info'])) {
                ksort($result['info']);
            }
        }
        if (isset($result['response'])) {
            if (is_array($result['response'])) {
                ksort($result['response']);
            }
        }
        return $result;
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
        $info = new \SplFileInfo($fileGiven);
        return [
            'File Extension'         => $info->getExtension(),
            'File Group'             => $info->getGroup(),
            'File Inode'             => $info->getInode(),
            'File Link Target'       => ($info->isLink() ? $info->getLinkTarget() : '-'),
            'File is Dir'            => $info->isDir(),
            'File is Executable'     => $info->isExecutable(),
            'File is File'           => $info->isFile(),
            'File is Link'           => $info->isLink(),
            'File is Readable'       => $info->isReadable(),
            'File is Writable'       => $info->isWritable(),
            'File Name'              => $info->getBasename('.' . $info->getExtension()),
            'File Name w. Extension' => $info->getFilename(),
            'File Owner'             => $info->getOwner(),
            'File Path'              => $info->getPath(),
            'File Permissions'       => $this->explainPerms($info->getPerms()),
            'Name'                   => $info->getRealPath(),
            'Size'                   => $info->getSize(),
            'Sha1'                   => sha1_file($fileGiven),
            'Timestamp Accessed'     => [
                'PHP number' => $info->getATime(),
                'SQL format' => date('Y-m-d H:i:s', $info->getATime()),
            ],
            'Timestamp Changed'      => [
                'PHP number' => $info->getCTime(),
                'SQL format' => date('Y-m-d H:i:s', $info->getCTime()),
            ],
            'Timestamp Modified'     => [
                'PHP number' => $info->getMTime(),
                'SQL format' => date('Y-m-d H:i:s', $info->getMTime()),
            ],
            'Type'                   => $info->getType(),
        ];
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
        $aFiles   = null;
        $finder   = new \Symfony\Component\Finder\Finder();
        $iterator = $finder
                ->files()
                ->sortByName()
                ->in($pathAnalised);
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
        $crtTime = gettimeofday();
        switch ($returnType) {
            case 'array':
                $sReturn = [
                    'float'  => ($crtTime['sec'] + $crtTime['usec'] / pow(10, 6)),
                    'string' => implode('', [
                        '<span style="color:black!important;font-weight:bold;">[',
                        date('Y-m-d H:i:s.', $crtTime['sec']),
                        substr(round($crtTime['usec'], -3), 0, 3),
                        ']</span> '
                    ]),
                ];
                break;
            case 'float':
                $sReturn = ($crtTime['sec'] + $crtTime['usec'] / pow(10, 6));
                break;
            case 'string':
                $sReturn = implode('', [
                    '<span style="color:black!important;font-weight:bold;">[',
                    date('Y-m-d H:i:s.', $crtTime['sec']),
                    substr(round($crtTime['usec'], -3), 0, 3),
                    ']</span> '
                ]);
                break;
            default:
                $sReturn = sprintf($this->lclMsgCmn('i18n_Error_UnknownReturnType'), $returnType);
                break;
        }
        return $sReturn;
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
        } else {
            return $this->lclMsgCmn('i18n_Error_GivenInputIsNotJson');
        }
    }

    /**
     * Moves files into another folder
     *
     * @param type $sourcePath
     * @param type $targetPath
     * @param type $overwrite
     * @return type
     */
    protected function moveFilesIntoTargetFolder($sourcePath, $targetPath)
    {
        $filesystem = new \Symfony\Component\Filesystem\Filesystem();
        $filesystem->mirror($sourcePath, $targetPath);
        $finder     = new \Symfony\Component\Finder\Finder();
        $iterator   = $finder
                ->files()
                ->ignoreUnreadableDirs(true)
                ->followLinks()
                ->in($sourcePath);
        $sFiles     = [];
        foreach ($iterator as $file) {
            $relativePathFile = str_replace($sourcePath, '', $file->getRealPath());
            if (!file_exists($targetPath . $relativePathFile)) {
                $sFiles[$relativePathFile] = $targetPath . $relativePathFile;
            }
        }
        return $this->setArrayToJson($sFiles);
    }

    /**
     * Remove files older than given rule
     * (both Access time and Modified time will be checked
     * and only if both matches removal will take place)
     *
     * @param array $inputArray
     * @return string
     */
    protected function removeFilesOlderThanGivenRule($inputArray)
    {
        if (is_array($inputArray)) {
            if (!isset($inputArray['path'])) {
                $proceedWithDeletion = false;
                $error               = '`path` has not been provided';
            } elseif (!isset($inputArray['dateRule'])) {
                $proceedWithDeletion = false;
                $error               = '`dateRule` has not been provided';
            } else {
                $proceedWithDeletion = true;
            }
        } else {
            $proceedWithDeletion = false;
        }
        if ($proceedWithDeletion) {
            $finder   = new \Symfony\Component\Finder\Finder();
            $iterator = $finder
                    ->files()
                    ->ignoreUnreadableDirs(true)
                    ->followLinks()
                    ->in($inputArray['path']);
            $aFiles   = null;
            foreach ($iterator as $file) {
                if ($file->getATime() < strtotime($inputArray['dateRule'])) {
                    $aFiles[] = $file->getRealPath();
                }
            }
            if (is_null($aFiles)) {
                return null;
            } else {
                $filesystem = new \Symfony\Component\Filesystem\Filesystem();
                $filesystem->remove($aFiles);
                return $this->setArrayToJson($aFiles);
            }
        }
        return $error;
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
        try {
            $postingUrl = filter_var($urlToSendTo, FILTER_VALIDATE_URL);
            if ($postingUrl === false) {
                throw new \Exception($exc);
            } else {
                if (is_array($params)) {
                    $postingString   = $this->setArrayToStringForUrl('&', $params);
                    $postingUrlParts = parse_url($postingUrl);
                    $postingPort     = (isset($postingUrlParts['port']) ? $postingUrlParts['port'] : 80);
                    $flPointer       = fsockopen($postingUrlParts['host'], $postingPort, $errNo, $errorMessage, 30);
                    if ($flPointer === false) {
                        throw new \UnexpectedValueException($this->lclMsgCmn('i18n_Error_FailedToConnect') . ': '
                        . $errNo . ' (' . $errorMessage . ')');
                    } else {
                        $out[] = 'POST ' . $postingUrlParts['path'] . ' ' . $_SERVER['SERVER_PROTOCOL'];
                        $out[] = 'Host: ' . $postingUrlParts['host'];
                        if (isset($_SERVER['HTTP_USER_AGENT'])) {
                            $out[] = 'User-Agent: ' . filter_var($_SERVER['HTTP_USER_AGENT'], FILTER_SANITIZE_STRING);
                        }
                        $out[] = 'Content-Type: application/x-www-form-urlencoded';
                        $out[] = 'Content-Length: ' . strlen($postingString);
                        $out[] = 'Connection: Close' . "\r\n";
                        $out[] = $postingString;
                        fwrite($flPointer, implode("\r\n", $out));
                        fclose($flPointer);
                    }
                } else {
                    throw new \UnexpectedValueException($this->lclMsgCmn('i18n_Error_GivenParameterIsNotAnArray'));
                }
            }
        } catch (\Exception $exc) {
            echo '<pre style="color:#f00">' . $exc->getTraceAsString() . '</pre>';
        }
    }

    /**
     * Converts an array into JSON string
     *
     * @param array $inArray
     * @return string
     */
    protected function setArrayToJson(array $inArray)
    {
        if (!is_array($inArray)) {
            return $this->lclMsgCmn('i18n_Error_GivenInputIsNotArray');
        }
        $rtrn      = utf8_encode(json_encode($inArray, JSON_FORCE_OBJECT | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        $jsonError = $this->setJsonErrorInPlainEnglish();
        if (is_null($jsonError)) {
            return $rtrn;
        } else {
            return $jsonError;
        }
    }

    /**
     * Replace space with break line for each key element
     *
     * @param array $aElements
     * @return array
     */
    protected function setArrayToArrayKbr(array $aElements)
    {
        $aReturn = [];
        foreach ($aElements as $key => $value) {
            $aReturn[str_replace(' ', '<br/>', $key)] = $value;
        }
        return $aReturn;
    }

    /**
     * Converts a single-child array into an parent-child one
     *
     * @param type $inArray
     * @return type
     */
    protected function setArrayValuesAsKey(array $inArray)
    {
        $outArray = array_combine($inArray, $inArray);
        ksort($outArray);
        return $outArray;
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
        // prevent infinite result AND division by 0
        if (($fAbove == 0) || ($fBelow == 0)) {
            $nReturn = 0;
        } else {
            if (is_array($mArguments)) {
                $nReturn = $this->setNumberFormat(($fAbove / $fBelow), [
                    'MinFractionDigits' => $mArguments[1],
                    'MaxFractionDigits' => $mArguments[1],
                ]);
            } else {
                $nReturn = $this->setNumberFormat(round(($fAbove / $fBelow), $mArguments));
            }
        }
        return $nReturn;
    }

    /**
     * Provides a list of all known JSON errors and their description
     *
     * @return type
     */
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
        $sReturn      = null;
        if (in_array($currentError, $knownErrors)) {
            $sReturn = $knownErrors[$currentError];
        }
        return $sReturn;
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
            return [
                'error' => $this->lclMsgCmn('i18n_Error_GivenInputIsNotJson')
            ];
        }
        $sReturn   = (json_decode($inputJson, true));
        $jsonError = $this->setJsonErrorInPlainEnglish();
        if (is_null($jsonError)) {
            return $sReturn;
        } else {
            return [
                'error' => $jsonError
            ];
        }
    }
}
