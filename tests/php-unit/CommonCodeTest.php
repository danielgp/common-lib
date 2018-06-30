<?php

/**
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 - 2018 Daniel Popiniuc
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
class CommonCodeTest extends \PHPUnit\Framework\TestCase
{

    use \danielgp\common_lib\CommonCode;

    public function testExplainPermissions()
    {
        $actual = $this->explainPerms('0666');
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
        $actual = $this->getContentFromUrlThroughCurl('http://127.0.0.1/informator/?Label=Client+Info');
        $this->assertJson($actual);
    }

    public function testGetContentFromUrlThroughCurlAsArrayIfJson()
    {
        $actual = $this->getContentFromUrlThroughCurlAsArrayIfJson('https://danielgp.000webhostapp.com/apps/informator/?Label=Client+Info')['response'];
        $this->assertArrayHasKey('Browser', $actual);
    }

    public function testGetContentFromUrlThroughCurlInvalid()
    {
        $actual = $this->getContentFromUrlThroughCurl('danielgp.000webhostapp.com/apps/informator/?Label=Client+Info');
        $this->assertJson($actual);
    }

    public function testGetContentFromUrlThroughCurlSecure()
    {
        $actual = $this->getContentFromUrlThroughCurl('http://danielgp.000webhostapp.com/apps/informator/?Label=Client+Info', [
            'forceSSLverification'
        ]);
        $this->assertJson($actual);
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
        $this->removeFilesOlderThanGivenRule([
            'path'     => dirname(ini_get('error_log')),
            'dateRule' => strtotime('10 years ago'),
        ]);
        $fileToCheck = str_replace('/', DIRECTORY_SEPARATOR, implode(DIRECTORY_SEPARATOR, [
            'path' => dirname(ini_get('error_log')),
            'php_error_log',
        ]));
        $this->assertFileNotExists($fileToCheck);
    }

    public function testRemoveFilesOlderThanGivenRuleNoDateRule()
    {
        $actual = $this->removeFilesOlderThanGivenRule([
            'path' => dirname(ini_get('error_log')),
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

    public function testRemoveFilesOlderThanGivenRuleStringInputs()
    {
        $actual = $this->removeFilesOlderThanGivenRule('given rule');
        $this->assertEquals(false, $actual);
    }

    public function testSetArrayToJsonInvalid()
    {
        $actual        = $this->setArrayToJson(['string']);
        $jsn           = ['string'];
        $valueToReturn = utf8_encode(json_encode($jsn, JSON_FORCE_OBJECT | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        $this->assertEquals($valueToReturn, $actual);
    }

    public function testSetArrayValuesAsKey()
    {
        $actual = $this->setArrayValuesAsKey(['one', 'two']);
        $this->assertArrayHasKey('one', $actual);
    }

    public function testSetJsonToArrayInvalidJson()
    {
        $actual = $this->setJsonToArray("['Item']['systemSku']");
        $this->assertArrayHasKey('error', $actual);
    }

    public function testSetJsonToArrayString()
    {
        $actual = $this->setJsonToArray('one');
        $this->assertArrayHasKey('error', $actual);
    }

    public function testSetJsonToArrayValid()
    {
        $actual = $this->setJsonToArray('{ "minion": "banana" }');
        $this->assertArrayHasKey('minion', $actual);
    }

    public function testUpperRightBoxLanguages()
    {
        $actual = $this->setUpperRightBoxLanguages([
            'en_US' => 'US English',
            'ro_RO' => 'Română',
            'it_IT' => 'Italiano',
        ]);
        $this->assertContains('Română', $actual);
    }

}
