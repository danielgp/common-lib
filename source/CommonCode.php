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
        DomComponentsByDanielGP,
        DomComponentsByDanielGPwithCDN,
        MySQLiByDanielGPqueries,
        MySQLiByDanielGP,
        NetworkComponentsByDanielGP,
        BrowserAgentInfosByDanielGP,
        RomanianHolidays;

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
                $error[]             = '`path` has not been provided';
            } elseif (!isset($inputArray['dateRule'])) {
                $proceedWithDeletion = false;
                $error[]             = '`dateRule` has not been provided';
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
            foreach ($iterator as $file) {
                if ($file->getATime() < strtotime($inputArray['dateRule'])) {
                    if ($file->getMTime() < strtotime($inputArray['dateRule'])) {
                        $aFiles[] = $file->getRealPath();
                    }
                }
            }
            $filesystem = new \Symfony\Component\Filesystem\Filesystem();
            $filesystem->remove($aFiles);
            return $this->setArrayToJson($aFiles);
        } else {
            return $error;
        }
    }

    /**
     * Generate an Excel file from a given array
     *
     * @param array $inFeatures
     */
    protected function setArrayToExcel($inFeatures)
    {
        if (is_array($inFeatures)) {
            if (isset($inFeatures['filename'])) {
                if (is_string($inFeatures['filename'])) {
                    $inFeatures['filename'] = filter_var($inFeatures['filename'], FILTER_SANITIZE_STRING);
                } else {
                    return 'Provided filename is not a string!';
                }
            } else {
                return 'No filename provided';
            }
            if (!isset($inFeatures['worksheetname'])) {
                $inFeatures['worksheetname'] = 'Worksheet1';
            }
            if (!is_array($inFeatures['contentArray'])) {
                return 'No content!';
            }
        } else {
            return 'Missing parameters!';
        }
        $xlFileName  = str_replace('.xls', '', $inFeatures['filename']) . '.xlsx';
        // Create an instance
        $objPHPExcel = new \PHPExcel();
        // Set properties
        if (isset($inFeatures['properties'])) {
            if (isset($inFeatures['properties']['Creator'])) {
                $objPHPExcel->getProperties()->setCreator($inFeatures['properties']['Creator']);
            }
            if (isset($inFeatures['properties']['LastModifiedBy'])) {
                $objPHPExcel->getProperties()->setLastModifiedBy($inFeatures['properties']['LastModifiedBy']);
            }
            if (isset($inFeatures['properties']['description'])) {
                $objPHPExcel->getProperties()->setDescription($inFeatures['properties']['description']);
            }
            if (isset($inFeatures['properties']['subject'])) {
                $objPHPExcel->getProperties()->setSubject($inFeatures['properties']['subject']);
            }
            if (isset($inFeatures['properties']['title'])) {
                $objPHPExcel->getProperties()->setTitle($inFeatures['properties']['title']);
            }
        }
        // Add a worksheet to the file, returning an object to add data to
        $objPHPExcel->setActiveSheetIndex(0);
        if (is_array($inFeatures['contentArray'])) {
            $counter = 0;
            foreach ($inFeatures['contentArray'] as $key => $value) {
                $columnCounter = 0;
                if ($counter == 0) { // headers
                    foreach ($value as $key2 => $value2) {
                        $crCol          = $this->setArrayToExcelStringFromColumnIndex($columnCounter);
                        $objPHPExcel->getActiveSheet()->getColumnDimension($crCol)->setAutoSize(true);
                        $crtCellAddress = $crCol . '1';
                        $objPHPExcel->getActiveSheet()->SetCellValue($crtCellAddress, $key2);
                        $objPHPExcel->getActiveSheet()->getStyle($crCol . '1')->getFill()->applyFromArray([
                            'type'       => 'solid',
                            'startcolor' => ['rgb' => 'CCCCCC'],
                            'endcolor'   => ['rgb' => 'CCCCCC'],
                        ]);
                        $objPHPExcel->getActiveSheet()->getStyle($crCol . '1')->applyFromArray([
                            'font' => [
                                'bold'  => true,
                                'color' => ['rgb' => '000000'],
                            ]
                        ]);
                        $columnCounter += 1;
                    }
                    $objPHPExcel->getActiveSheet()->calculateColumnWidths();
                    $counter += 1;
                }
                $columnCounter = 0;
                foreach ($value as $key2 => $value2) {
                    if (strlen($value2) > 50) {
                        $objPHPExcel->getActiveSheet()->getStyle($crtCellAddress)->getAlignment()->setWrapText(true);
                    }
                    if ($counter == 1) {
                        $objPHPExcel->getActiveSheet()->getColumnDimension($crCol)->setAutoSize(false);
                    }
                    $crCol          = $this->setArrayToExcelStringFromColumnIndex($columnCounter);
                    $crtCellAddress = $crCol . ($counter + 1);
                    if (($value2 == '') || ($value2 == '00:00:00') || ($value2 == '0')) {
                        $value2 = '';
                    }
                    if ((strlen($value2) == 8) && (strpos($value2, ':') !== false)) {
                        if ($value2 == '') {
                            $calculated_time_as_number = 0;
                        } else {
                            $calculated_time_as_number = $this->LocalTime2Seconds($value2) / 60 / 60 / 24;
                        }
                        $objPHPExcel->getActiveSheet()->SetCellValue($crtCellAddress, $calculated_time_as_number);
                        $objPHPExcel
                                ->getActiveSheet()
                                ->getStyle($crtCellAddress)
                                ->getNumberFormat()
                                ->setFormatCode('[h]:mm:ss;@');
                    } else {
                        $objPHPExcel->getActiveSheet()->SetCellValue($crtCellAddress, strip_tags($value2));
                    }
                    $columnCounter += 1;
                }
                $counter += 1;
            }
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle($inFeatures['worksheetname']);
            $objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation('portrait');
            //coresponding to A4
            $objPHPExcel->getActiveSheet()->getPageSetup()->setPaperSize(9);
            // freeze 1st top row
            $objPHPExcel->getActiveSheet()->freezePane('A2');
            // activate AutoFilter
            $objPHPExcel->getActiveSheet()->setAutoFilter('A1:' . $crCol . ($counter - 1));
            // margin is set in inches (0.7cm)
            $margin = 0.7 / 2.54;
            $objPHPExcel->getActiveSheet()->getPageMargins()->setHeader($margin);
            $objPHPExcel->getActiveSheet()->getPageMargins()->setTop($margin * 2);
            $objPHPExcel->getActiveSheet()->getPageMargins()->setBottom($margin);
            $objPHPExcel->getActiveSheet()->getPageMargins()->setLeft($margin);
            $objPHPExcel->getActiveSheet()->getPageMargins()->setRight($margin);
            // add header content
            $objPHPExcel->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&F&RPage &P / &N');
            // repeat coloumn headings for every new page...
            $objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
            // activate printing of gridlines
            $objPHPExcel->getActiveSheet()->setPrintGridlines(true);
            if (!in_array(PHP_SAPI, ['cli', 'cli-server'])) {
                // output the created content to the browser
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Pragma: private');
                header('Cache-control: private, must-revalidate');
                header('Content-Disposition: attachment;filename="' . $xlFileName . '"');
                header('Cache-Control: max-age=0');
            }
            $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
            if (in_array(PHP_SAPI, ['cli', 'cli-server'])) {
                $objWriter->save($xlFileName);
            } else {
                $objWriter->save('php://output');
            }
            unset($objPHPExcel);
        }
    }

    /**
     * Using a lookup cache adds a slight memory overhead,
     * but boosts speed caching using a static within the method is faster than a class static,
     * though it's additional memory overhead
     *
     * @staticvar array $_indexCache
     * @param type $pColumnIndex
     * @return string
     */
    private static function setArrayToExcelStringFromColumnIndex($pColumnIndex = 0)
    {
        static $_indexCache = [];
        if (!isset($_indexCache[$pColumnIndex])) {
            // Determine column string
            if ($pColumnIndex < 26) {
                // 26 is the # of column of 1 single letter
                $_indexCache[$pColumnIndex] = chr(65 + $pColumnIndex);
            } elseif ($pColumnIndex < 702) {
                // 702 is the # of columns with 2 letters
                $_indexCache[$pColumnIndex] = chr(64 + ($pColumnIndex / 26)) .
                        chr(65 + $pColumnIndex % 26);
            } else {
                // anything above 702 has 3 letters combination
                $_indexCache[$pColumnIndex] = chr(64 + (($pColumnIndex - 26) / 676)) .
                        chr(65 + ((($pColumnIndex - 26) % 676) / 26)) .
                        chr(65 + $pColumnIndex % 26);
            }
        }
        return $_indexCache[$pColumnIndex];
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
        if (in_array($currentError, $knownErrors)) {
            $sReturn = $knownErrors[$currentError];
        } else {
            $sReturn = null;
        }
        return $sReturn;
    }
}
