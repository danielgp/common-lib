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

    private function packIntoJson($aReturn, $keyToWorkWith)
    {
        if ($this->isJsonByDanielGP($aReturn[$keyToWorkWith])) {
            return '"' . $keyToWorkWith . '": ' . $aReturn[$keyToWorkWith];
        }
        return '"' . $keyToWorkWith . '": {' . $aReturn[$keyToWorkWith] . ' }';
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
        $proceedWithDeletion = false;
        if (is_array($inputArray)) {
            if (!isset($inputArray['path'])) {
                return '`path` has not been provided';
            } elseif (!isset($inputArray['dateRule'])) {
                return '`dateRule` has not been provided';
            }
            $proceedWithDeletion = true;
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
