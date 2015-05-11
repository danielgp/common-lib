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
class NetworkComponentsByDanielGPTest extends PHPUnit_Framework_TestCase
{

    use \danielgp\common_lib\NetworkComponentsByDanielGP;

    public function testCheckIpIsInRangeIn()
    {
        // Arrange
        $a = $this->checkIpIsInRange('160.221.78.69', '160.221.78.1', '160.221.79.254');
        // Assert
        $this->assertEquals('in', $a);
    }

    public function testCheckIpIsInRangeOut()
    {
        // Arrange
        $a = $this->checkIpIsInRange('160.221.78.69', '160.221.79.1', '160.221.79.254');
        // Assert
        $this->assertEquals('out', $a);
    }

    public function testCheckIpIsPrivateEqualInvalid()
    {
        // Arrange
        $a = $this->checkIpIsPrivate('192.168');
        // Assert
        $this->assertEquals('invalid', $a);
    }

    public function testCheckIpIsPrivateEqualPublic()
    {
        // Arrange
        $a = $this->checkIpIsPrivate('216.58.211.4');
        // Assert
        $this->assertEquals('public', $a);
    }

    public function testCheckIpIsV4OrV6EqualInvalid()
    {
        // Arrange
        $a = $this->checkIpIsV4OrV6('192.168');
        // Assert
        $this->assertEquals('invalid', $a);
    }

    public function testCheckIpIsV4OrV6EqualV4()
    {
        // Arrange
        $a = $this->checkIpIsV4OrV6('192.168.1.1');
        // Assert
        $this->assertEquals('V4', $a);
    }

    public function testCheckIpIsV4OrV6EqualV6()
    {
        // Arrange
        $a = $this->checkIpIsV4OrV6('::1');
        // Assert
        $this->assertEquals('V6', $a);
    }

    public function testConvertIpToNumber()
    {
        // Arrange
        $a = $this->convertIpToNumber('10.0.5.9');
        // Assert
        $this->assertEquals(167773449, $a);
    }

    public function testConvertIpToNumberInvlidIP()
    {
        // Arrange
        $a = $this->convertIpToNumber('10.99');
        // Assert
        $this->assertEquals('invalid IP', $a);
    }

    public function testConvertIpToNumberOfIpV6()
    {
        // Arrange
        $a = $this->convertIpToNumber('::FFFF:FFFF');
        // Assert
        $this->assertEquals(4294967295, $a);
    }
}
