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
 * useful functions to get quick results
 *
 * @author Daniel Popiniuc
 */
trait CommonBasic
{

    use CommonPermissions;

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
     * Returns the details about Communicator (current) file
     * w/o any kind of verification of file existance
     *
     * @param string $fileGiven
     * @return array
     */
    protected function getFileDetailsRaw($fileGiven)
    {
        $info              = new \SplFileInfo($fileGiven);
        $aFileBasicDetails = [
            'File Extension'         => $info->getExtension(),
            'File Group'             => $info->getGroup(),
            'File Inode'             => $info->getInode(),
            'File Link Target'       => ($info->isLink() ? $info->getLinkTarget() : '-'),
            'File Name'              => $info->getBasename('.' . $info->getExtension()),
            'File Name w. Extension' => $info->getFilename(),
            'File Owner'             => $info->getOwner(),
            'File Path'              => $info->getPath(),
            'Name'                   => $info->getRealPath(),
            'Type'                   => $info->getType(),
        ];
        $aDetails          = array_merge($aFileBasicDetails, $this->getFileDetailsRawStatistic($info, $fileGiven));
        ksort($aDetails);
        return $aDetails;
    }

    protected function getFileDetailsRawStatistic(\SplFileInfo $info, $fileGiven)
    {
        return [
            'File is Dir'        => $info->isDir(),
            'File is Executable' => $info->isExecutable(),
            'File is File'       => $info->isFile(),
            'File is Link'       => $info->isLink(),
            'File is Readable'   => $info->isReadable(),
            'File is Writable'   => $info->isWritable(),
            'File Permissions'   => $this->explainPerms($info->getPerms()),
            'Size'               => $info->getSize(),
            'Sha1'               => sha1_file($fileGiven),
            'Timestamp Accessed' => $this->getFileTimes($info->getATime()),
            'Timestamp Changed'  => $this->getFileTimes($info->getCTime()),
            'Timestamp Modified' => $this->getFileTimes($info->getMTime()),
        ];
    }

    private function getFileTimes($timeAsPhpNumber)
    {
        return [
            'PHP number' => $timeAsPhpNumber,
            'SQL format' => date('Y-m-d H:i:s', $timeAsPhpNumber),
        ];
    }

    /**
     * Moves files into another folder
     *
     * @param string $sourcePath
     * @param string $targetPath
     * @return string
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
        if (is_array($inputArray)) {
            if (!array_key_exists('path', $inputArray)) {
                return '`path` has not been provided';
            } elseif (!array_key_exists('dateRule', $inputArray)) {
                return '`dateRule` has not been provided';
            }
            return true;
        }
        return false;
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
        if (is_array($aFiles)) {
            $filesystem = new \Symfony\Component\Filesystem\Filesystem();
            $filesystem->remove($aFiles);
            return $this->setArrayToJson($aFiles);
        }
        return $aFiles;
    }

    protected function retrieveFilesOlderThanGivenRule($inputArray)
    {
        $proceedRetrieving = $this->removeFilesDecision($inputArray);
        if ($proceedRetrieving === true) {
            $finder   = new \Symfony\Component\Finder\Finder();
            $iterator = $finder->files()->ignoreUnreadableDirs(true)->followLinks()->in($inputArray['path']);
            $aFiles   = [];
            foreach ($iterator as $file) {
                if ($file->getATime() <= strtotime($inputArray['dateRule'])) {
                    $aFiles[] = $file->getRealPath();
                }
            }
            return $aFiles;
        }
        return $proceedRetrieving;
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
     * @param array $inArray
     * @return array
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
        if ($jsonError == '') {
            $jsonError = $rtrn;
        }
        return $jsonError;
    }

    /**
     * Provides a list of all known JSON errors and their description
     *
     * @return string
     */
    protected function setJsonErrorInPlainEnglish()
    {
        $knownErrors = [
            JSON_ERROR_NONE           => '',
            JSON_ERROR_DEPTH          => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR      => 'Unexpected control character found',
            JSON_ERROR_SYNTAX         => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8           => 'Malformed UTF-8 characters, possibly incorrectly encoded',
        ];
        return $knownErrors[json_last_error()];
    }
}
