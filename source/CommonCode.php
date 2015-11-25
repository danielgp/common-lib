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
        \danielgp\browser_agent_info\BrowserAgentInfosByDanielGP,
        \danielgp\network_components\NetworkComponentsByDanielGP,
        DomComponentsByDanielGP,
        DomComponentsByDanielGPwithCDN,
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

    protected function cleanStringForId($givenString)
    {
        return preg_replace("/[^a-zA-Z0-9]/", ucwords($givenString));
    }

    /**
     * Returns an array with meaningfull content of permissions
     *
     * @param int $permissionNumber
     * @return array
     */
    protected function explainPermissions($permissionNumber)
    {
        if (($permissionNumber & 0xC000) == 0xC000) {
            $firstFlag = [
                'code' => 's',
                'name' => 'Socket',
            ];
        } elseif (($permissionNumber & 0xA000) == 0xA000) {
            $firstFlag = [
                'code' => 'l',
                'name' => 'Symbolic Link',
            ];
        } elseif (($permissionNumber & 0x8000) == 0x8000) {
            $firstFlag = [
                'code' => '-',
                'name' => 'Regular',
            ];
        } elseif (($permissionNumber & 0x6000) == 0x6000) {
            $firstFlag = [
                'code' => 'b',
                'name' => 'Block special',
            ];
        } elseif (($permissionNumber & 0x4000) == 0x4000) {
            $firstFlag = [
                'code' => 'd',
                'name' => 'Directory',
            ];
        } elseif (($permissionNumber & 0x2000) == 0x2000) {
            $firstFlag = [
                'code' => 'c',
                'name' => 'Character special',
            ];
        } elseif (($permissionNumber & 0x1000) == 0x1000) {
            $firstFlag = [
                'code' => 'p',
                'name' => 'FIFO pipe',
            ];
        } else {
            $firstFlag = [
                'code' => 'u',
                'name' => 'FIFO pipe',
            ];
        }
        $permissionsString    = substr(sprintf('%o', $permissionNumber), -4);
        $numericalPermissions = [
            0 => [
                'code' => '---',
                'name' => 'none',
            ],
            1 => [
                'code' => '--x',
                'name' => 'execute only',
            ],
            2 => [
                'code' => '-w-',
                'name' => 'write only',
            ],
            3 => [
                'code' => '-wx',
                'name' => 'write and execute',
            ],
            4 => [
                'code' => 'r--',
                'name' => 'read only',
            ],
            5 => [
                'code' => 'r-x',
                'name' => 'read and execute',
            ],
            6 => [
                'code' => 'rw-',
                'name' => 'read and write',
            ],
            7 => [
                'code' => 'rwx',
                'name' => 'read, write and execute',
            ],
        ];
        return [
            'Code'        => $permissionsString,
            'Overall'     => implode('', [
                $firstFlag['code'],
                $numericalPermissions[substr($permissionsString, 1, 1)]['code'],
                $numericalPermissions[substr($permissionsString, 2, 1)]['code'],
                $numericalPermissions[substr($permissionsString, 3, 1)]['code'],
            ]),
            'First'       => $firstFlag,
            'Owner'       => $numericalPermissions[substr($permissionsString, 1, 1)],
            'Group'       => $numericalPermissions[substr($permissionsString, 2, 1)],
            'World/Other' => $numericalPermissions[substr($permissionsString, 3, 1)],
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
            $aReturn['info']     = $this->lclMsgCmn('i18n_Error_ExtensionNotLoaded');
            $aReturn['response'] = '';
        }
        if (!filter_var($fullURL, FILTER_VALIDATE_URL)) {
            $aReturn['info']     = $this->lclMsgCmn('i18n_Error_GivenUrlIsNotValid');
            $aReturn['response'] = '';
        }
        $aReturn = [];
        $ch      = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, $this->getUserAgentByCommonLib());
        if ((strpos($fullURL, 'https') !== false) || (isset($features['forceSSLverification']))) {
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
            $aReturn['info']     = $this->setArrayToJson([
                '#'           => curl_errno($ch),
                'description' => curl_error($ch)
            ]);
            $aReturn['response'] = '';
        } else {
            $aReturn['info']     = $this->setArrayToJson(curl_getinfo($ch));
            $aReturn['response'] = $responseJsonFromClientOriginal;
        }
        curl_close($ch);
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
            $ar      = $this->mySQLconnection->affected_rows;
            $message = sprintf($this->lclMsgCmnNumber('i18n_Record', 'i18n_Records', $ar), $ar);
        }
        return '<div>'
                . $message
                . '</div>';
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
            return [
                'error' => sprintf($this->lclMsgCmn('i18n_Error_GivenFileDoesNotExist'), $fileGiven)
            ];
        }
        $info    = new \SplFileInfo($fileGiven);
        $sReturn = [
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
            'File Permissions'       => array_merge([
                'Permissions' => $info->getPerms(),
                    ], $this->explainPermissions($info->getPerms())),
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
        return $sReturn;
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
            $aFiles = [
                'error' => sprintf($this->lclMsgCmn('i18n_Error_GivenPathIsNotValid'), $pathAnalised)
            ];
        } elseif (!is_dir($pathAnalised)) {
            $aFiles = [
                'error' => $this->lclMsgCmn('i18n_Error_GivenPathIsNotFolder')
            ];
        } else {
            $finder   = new \Symfony\Component\Finder\Finder();
            $iterator = $finder
                    ->files()
                    ->sortByName()
                    ->in($pathAnalised);
            foreach ($iterator as $file) {
                $aFiles[$file->getRealPath()] = $this->getFileDetails($file);
            }
        }
        return $aFiles;
    }

    /**
     * Returns a complete list of packages and respective details from a composer.lock file
     *
     * @param string $fileToRead
     * @return array
     */
    protected function getPackageDetailsFromGivenComposerLockFile($fileToRead)
    {
        if (!file_exists($fileToRead)) {
            return [
                'error' => $fileToRead . ' was not found'
            ];
        }
        $dateTimeToday    = new \DateTime(date('Y-m-d', strtotime('today')));
        $defaultNA        = '---';
        $finalInformation = [];
        $handle           = fopen($fileToRead, 'r');
        $fileContents     = fread($handle, filesize($fileToRead));
        fclose($handle);
        $packages         = $this->setJsonToArray($fileContents);
        foreach ($packages['packages'] as $value) {
            if (isset($value['time'])) {
                $dateTime = new \DateTime(date('Y-m-d', strtotime($value['time'])));
                $interval = $dateTimeToday->diff($dateTime);
            }
            if (isset($value['version'])) {
                if (substr($value['version'], 0, 1) == 'v') {
                    $v = substr($value['version'], 1, strlen($value['version']) - 1);
                } else {
                    $v = $value['version'];
                }
                if (strpos($v, '-') !== false) {
                    $v = substr($v, 0, strpos($v, '-'));
                }
            }
            if (isset($value['license'])) {
                if (is_array($value['license'])) {
                    $l = implode(', ', $value['license']);
                } else {
                    $l = $value['license'];
                }
            } else {
                $l = $defaultNA;
            }
            $finalInformation[$value['name']] = [
                'Aging'            => (isset($value['time']) ? $interval->format('%a days ago') : $defaultNA),
                'Description'      => (isset($value['description']) ? $value['description'] : $defaultNA),
                'Homepage'         => (isset($value['homepage']) ? $value['homepage'] : $defaultNA),
                'License'          => $l,
                'Notification URL' => (isset($value['version']) ? $value['notification-url'] : $defaultNA),
                'Package Name'     => $value['name'],
                'PHP required'     => (isset($value['require']['php']) ? $value['require']['php'] : $defaultNA),
                'Product'          => explode('/', $value['name'])[1],
                'Type'             => (isset($value['type']) ? $value['type'] : $defaultNA),
                'Time'             => (isset($value['time']) ? date('l, d F Y H:i:s', strtotime($value['time'])) : ''),
                'Time as PHP no.'  => (isset($value['time']) ? strtotime($value['time']) : ''),
                'URL'              => (isset($value['url']) ? $value['url'] : $defaultNA),
                'Vendor'           => explode('/', $value['name'])[0],
                'Version'          => (isset($value['version']) ? $value['version'] : $defaultNA),
                'Version no.'      => (isset($value['version']) ? $v : $defaultNA),
            ];
        }
        asort($finalInformation);
        ksort($finalInformation);
        return $finalInformation;
    }

    /**
     * Returns server Timestamp into various formats
     *
     * @param string $returnType
     * @return string
     */
    protected function getTimestamp($returnType = 'string')
    {
        $dt = gettimeofday();
        switch ($returnType) {
            case 'array':
                $sReturn = [
                    'float'  => ($dt['sec'] + $dt['usec'] / pow(10, 6)),
                    'string' => implode('', [
                        '<span style="color:black!important;font-weight:bold;">[',
                        date('Y-m-d H:i:s.', $dt['sec']),
                        substr(round($dt['usec'], -3), 0, 3),
                        ']</span> '
                    ]),
                ];
                break;
            case 'float':
                $sReturn = ($dt['sec'] + $dt['usec'] / pow(10, 6));
                break;
            case 'string':
                $sReturn = implode('', [
                    '<span style="color:black!important;font-weight:bold;">[',
                    date('Y-m-d H:i:s.', $dt['sec']),
                    substr(round($dt['usec'], -3), 0, 3),
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
        } else {
            return $error;
        }
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
                throw new \Exception($ex);
            } else {
                if (is_array($params)) {
                    $postingString   = $this->setArrayToStringForUrl('&', $params);
                    $postingUrlParts = parse_url($postingUrl);
                    $postingPort     = (isset($postingUrlParts['port']) ? $postingUrlParts['port'] : 80);
                    $fp              = fsockopen($postingUrlParts['host'], $postingPort, $errNo, $errorMessage, 30);
                    if ($fp === false) {
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
                        fwrite($fp, implode("\r\n", $out));
                        fclose($fp);
                    }
                } else {
                    throw new \UnexpectedValueException($this->lclMsgCmn('i18n_Error_GivenParameterIsNotAnArray'));
                }
            }
        } catch (\Exception $ex) {
            echo '<pre style="color:#f00">' . $ex->getTraceAsString() . '</pre>';
        }
    }

    /**
     * Converts an array into JSON string
     *
     * @param array $inArray
     * @return string
     */
    protected function setArrayToJson($inArray)
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
    protected function setArrayToArrayKbr($aElements)
    {
        foreach ($aElements as $key => $value) {
            $aReturn[str_replace(' ', '<br/>', $key)] = $value;
        }
        return $aReturn;
    }

    /**
     * Returns a table from an query
     *
     * @param array $gArray
     * @param array $features
     * @param boolean $bKeepFullPage
     * @return string
     */
    protected function setArrayToTable($aElements, $ftrs = null, $bKpFlPge = true)
    {
        if (isset($ftrs['limits'])) {
            $ftrs['limits'][1] = min($ftrs['limits'][1], $ftrs['limits'][2]);
            if ($ftrs['limits'][2] > $ftrs['limits'][1]) {
                $iStartingPageRecord = 1;
            }
        }
        $rows = count($aElements);
        if ($rows == 0) {
            return $this->setFeedbackModern('error', 'Error', $this->lclMsgCmn('i18n_NoData'));
        }
        $sReturn = '';
        if (isset($ftrs['hidden_columns'])) {
            $hdClmns = $this->setArrayValuesAsKey($ftrs['hidden_columns']);
        } else {
            $hdClmns = [''];
        }
        if ((isset($ftrs['actions']['checkbox_inlineEdit'])) || (isset($ftrs['actions']['checkbox']))) {
            $checkboxFormId = 'frm' . date('YmdHis');
            $sReturn .= '<form id="' . $checkboxFormId . '" ' . 'name="' . $checkboxFormId
                    . '" method="post" ' . ' action="' . $_SERVER['PHP_SELF'] . '" >';
        }
        $tbl['Def'] = '<table'
                . (isset($ftrs['table_style']) ? ' style="' . $ftrs['table_style'] . '"' : '')
                . (isset($ftrs['table_class']) ? ' class="' . $ftrs['table_class'] . '"' : '')
                . '>';
        if (!isset($ftrs['grouping_cell_type'])) {
            $ftrs['grouping_cell_type'] = 'row';
        }
        switch ($ftrs['grouping_cell_type']) {
            case 'row':
                $sReturn .= $tbl['Def'];
                break;
            case 'tab':
                if (!isset($ftrs['noGlobalTab'])) {
                    $sReturn .= '<div class="tabber" id="tab">';
                }
                break;
        }
        $iTableColumns         = 0;
        $remebered_value       = -1;
        $rememberGroupingValue = null;
        $color_no              = null;
        if (!isset($ftrs['headers_breaked'])) {
            $ftrs['headers_breaked'] = true;
        }
        for ($rCntr = 0; $rCntr < $rows; $rCntr++) {
            if ($rCntr == 0) {
                $header        = array_diff_key($aElements[$rCntr], $hdClmns);
                $iTableColumns = count($header);
                if (isset($ftrs['computed_columns'])) {
                    $iTableColumns += count($ftrs['computed_columns']);
                }
                if (isset($ftrs['actions'])) {
                    $iTableColumns += 1;
                }
                if (isset($ftrs['grouping_cell'])) {
                    $iTableColumns -= 1;
                }
                $tbl['Head'] = '<thead>';
                if ($ftrs['grouping_cell_type'] == 'row') {
                    $sReturn .= $tbl['Head'];
                }
                if (isset($iStartingPageRecord)) {
                    $pg = $this->setPagination($ftrs['limits'][0], $ftrs['limits'][1], $ftrs['limits'][2], $bKpFlPge);
                    $sReturn .= $this->setStringIntoTag($this->setStringIntoTag($pg, 'th', [
                                'colspan' => $iTableColumns
                            ]), 'tr');
                }
                $tbl['Header'] = '<tr>';
                if (isset($ftrs['grouping_cell'])) { // Grouping columns
                    $header = array_diff_key($header, [$ftrs['grouping_cell'] => '']);
                }
                if (isset($ftrs['actions'])) { // Action column
                    $tbl['Header'] .= '<th>&nbsp;</th>';
                }
                if (isset($ftrs['RowStyle'])) { //Exclude style columns from displaying
                    $tmpClmns = $this->setArrayValuesAsKey([$ftrs['RowStyle']]);
                    $header   = array_diff_key($header, $tmpClmns);
                    $hdClmns  = array_merge($hdClmns, $tmpClmns);
                    unset($tmpClmns);
                }
                $tbl['Header'] .= $this->setTableHeader($header, $ftrs['headers_breaked']); // Regular columns
                if (isset($ftrs['computed_columns'])) { // Computed columns
                    $tbl['Header'] .= $this->setTableHeader($ftrs['computed_columns'], $ftrs['headers_breaked']);
                }
                $tbl['Header'] .= '</tr></thead><tbody>';
                if ($ftrs['grouping_cell_type'] == 'row') {
                    $sReturn .= $tbl['Header'];
                }
            }
            $row_current = array_diff_key($aElements[$rCntr], $hdClmns);
            if (isset($ftrs['row_colored_alternated'])) {
                if ($ftrs['row_colored_alternated'][0] == '#') {
                    $color_column_value = $rCntr;
                } else {
                    $color_column_value = $row_current[$ftrs['row_colored_alternated'][0]];
                }
                if ($remebered_value != $color_column_value) {
                    if (isset($color_no)) {
                        $color_no = 1;
                    } else {
                        $color_no = 2;
                    }
                    $remebered_value = $color_column_value;
                }
                $color = ' style="background-color: ' . $ftrs['row_colored_alternated'][$color_no] . ';"';
            } else {
                if (isset($ftrs['RowStyle'])) {
                    $color = ' style="' . $aElements[$rCntr][$ftrs['RowStyle']] . '"';
                } else {
                    $color = '';
                }
            }
            $tbl['tr_Color'] = '<tr' . $color . '>';
// Grouping column
            if (isset($ftrs['grouping_cell'])) {
                foreach ($aElements[$rCntr] as $key => $value) {
                    if (($ftrs['grouping_cell'] == $key) && ($rememberGroupingValue != $value)) {
                        switch ($ftrs['grouping_cell_type']) {
                            case 'row':
                                $sReturn .= $tbl['tr_Color'] . '<td ' . 'colspan="' . $iTableColumns . '">'
                                        . $this->setStringIntoTag($value, 'div', ['class' => 'rowGroup rounded'])
                                        . '</td></tr>';
                                break;
                            case 'tab':
                                if (is_null($rememberGroupingValue)) {
                                    if (isset($ftrs['showGroupingCounter'])) {
                                        $groupCounter = 0;
                                    }
                                } else {
                                    $sReturn .= '</tbody></table>';
                                    if (isset($ftrs['showGroupingCounter'])) {
                                        $sReturn .= $this->updateDivTitleName($rememberGroupingValue, $groupCounter);
                                        $groupCounter = 0;
                                    }
                                    $sReturn .= '</div>';
                                }
                                $sReturn .= '<div class="tabbertab';
                                if (isset($ftrs['grouping_default_tab'])) {
                                    $sReturn .= ($ftrs['grouping_default_tab'] == $value ? ' tabbertabdefault' : '');
                                }
                                $sReturn .= '" id="tab_' . $this->cleanStringForId($value) . '" '
                                        . 'title="' . $value . '">'
                                        . $tbl['Def'] . $tbl['Head'] . $tbl['Header'];
                                break;
                        }
                        $rememberGroupingValue = $value;
                    }
                }
            }
            if (isset($ftrs['grouping_cell'])) {
                if ($ftrs['grouping_cell_type'] == 'tab') {
                    if (isset($ftrs['showGroupingCounter'])) {
                        $groupCounter++;
                    }
                }
            }
            $sReturn .= $tbl['tr_Color'];
// Action column
            if (isset($ftrs['actions'])) {
                $sReturn .= '<td style="white-space:nowrap;">';
                $action_argument = 0;
                if (isset($ftrs['actions']['key'])) {
                    $action_key = $ftrs['actions']['key'];
                } else {
                    $action_key = 'view';
                }
                if (isset($ftrs['action_prefix'])) {
                    $actPrfx    = $ftrs['action_prefix'] . '&amp;';
                    $action_key = 'view2';
                } else {
                    $actPrfx = '';
                }
                foreach ($ftrs['actions'] as $key => $value) {
                    if ($action_argument != 0) {
                        $sReturn .= '&nbsp;';
                    }
                    switch ($key) {
                        case 'checkbox':
                            $checkboxName  = $value . '[]';
                            $checkboxNameS = $value;
                            $sReturn .= '&nbsp;<input type="checkbox" name="' . $checkboxName
                                    . '" id="n' . $aElements[$rCntr][$value]
                                    . '" value="' . $aElements[$rCntr][$value] . '" ';
                            if (isset($_REQUEST[$checkboxNameS])) {
                                if (is_array($_REQUEST[$checkboxNameS])) {
                                    if (in_array($aElements[$rCntr][$value], $_REQUEST[$checkboxNameS])) {
                                        $sReturn .= 'checked="checked" ';
                                    }
                                } else {
                                    if ($aElements[$rCntr][$value] == $_REQUEST[$checkboxNameS]) {
                                        $sReturn .= 'checked="checked" ';
                                    }
                                }
                            }
                            if (strpos($_REQUEST['view'], 'multiEdit') !== false) {
                                $sReturn .= 'disabled="disabled" ';
                            }
                            $sReturn .= '/>';
                            break;
                        case 'checkbox_inlineEdit':
                            $checkboxName  = $value . '[]';
                            $checkboxNameS = $value;
                            $sReturn .= '&nbsp;<input type="checkbox" name="' . $checkboxName
                                    . '" id="n' . $aElements[$rCntr][$value] . '" value="'
                                    . $aElements[$rCntr][$value] . '"/>';
                            break;
                        case 'edit':
                            $edt           = '';
                            if (isset($ftrs['NoAjaxEditing'])) {
                                $edt .= $_SERVER['PHP_SELF'] . '?' . $actPrfx
                                        . $action_key . '=' . $value[0] . '&amp;';
                                $iActArgs = count($value[1]);
                                for ($cntr2 = 0; $cntr2 < $iActArgs; $cntr2++) {
                                    $edt .= $value[1][$cntr2] . '=' . $aElements[$rCntr][$value[1][$cntr2]];
                                }
                                $sReturn .= '<a href="' . $edt . '"><i class="fa fa-pencil">&nbsp;</i></a>';
                            } else {
                                $edt .= 'javascript:loadAE(\'' . $_SERVER['PHP_SELF'] . '?'
                                        . $actPrfx . $action_key . '=' . $value[0] . '&amp;';
                                $iActArgs = count($value[1]);
                                for ($cntr2 = 0; $cntr2 < $iActArgs; $cntr2++) {
                                    $edt .= $value[1][$cntr2] . '=' . $aElements[$rCntr][$value[1][$cntr2]];
                                }
                                $edt .= '\');';
                                $sReturn .= '<a href="#" onclick="' . $edt . '">'
                                        . '<i class="fa fa-pencil">&nbsp;</i></a>';
                            }
                            break;
                        case 'list2':
                            $sReturn .= '<a href="?' . $actPrfx . $action_key . '=' . $value[0] . '&amp;';
                            $iActArgs = count($value[1]);
                            for ($cntr2 = 0; $cntr2 < $iActArgs; $cntr2++) {
                                $sReturn .= $value[1][$cntr2] . '=' . $aElements[$rCntr][$value[1][$cntr2]];
                            }
                            $sReturn .= '"><i class="fa fa-list">&nbsp;</i></a>';
                            break;
                        case 'delete':
                            $sReturn .= '<a href="javascript:setQuest(\'' . $value[0] . '\',\'';
                            $iActArgs = count($value[1]);
                            for ($cntr2 = 0; $cntr2 < $iActArgs; $cntr2++) {
                                $sReturn .= $value[1][$cntr2] . '=' . $aElements[$rCntr][$value[1][$cntr2]];
                            }
                            $sReturn .= '\');"><i class="fa fa-times">&nbsp;</i></a>';
                            break;
                    }
                    $action_argument += 1;
                }
                $sReturn .= '</td>';
            }
// Regular columns
            $sReturn .= $this->setTableCell($row_current, $ftrs);
// Computed columns
            if (isset($ftrs['computed_columns'])) {
                foreach ($ftrs['computed_columns'] as $key => $value) {
                    if ($value[0] == '%') {
                        $dec = $value[2] + 2;
                    } else {
                        $dec = $value[2];
                    }
                    switch ($value[1]) {
                        case '/':
                            // next variable is only to avoid a long line
                            $shorter                 = [
                                $aElements[$rCntr][$value[3]],
                                $aElements[$rCntr][$value[4]],
                            ];
                            $aElements[$rCntr][$key] = $this->setDividedResult($shorter[0], $shorter[1], $dec);
                            break;
                        case '+':
                            // next variable is only to avoid a long line
                            $iTemp                   = $this->setArrayValuesAsKey([
                                $value[0],
                                $value[1],
                                $value[2]
                            ]);
                            $aTemp                   = array_diff($value, $iTemp);
                            $aElements[$rCntr][$key] = 0;
                            foreach ($aTemp as $sValue) {
                                $aElements[$rCntr][$key] += $aElements[$rCntr][$sValue];
                            }
                            break;
                        default:
                            $row_computed[$key] = '';
                            break;
                    }
                    if ($value[0] == '%') {
                        $row_computed[$key] = ($aElements[$rCntr][$key] * 100);
                        $dec -= 2;
                    } else {
                        $row_computed[$key] = $aElements[$rCntr][$key];
                    }
                    $decimals[$key] = $dec;
                }
// displaying them
                $sReturn .= $this->setTableCell($row_computed, ['decimals' => $decimals]);
            }
            $sReturn .= '</tr>';
        }
        if (isset($iStartingPageRecord)) {
            $pg = $this->setPagination($ftrs['limits'][0], $ftrs['limits'][1], $ftrs['limits'][2]);
            $sReturn .= '<tr>' . $this->setStringIntoTag($pg, 'th', ['colspan' => $iTableColumns]) . '</tr>';
        }
        $sReturn .= '</tbody></table>';
        if ($ftrs['grouping_cell_type'] == 'tab') {
            if (isset($ftrs['showGroupingCounter'])) {
                $sReturn .= $this->updateDivTitleName($rememberGroupingValue, $groupCounter);
            }
            $sReturn .= '</div><!-- from ' . $rememberGroupingValue . ' -->';
            if (!isset($ftrs['noGlobalTab'])) {
                $sReturn .= '</div><!-- from global tab -->';
            }
        }
        if (isset($ftrs['actions']['checkbox'])) {
            if (strpos($_REQUEST['view'], 'multiEdit') === false) {
                $sReturn .= '<a href="#" onclick="javascript:checking(\'' . $checkboxFormId
                        . '\',\'' . $checkboxName . '\',true);">Check All</a>&nbsp;&nbsp;'
                        . '<a href="#" onclick="javascript:checking(\'' . $checkboxFormId
                        . '\',\'' . $checkboxName . '\',false);">Uncheck All</a>&nbsp;&nbsp;'
                        . '<input type="hidden" name="action" value="multiEdit_' . $checkboxNameS . '" />';
                if (isset($ftrs['hiddenInput'])) {
                    if (is_array($ftrs['hiddenInput'])) {
                        foreach ($ftrs['hiddenInput'] as $valueF) {
                            $sReturn .= '<input type="hidden" name="' . $valueF
                                    . '" value="' . $_REQUEST[$valueF] . '" />';
                        }
                    } else {
                        $sReturn .= '<input type="hidden" name="' . $ftrs['hiddenInput']
                                . '" value="' . $_REQUEST[$ftrs['hiddenInput']] . '" />';
                    }
                }
                $sReturn .= '<input style="margin: 0 3em 0 3em;" type="submit" ' . 'value="Edit selected" />';
            }
            $sReturn .= '</form>';
        }
        if (isset($ftrs['actions']['checkbox_inlineEdit'])) {
            $sReturn .= '<a href="#" onclick="javascript:checking(\'' . $checkboxFormId
                    . '\',\'' . $checkboxName . '\',true);">Check All</a>&nbsp;&nbsp;'
                    . '<a href="#" onclick="javascript:checking(\'' . $checkboxFormId
                    . '\',\'' . $checkboxName . '\',false);">Uncheck All</a>&nbsp;&nbsp;';
            if (isset($ftrs['visibleInput'])) {
                $sReturn .= $ftrs['visibleInput'];
            }
            $sReturn .= '<input type="hidden" name="view" value="save_' . $checkboxNameS . '" />';
            if (isset($ftrs['hiddenInput'])) {
                if (is_array($ftrs['hiddenInput'])) {
                    foreach ($ftrs['hiddenInput'] as $valueF) {
                        $sReturn .= '<input type="hidden" name="' . $valueF
                                . '" value="' . $_REQUEST[$valueF] . '" />';
                    }
                } else {
                    $sReturn .= '<input type="hidden" name="' . $ftrs['hiddenInput']
                            . '" value="' . $_REQUEST[$ftrs['hiddenInput']] . '" />';
                }
            }
            $sReturn .= '<input style="margin: 0 3em 0 3em;" type="submit" value="Store the modification" />';
            $sReturn .= '</form>';
        }
        return $sReturn;
    }

    /**
     * Converts a single-child array into an parent-child one
     *
     * @param type $inArray
     * @return type
     */
    protected function setArrayValuesAsKey($inArray)
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

    /**
     * Generates a table cell
     *
     * @param array $aElements
     * @param array $features
     * @return string
     */
    private function setTableCell($aElements, $features = null)
    {
        $sReturn = null;
        foreach ($aElements as $key => $value) {
            $value = str_replace(['& ', '\"', "\'"], ['&amp; ', '"', "'"], $value);
            if ((isset($features['grouping_cell'])) && ($features['grouping_cell'] == $key)) {
                // just skip
            } else {
                $sReturn .= '<td ';
                if (isset($features['column_formatting'][$key])) {
                    switch ($features['column_formatting'][$key]) {
                        case '@':
                            $sReturn .= 'style="text-align:left;">' . $value;
                            break;
                        case 'right':
                            $sReturn .= 'style="text-align:right;">' . $value;
                            break;
                        default:
                            $sReturn .= '???';
                            break;
                    }
                } else {
                    if (is_numeric($value)) {
                        if (substr($value, 0, 1) === '0') {
                            $sReturn .= 'style="text-align: right;">' . $value;
                        } else {
                            $decimals = 0;
                            if (isset($features['no_of_decimals'])) {
                                $decimals = $features['no_of_decimals'];
                            }
                            if (isset($features['decimals']) && array_key_exists($key, $features['decimals'])) {
                                $decimals = $features['decimals'][$key];
                            }
                            $sReturn .= 'style="text-align: right;">';
                            $sReturn .= $this->setNumberFormat($value, [
                                'MinFractionDigits' => $decimals,
                                'MaxFractionDigits' => $decimals
                            ]);
                        }
                    } else {
                        $outputet = false;
                        if ((strpos($value, '-') !== false) && (strlen($value) == 10)) {
                            if (preg_match("([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})", $value, $regs)) {
                                $outputet = true;
                                $sReturn .= 'style="text-align:right;width: 10px;">'
                                        . $regs[3] . '.' . $regs[2] . '.' . $regs[1];
                            }
                        }
                        if (!$outputet) {
                            $sReturn .= 'style="text-align:left;">' . $value;
                        }
                    }
                }
                $sReturn .= '</td>';
            }
        }
        return $sReturn;
    }

    /**
     * Generates a table header
     *
     * @param array $aElements
     * @param boolean $bHeadersBreaked
     * @return string
     */
    private function setTableHeader($aElements, $bHeadersBreaked)
    {
        if ($bHeadersBreaked) {
            $aTableHeader = $this->setArrayToArrayKbr($aElements);
        } else {
            $aTableHeader = $aElements;
        }
        $sReturn[] = null;
        foreach (array_keys($aTableHeader) as $value) {
            $sReturn[] = $this->setStringIntoTag($value, 'th');
        }
        return implode('', $sReturn);
    }

    protected function updateDivTitleName($rememberGroupingValue, $groupCounter)
    {
        $jsContent = '$(document).ready(function() { $("#tab_'
                . $this->cleanStringForId($rememberGroupingValue) . '").attr("title", "'
                . $rememberGroupingValue . ' (' . $groupCounter . ')"); });';
        return $this->setJavascriptContent($jsContent);
    }
}
