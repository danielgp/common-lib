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

class CommonLibLocaleTest extends \PHPUnit\Framework\TestCase
{

    public static function setUpBeforeClass()
    {
        require_once str_replace('tests' . DIRECTORY_SEPARATOR . 'php-unit', 'source', __DIR__)
            . DIRECTORY_SEPARATOR . 'CommonLibLocale.php';
    }

    public function testLocalMessage()
    {
        $mock = $this->getMockForTrait(CommonLibLocale::class);
        $actual = $mock->lclMsgCmn('i18n_Generic_Unknown');
        $this->assertEquals('unknown', $actual);
    }

    public function testlclMsgCmnNumber()
    {
        $mock = $this->getMockForTrait(CommonLibLocale::class);
        $mock->initializeSprGlbAndSession();
        $mock->tCmnSuperGlobals->request->set('lang', 'ro_RO');
        $numberZero = $mock->lclMsgCmnNumber($mock->lclMsgCmn('i18n_Record'), $mock->lclMsgCmn('i18n_Records'), 0);
        $this->assertEquals(str_replace('%d', 0, $mock->lclMsgCmn('i18n_Records')), $numberZero);
        $numberOne  = $mock->lclMsgCmnNumber($mock->lclMsgCmn('i18n_Record'), $mock->lclMsgCmn('i18n_Records'), 1);
        $this->assertEquals(str_replace('%d', 1, $mock->lclMsgCmn('i18n_Record')), $numberOne);
        $numberNine = $mock->lclMsgCmnNumber($mock->lclMsgCmn('i18n_Record'), $mock->lclMsgCmn('i18n_Records'), 9);
        $this->assertEquals(str_replace('%d', 9, $mock->lclMsgCmn('i18n_Records')), $numberNine);
    }

    public function testhandleLanguageIntoSessionGet()
    {
        $mock = $this->getMockForTrait(CommonLibLocale::class);
        $mock->initializeSprGlbAndSession();
        $mock->tCmnSuperGlobals->request->set('lang', 'en_US');
        $mock->handleLanguageIntoSession();
        $this->assertEquals('en_US', $mock->tCmnSession->get('lang'));
    }

    public function testhandleLanguageIntoSessionNormalize()
    {
        $mock = $this->getMockForTrait(CommonLibLocale::class);
        $mock->initializeSprGlbAndSession();
        $mock->tCmnSuperGlobals->request->set('lang', 'en_UK');
        $mock->handleLanguageIntoSession();
        $this->assertEquals('en_US', $mock->tCmnSession->get('lang'));
    }

    public function testsetDividedResult()
    {
        $mock = $this->getMockForTrait(CommonLibLocale::class);
        $mock->initializeSprGlbAndSession();
        $mock->tCmnSuperGlobals->request->set('lang', 'en_US');
        $numberZero                   = $mock->setDividedResult(0, 1);
        $this->assertEquals(0, $numberZero);
        $numberDivisionZero           = $mock->setDividedResult(1, 0);
        $this->assertEquals(0, $numberDivisionZero);
        $numberOneThousandNineHundred = $mock->setDividedResult(1900, 1, null);
        $this->assertEquals('1,900', $numberOneThousandNineHundred);
        $numberPi                     = $mock->setDividedResult(3.1459, 1, 2);
        $this->assertEquals('3.15', $numberPi);
    }
}
