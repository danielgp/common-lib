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

namespace danielgp\common_lib;

class CommonCodeTest extends \PHPUnit\Framework\TestCase
{

    protected function setUp()
    {
        require_once str_replace('tests' . DIRECTORY_SEPARATOR . 'php-unit', 'source', __DIR__)
                . DIRECTORY_SEPARATOR . 'CommonLibLocale.php';
        require_once str_replace('tests' . DIRECTORY_SEPARATOR . 'php-unit', 'source', __DIR__)
                . DIRECTORY_SEPARATOR . 'CommonPermissions.php';
        require_once str_replace('tests' . DIRECTORY_SEPARATOR . 'php-unit', 'source', __DIR__)
                . DIRECTORY_SEPARATOR . 'DomBasicComponentsByDanielGP.php';
        require_once str_replace('tests' . DIRECTORY_SEPARATOR . 'php-unit', 'source', __DIR__)
                . DIRECTORY_SEPARATOR . 'MySQLiByDanielGPqueriesBasic.php';
        require_once str_replace('tests' . DIRECTORY_SEPARATOR . 'php-unit', 'source', __DIR__)
                . DIRECTORY_SEPARATOR . 'MySQLiByDanielGPqueries.php';
        require_once str_replace('tests' . DIRECTORY_SEPARATOR . 'php-unit', 'source', __DIR__)
                . DIRECTORY_SEPARATOR . 'CommonBasic.php';
        require_once str_replace('tests' . DIRECTORY_SEPARATOR . 'php-unit', 'source', __DIR__)
                . DIRECTORY_SEPARATOR . 'DomHeaderFooterByDanielGP.php';
        require_once str_replace('tests' . DIRECTORY_SEPARATOR . 'php-unit', 'source', __DIR__)
                . DIRECTORY_SEPARATOR . 'DomComponentsByDanielGP.php';
        require_once str_replace('tests' . DIRECTORY_SEPARATOR . 'php-unit', 'source', __DIR__)
                . DIRECTORY_SEPARATOR . 'MySQLiByDanielGPnumbers.php';
        require_once str_replace('tests' . DIRECTORY_SEPARATOR . 'php-unit', 'source', __DIR__)
                . DIRECTORY_SEPARATOR . 'MySQLiByDanielGP.php';
        require_once str_replace('tests' . DIRECTORY_SEPARATOR . 'php-unit', 'source', __DIR__)
                . DIRECTORY_SEPARATOR . 'MySQLiByDanielGPstructures.php';
        require_once str_replace('tests' . DIRECTORY_SEPARATOR . 'php-unit', 'source', __DIR__)
                . DIRECTORY_SEPARATOR . 'MySQLiByDanielGPtables.php';
        require_once str_replace('tests' . DIRECTORY_SEPARATOR . 'php-unit', 'source', __DIR__)
                . DIRECTORY_SEPARATOR . 'MySQLiAdvancedOutput.php';
        require_once str_replace('tests' . DIRECTORY_SEPARATOR . 'php-unit', 'source', __DIR__)
                . DIRECTORY_SEPARATOR . 'CommonViews.php';
        require_once str_replace('tests' . DIRECTORY_SEPARATOR . 'php-unit', 'source', __DIR__)
                . DIRECTORY_SEPARATOR . 'CommonCode.php';
    }

