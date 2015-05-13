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
class CommonLibLocaleTest extends PHPUnit_Framework_TestCase
{

    use \danielgp\common_lib\DomComponentsByDanielGP,
        \danielgp\common_lib\CommonLibLocale;

    public function testLocalMessage()
    {
        $actual = $this->lclMsgCmn('i18n_Generic_Unknown');
        $this->assertEquals('unknown', $actual);
    }

    public function testUppeRightBoxLanguages()
    {
        $actual = $this->setUppeRightBoxLanguages([
            'en_US' => 'US English',
            'ro_RO' => 'Română',
            'it_IT' => 'Italiano',
        ]);
        $this->assertContains('Română', $actual);
    }
}
