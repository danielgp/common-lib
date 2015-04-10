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

    use DomComponentsByDanielGPwithCDN,
        DomComponentsByDanielGP,
        RomanianHolidays,
        MySQLiByDanielGPqueries,
        MySQLiByDanielGP;

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
            $aReturn['response'] = $responseJsonFromClientOriginal;
            if (is_array($aReturn['response'])) {
                ksort($aReturn['response']);
            }
            ksort($aReturn['info']);
        }
        curl_close($ch);
        return $aReturn;
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
     * Converts an array into JSON string
     *
     * @param array $inArray
     * @return string
     */
    protected function setArray2json($inArray)
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
