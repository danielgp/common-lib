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
trait CommonBasic
{

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
        $aReturn           = [];
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
        return $aReturn;
    }

    /**
     * Moves files into another folder
     *
     * @param type $sourcePath
     * @param type $targetPath
     * @return type
     */
    protected function moveFilesIntoTargetFolder($sourcePath, $targetPath)
    {
        $filesystem = new \Symfony\Component\Filesystem\Filesystem();
        $filesystem->mirror($sourcePath, $targetPath);
        $finder     = new \Symfony\Component\Finder\Finder();
        $iterator   = $finder->files()->ignoreUnreadableDirs(true)->followLinks()->in($sourcePath);
        $sFiles     = [];
        foreach ($iterator as $file) {
            $relativePathFile = str_replace($sourcePath, '', $file->getRealPath());
            if (!file_exists($targetPath . $relativePathFile)) {
                $sFiles[$relativePathFile] = $targetPath . $relativePathFile;
            }
        }
        return $this->setArrayToJson($sFiles);
    }

    protected function removeFilesDecision($inputArray)
    {
        $proceedWithDeletion = false;
        if (is_array($inputArray)) {
            if (!isset($inputArray['path'])) {
                return '`path` has not been provided';
            } elseif (!isset($inputArray['dateRule'])) {
                return '`dateRule` has not been provided';
            }
            $proceedWithDeletion = true;
        }
        return $proceedWithDeletion;
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
        $aFiles = $this->retrieveFilesOlderThanGivenRule($inputArray);
        if (is_null($aFiles)) {
            return $aFiles;
        }
        $filesystem = new \Symfony\Component\Filesystem\Filesystem();
        $filesystem->remove($aFiles);
        return $this->setArrayToJson($aFiles);
    }

    protected function retrieveFilesOlderThanGivenRule($inputArray)
    {
        $proceedWithRetrieving = $this->removeFilesDecision($inputArray);
        if ($proceedWithRetrieving) {
            $finder   = new \Symfony\Component\Finder\Finder();
            $iterator = $finder->files()->ignoreUnreadableDirs(true)->followLinks()->in($inputArray['path']);
            $aFiles   = null;
            foreach ($iterator as $file) {
                if ($file->getATime() < strtotime($inputArray['dateRule'])) {
                    $aFiles[] = $file->getRealPath();
                }
            }
            return $aFiles;
        }
        return null;
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
     * Converts an array into JSON string
     *
     * @param array $inArray
     * @return string
     */
    protected function setArrayToJson(array $inArray)
    {
        $rtrn      = utf8_encode(json_encode($inArray, JSON_FORCE_OBJECT | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        $jsonError = $this->setJsonErrorInPlainEnglish();
        if (is_null($jsonError)) {
            return $rtrn;
        } else {
            return $jsonError;
        }
    }

    /**
     * Provides a list of all known JSON errors and their description
     *
     * @return type
     */
    protected function setJsonErrorInPlainEnglish()
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
}