    public function testExplainPermissions()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->explainPerms('0666');
        $this->assertArrayHasKey('Code', $actual);
        $this->assertArrayHasKey('Overall', $actual);
        $this->assertArrayHasKey('First', $actual);
        $this->assertArrayHasKey('Owner', $actual);
        $this->assertArrayHasKey('Group', $actual);
    }

    public function testFileDetailsExisting()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->getFileDetails(__FILE__);
        $this->assertArrayHasKey('File Name w. Extension', $actual);
        $this->assertArrayHasKey('Timestamp Modified', $actual);
        $this->assertArrayHasKey('Type', $actual);
    }

    public function testFileDetaiNotExisting()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->getFileDetails('not existing file');
        $this->assertArrayHasKey('error', $actual);
    }

    public function testGetContentFromUrlThroughCurl()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->getContentFromUrlThroughCurl('http://127.0.0.1/informator/?Label=Client+Info');
        $this->assertJson($actual);
    }

    public function testGetContentFromUrlThroughCurlAsArrayIfJson()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->getContentFromUrlThroughCurlAsArrayIfJson(''
                        . 'https://danielgp.000webhostapp.com/apps/informator/?Label=Client+Info')['response'];
        $this->assertArrayHasKey('Browser', $actual);
    }

    public function testGetContentFromUrlThroughCurlInvalid()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->getContentFromUrlThroughCurl('danielgp.000webhostapp.com/apps/informator/?Label=Client+Info');
        $this->assertJson($actual);
    }

    public function testGetContentFromUrlThroughCurlSecure()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->getContentFromUrlThroughCurl(''
                . 'http://danielgp.000webhostapp.com/apps/informator/?Label=Client+Info', ['forceSSLverification']);
        $this->assertJson($actual);
    }

    public function testListOfFilesExisting()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->getListOfFiles(__DIR__);
        $this->assertArrayHasKey(__FILE__, $actual);
    }

    public function testListOfFilesNotExisting()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->getListOfFiles('not existing file');
        $this->assertArrayHasKey('error', $actual);
    }

    public function testListOfFilesNotFolder()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->getListOfFiles(__FILE__);
        $this->assertArrayHasKey('error', $actual);
    }

    public function testGetTimestampArray()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->getTimestamp('array');
        $this->assertArrayHasKey('float', $actual);
    }

    public function testGetTimestampFloat()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->getTimestamp('float');
        $this->assertGreaterThan(strtotime('now'), $actual);
    }

    public function testGetTimestampString()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->getTimestamp('string');
        $this->assertEquals(date('[Y-m-d H:i:s', strtotime('now')), substr(strip_tags($actual), 0, 20));
    }

    public function testGetTimestampUnknownReturnType()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->getTimestamp('just time');
        $this->assertEquals(sprintf($mock->lclMsgCmn('i18n_Error_UnknownReturnType'), 'just time'), $actual);
    }

    public function testIsJsonByDanielGP()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->isJsonByDanielGP(['array']);
        $this->assertEquals($mock->lclMsgCmn('i18n_Error_GivenInputIsNotJson'), $actual);
    }

    public function testRemoveFilesOlderThanGivenRule()
    {
        $mock        = $this->getMockForTrait(CommonCode::class);
        $where       = getcwd();
        $mock->removeFilesOlderThanGivenRule([
            'path'     => $where,
            'dateRule' => strtotime('10 years ago'),
        ]);
        $fileToCheck = str_replace('/', DIRECTORY_SEPARATOR, implode(DIRECTORY_SEPARATOR, [
            'path' => $where,
            'php_error_log',
        ]));
        $this->assertFileNotExists($fileToCheck);
    }

    public function testRemoveFilesOlderThanGivenRuleNoDateRule()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->removeFilesOlderThanGivenRule([
            'path' => dirname(ini_get('error_log')),
        ]);
        $this->assertEquals('`dateRule` has not been provided', $actual);
    }

    public function testRemoveFilesOlderThanGivenRuleNoPath()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->removeFilesOlderThanGivenRule([
            'dateRule' => strtotime('1 sec ago'),
        ]);
        $this->assertEquals('`path` has not been provided', $actual);
    }

    public function testRemoveFilesOlderThanGivenRuleStringInputs()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->removeFilesOlderThanGivenRule('given rule');
        $this->assertEquals(false, $actual);
    }

    public function testSetArrayToJsonInvalid()
    {
        $mock          = $this->getMockForTrait(CommonCode::class);
        $actual        = $mock->setArrayToJson(['string']);
        $jsn           = ['string'];
        $valueToReturn = utf8_encode(json_encode($jsn, JSON_FORCE_OBJECT | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        $this->assertEquals($valueToReturn, $actual);
    }

    public function testSetArrayValuesAsKey()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->setArrayValuesAsKey(['one', 'two']);
        $this->assertArrayHasKey('one', $actual);
    }

    public function testSetJsonToArrayInvalidJson()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->setJsonToArray("['Item']['systemSku']");
        $this->assertArrayHasKey('error', $actual);
    }

    public function testSetJsonToArrayString()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->setJsonToArray('one');
        $this->assertArrayHasKey('error', $actual);
    }

    public function testSetJsonToArrayValid()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->setJsonToArray('{ "minion": "banana" }');
        $this->assertArrayHasKey('minion', $actual);
    }

    public function testUpperRightBoxLanguages()
    {
        $mock   = $this->getMockForTrait(CommonCode::class);
        $actual = $mock->setUpperRightBoxLanguages([
            'en_US' => 'US English',
            'ro_RO' => 'Română',
            'it_IT' => 'Italiano',
        ]);
        $this->assertContains('Română', $actual);
    }
}
