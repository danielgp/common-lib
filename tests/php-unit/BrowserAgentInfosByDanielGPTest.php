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
class BrowserAgentInfosByDanielGPTest extends PHPUnit_Framework_TestCase
{

    use \danielgp\common_lib\NetworkComponentsByDanielGP,
        \danielgp\common_lib\BrowserAgentInfosByDanielGP;

    public function testArchitectureBrowserAMD64()
    {
        $ua = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:39.0) Gecko/20100101 Firefox/39.0';
        // Arrange
        $a  = $this->getArchitectureFromUserAgent($ua, 'browser');
        // Assert
        $this->assertContains('AMD/Intel x64', $a['name']);
    }

    public function testArchitectureOperatingSystemAMD64()
    {
        $ua = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:39.0) Gecko/20100101 Firefox/39.0';
        // Arrange
        $a  = $this->getArchitectureFromUserAgent($ua, 'os');
        // Assert
        $this->assertContains('AMD/Intel x64', $a['name']);
    }

    public function testArchitectureOperatingSystemARM()
    {
        $ua = 'Mozilla/5.0 (Android; Mobile; rv:37.0) Gecko/37.0 Firefox/37.0';
        // Arrange
        $a  = $this->getArchitectureFromUserAgent($ua, 'os');
        // Assert
        $this->assertContains('Advanced RISC Machine', $a['name']);
    }

    public function testArchitectureOperatingSystemIA32()
    {
        $ua = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.90 Safari/537.36 OPR/29.0.1795.47';
        // Arrange
        $a  = $this->getArchitectureFromUserAgent($ua, 'browser');
        // Assert
        $this->assertContains('Intel x86', $a['name']);
    }

    public function testArchitectureOperatingSystemUnknown()
    {
        $ua = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.90 Safari/537.36 OPR/29.0.1795.47';
        // Arrange
        $a  = $this->getArchitectureFromUserAgent($ua, 'dummy');
        // Assert
        $this->assertContains('---', $a['name']);
    }

    public function testClientDetails()
    {
        // Arrange
        $a = $this->getClientBrowserDetails([
            'Browser',
            'Device',
            'OS',
                ], 'c:\\Users\\e303778\\AppData\\LocalLow\\Temp');
        // Assert
        $this->assertNotEmpty($a);
    }

    public function testClientDetailsNoCacheSpecified()
    {
        // Arrange
        $a = $this->getClientBrowserDetails([
            'Browser',
            'Device',
            'OS',
        ]);
        // Assert
        $this->assertNotEmpty($a);
    }

    public function UserAgent()
    {
        $actual = $this->getUserAgentByCommonLib();
        $this->assertEquals('Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:39.0) Gecko/20100101 Firefox/39.0', $actual);
    }
}
