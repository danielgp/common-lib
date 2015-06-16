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
class CommonCodeTest extends PHPUnit_Framework_TestCase
{

    use \danielgp\common_lib\CommonCode;

    public function testExplainPermissions()
    {
        $actual = $this->explainPermissions('0666');
        $this->assertArrayHasKey('Code', $actual);
        $this->assertArrayHasKey('Overall', $actual);
        $this->assertArrayHasKey('First', $actual);
        $this->assertArrayHasKey('Owner', $actual);
        $this->assertArrayHasKey('Group', $actual);
    }

    public function testFileDetailsExisting()
    {
        $actual = $this->getFileDetails(__FILE__);
        $this->assertArrayHasKey('File Name w. Extension', $actual);
        $this->assertArrayHasKey('Timestamp Modified', $actual);
        $this->assertArrayHasKey('Type', $actual);
    }

    public function testFileDetaiNotExisting()
    {
        $actual = $this->getFileDetails('not existing file');
        $this->assertArrayHasKey('error', $actual);
    }

    public function testGetContentFromUrlThroughCurl()
    {
        $actual = $this->getContentFromUrlThroughCurl('http://127.0.0.1/informator/source/info/?Label=ClientInfo');
        $this->assertJson($actual);
    }

    public function testGetContentFromUrlThroughCurlAsArrayIfJson()
    {
        $actual = $this->getContentFromUrlThroughCurlAsArrayIfJson('http://127.0.0.1/informator/source/info/?Label=ClientInfo')['response'];
        $this->assertArrayHasKey('Browser', $actual);
    }

    public function testGetContentFromUrlThroughCurlInvalid()
    {
        $actual = $this->getContentFromUrlThroughCurl('127.0.0.1/informator/source/info/?Label=ClientInfo');
        $this->assertJson($actual);
    }

    public function testGetContentFromUrlThroughCurlSecure()
    {
        $actual = $this->getContentFromUrlThroughCurl('https://127.0.0.1/informator/source/info/?Label=ClientInfo', [
            'forceSSLverification'
        ]);
        $this->assertJson($actual);
    }

    public function testGetPackageDetailsFromGivenComposerLockFile()
    {
        $pathParts = explode(DIRECTORY_SEPARATOR, __DIR__);
        $file      = implode(DIRECTORY_SEPARATOR, array_diff($pathParts, ['tests', 'php-unit']))
                . DIRECTORY_SEPARATOR . 'composer.lock';
        $actual    = $this->getPackageDetailsFromGivenComposerLockFile($file);
        $this->assertArrayHasKey('Aging', $actual['gettext/gettext']);
    }

    public function testGetPackageDetailsFromGivenComposerLockFileError()
    {
        $actual = $this->getPackageDetailsFromGivenComposerLockFile('composer.not');
        $this->assertArrayHasKey('error', $actual);
    }

    public function testListOfFilesExisting()
    {
        $actual = $this->getListOfFiles(__DIR__);
        $this->assertArrayHasKey(__FILE__, $actual);
    }

    public function testListOfFilesNotExisting()
    {
        $actual = $this->getListOfFiles('not existing file');
        $this->assertArrayHasKey('error', $actual);
    }

    public function testListOfFilesNotFolder()
    {
        $actual = $this->getListOfFiles(__FILE__);
        $this->assertArrayHasKey('error', $actual);
    }

    public function testGetTimestampArray()
    {
        $actual = $this->getTimestamp('array');
        $this->assertArrayHasKey('float', $actual);
    }

    public function testGetTimestampFloat()
    {
        $actual = $this->getTimestamp('float');
        $this->assertGreaterThan(strtotime('now'), $actual);
    }

    public function testGetTimestampString()
    {
        $actual = $this->getTimestamp('string');
        $this->assertEquals(date('[Y-m-d H:i:s', strtotime('now')), substr(strip_tags($actual), 0, 20));
    }

    public function testGetTimestampUnknownReturnType()
    {
        $actual = $this->getTimestamp('just time');
        $this->assertEquals(sprintf($this->lclMsgCmn('i18n_Error_UnknownReturnType'), 'just time'), $actual);
    }

    public function testIsJsonByDanielGP()
    {
        $actual = $this->isJsonByDanielGP(['array']);
        $this->assertEquals($this->lclMsgCmn('i18n_Error_GivenInputIsNotJson'), $actual);
    }

    public function testRemoveFilesOlderThanGivenRule()
    {
        $actual = $this->removeFilesOlderThanGivenRule([
            'path'     => 'D:\\www\\other\\logs\\PHP\\PHP56\\',
            'dateRule' => strtotime('0 sec ago'),
        ]);
        $this->assertFileNotExists('D:\\www\\other\\logs\\PHP\\PHP56\\errors.log');
    }

    public function testRemoveFilesOlderThanGivenRuleNoDateRule()
    {
        $actual = $this->removeFilesOlderThanGivenRule([
            'path' => 'D:\\www\\other\\logs\\PHP\\PHP56\\',
        ]);
        $this->assertEquals('`dateRule` has not been provided', $actual);
    }

    public function testRemoveFilesOlderThanGivenRuleNoPath()
    {
        $actual = $this->removeFilesOlderThanGivenRule([
            'dateRule' => strtotime('1 sec ago'),
        ]);
        $this->assertEquals('`path` has not been provided', $actual);
    }

    public function testSetArrayToJsonInvalid()
    {
        $actual = $this->setArrayToJson('string');
        $this->assertEquals($this->lclMsgCmn('i18n_Error_GivenInputIsNotArray'), $actual);
    }

    public function testSetArrayValuesAsKey()
    {
        $actual = $this->setArrayValuesAsKey(['one', 'two']);
        $this->assertArrayHasKey('one', $actual);
    }

    public function testSetJsonToArrayInvalid()
    {
        $actual = $this->setJsonToArray('one');
        $this->assertArrayHasKey('error', $actual);
    }
}
