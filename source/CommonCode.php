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

    use DomComponentsByDanielGP,
        DomComponentsByDanielGPwithCDN,
        MySQLiByDanielGPqueries,
        MySQLiByDanielGP,
        NetworkComponentsByDanielGP,
        RomanianHolidays;

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
        $aReturn = [];
        $ch      = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, $this->getUserAgent());
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
            $aReturn['info']     = [
                '#'           => curl_errno($ch),
                'description' => curl_error($ch)
            ];
            $aReturn['response'] = '';
        } else {
            $aReturn['info']     = $this->setArrayToJson(curl_getinfo($ch));
            $aReturn['response'] = $responseJsonFromClientOriginal;
        }
        curl_close($ch);
        $sReturn = '';
        if ($this->isJson($aReturn['info'])) {
            $sReturn = '"info": ' . $aReturn['info'];
        } else {
            $sReturn = '"info": {' . $aReturn['info'] . ' }';
        }
        $sReturn .= ', ';
        if ($this->isJson($aReturn['response'])) {
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
        $result = $this->setJson2array($this->getContentFromUrlThroughCurl($fullURL, $features));
        ksort($result['info']);
        if (is_array($result['response'])) {
            ksort($result['response']);
        }
        return $result;
    }

    /**
     * returns the details about Communicator (current) file
     *
     * @return array
     */
    protected function getFileDetails($fileGiven)
    {
        if (!file_exists($fileGiven)) {
            return null;
        }
        $parts   = pathinfo($fileGiven);
        $sReturn = [
            'File Extension'            => $parts['extension'],
            'File Name'                 => $parts['basename'],
            'File Name w. Extension'    => $parts['filename'],
            'File Path'                 => $parts['dirname'],
            'Name'                      => $fileGiven,
            'Size'                      => filesize($fileGiven),
            'Sha1'                      => sha1_file($fileGiven),
            'TimestampAccessed'         => fileatime($fileGiven),
            'TimestampAccessedReadable' => date('Y-m-d H:i:s', fileatime($fileGiven)),
            'TimestampChanged'          => filectime($fileGiven),
            'TimestampChangedReadable'  => date('Y-m-d H:i:s', filectime($fileGiven)),
            'TimestampModified'         => filemtime($fileGiven),
            'TimestampModifiedReadable' => date('Y-m-d H:i:s', filemtime($fileGiven)),
        ];
        return $sReturn;
    }

    /**
     * returns a multi-dimensional array with list of file details within a given path
     * @param  string $pathAnalised
     * @return array
     */
    protected function getListOfFiles($pathAnalised)
    {
        if (!file_exists($pathAnalised)) {
            return null;
        }
        $dir                                 = dir($pathAnalised);
        $this->commonLibFlags[$pathAnalised] = 0;
        $fileDetails                         = null;
        while ($file                                = $dir->read()) {
            clearstatcache();
            $fName     = $pathAnalised . DIRECTORY_SEPARATOR . $file;
            $fileParts = pathinfo($fName);
            switch ($fileParts['basename']) {
                case '.':
                case '..':
                    break;
                default:
                    if (is_dir($fName)) {
                        $fileDetails[$fName] = $this->getListOfFiles($fName);
                    } else {
                        $this->commonLibFlags[$pathAnalised] += 1;
                        $xt                  = (isset($fileParts['extension']) ? $fileParts['extension'] : '-');
                        $fileDetails[$fName] = [
                            'Folder'                    => $fileParts['dirname'],
                            'BaseName'                  => $fileParts['basename'],
                            'Extension'                 => $xt,
                            'FileName'                  => $fileParts['filename'],
                            'Size'                      => filesize($fName),
                            'Sha1'                      => sha1_file($fName),
                            'TimestampAccessed'         => fileatime($fName),
                            'TimestampAccessedReadable' => date('Y-m-d H:i:s', fileatime($fName)),
                            'TimestampChanged'          => filectime($fName),
                            'TimestampChangedReadable'  => date('Y-m-d H:i:s', filectime($fName)),
                            'TimestampModified'         => filemtime($fName),
                            'TimestampModifiedReadable' => date('Y-m-d H:i:s', filemtime($fName)),
                        ];
                    }
                    break;
            }
        }
        $dir->close();
        return $fileDetails;
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
            return [];
        }
        $handle           = fopen($fileToRead, 'r');
        $fileContents     = fread($handle, filesize($fileToRead));
        fclose($handle);
        $packages         = $this->setJson2array($fileContents);
        $dateTimeToday    = new \DateTime(date('Y-m-d', strtotime('today')));
        $defaultNA        = '---';
        $finalInformation = [];
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
                    'string' => '<span style="color:black!important;font-weight:bold;">['
                    . date('Y-m-d H:i:s.', $dt['sec']) . substr(round($dt['usec'], -3), 0, 3) . ']</span> ',
                ];
                break;
            case 'float':
                $sReturn = ($dt['sec'] + $dt['usec'] / pow(10, 6));
                break;
            case 'string':
                $sReturn = '<span style="color:black!important;font-weight:bold;">['
                        . date('Y-m-d H:i:s.', $dt['sec']) . substr(round($dt['usec'], -3), 0, 3) . ']</span> ';
                break;
            default:
                $sReturn = 'Unknown return type...';
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
    protected function isJson($inputJson)
    {
        if (is_string($inputJson)) {
            json_decode($inputJson);
            return (json_last_error() == JSON_ERROR_NONE);
        } else {
            return 'Given input in ' . __FUNCTION__ . ' is not a json string...';
        }
    }

    /**
     * Generate an Excel file from a given array
     *
     * @param array $inFeatures
     */
    protected function setArrayToExcel($inFeatures)
    {
        if (!is_array($inFeatures['contentArray'])) {
            echo 'no data!!!';
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
            $margin    = 0.7 / 2.54;
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
            // output the created content to the browser
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Pragma: private');
            header('Cache-control: private, must-revalidate');
            header('Content-Disposition: attachment;filename="' . $xlFileName . '"');
            header('Cache-Control: max-age=0');
            $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
            $objWriter->save('php://output');
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
        //	Using a lookup cache adds a slight memory overhead, but boosts speed
        //	caching using a static within the method is faster than a class static,
        //	though it's additional memory overhead
        static $_indexCache = [];
        if (!isset($_indexCache[$pColumnIndex])) {
            // Determine column string
            if ($pColumnIndex < 26) {
                $_indexCache[$pColumnIndex] = chr(65 + $pColumnIndex);
            } elseif ($pColumnIndex < 702) {
                $_indexCache[$pColumnIndex] = chr(64 + ($pColumnIndex / 26)) .
                        chr(65 + $pColumnIndex % 26);
            } else {
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
            return 'Given input is not an array...';
        }
        if (version_compare(phpversion(), "5.4.0", ">=")) {
            $rtrn = utf8_encode(json_encode($inArray, JSON_FORCE_OBJECT | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } else {
            $rtrn = json_encode($inArray);
        }
        $jsonError = $this->setJsonErrorInPlainEnglish();
        if ($jsonError == '') {
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
        $outArray = [];
        foreach ($inArray as $value) {
            $outArray[$value] = $value;
        }
        ksort($outArray);
        return $outArray;
    }

    /**
     * Converts a JSON string into an Array
     *
     * @param string $inputJson
     * @return array
     */
    protected function setJson2array($inputJson)
    {
        if (!$this->isJson($inputJson)) {
            return ['error' => 'Given input is not an json...'];
        }
        $sReturn   = (json_decode($inputJson, true));
        $jsonError = $this->setJsonErrorInPlainEnglish();
        if ($jsonError == '') {
            return $sReturn;
        } else {
            return ['error' => $jsonError];
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
