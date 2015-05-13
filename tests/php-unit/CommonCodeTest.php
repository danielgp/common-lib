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
}
