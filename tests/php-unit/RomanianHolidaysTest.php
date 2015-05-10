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
class RomanianHolidaysTest extends \PHPUnit_Framework_TestCase
{

    use \danielgp\common_lib\RomanianHolidays;

    public function testHolidaysEaster2015CatholicEasterFirstDay()
    {
        $calculationDate = strtotime('2015-04-01');
        // Arrange
        $a               = $this->setHolidays($calculationDate, true);
        // Assert
        $this->assertContains(easter_date(date('Y', $calculationDate)), $a);
    }

    public function testHolidaysEaster2015CatholicEasterSecondDay()
    {
        $calculationDate = strtotime('2015-04-01');
        // Arrange
        $a               = $this->setHolidays($calculationDate, true);
        // Assert
        $this->assertContains(strtotime('+1 day', easter_date(date('Y', $calculationDate))), $a);
    }

    public function testHolidaysEaster2015FirstDayOfYear()
    {
        // Arrange
        $a = $this->setHolidays(strtotime('2015-12-01'));
        // Assert
        $this->assertContains(strtotime('2015-01-01'), $a);
    }

    public function testHolidaysEaster2015LastDayOfYear()
    {
        // Arrange
        $a = $this->setHolidays(strtotime('2015-12-01'));
        // Assert
        $this->assertNotContains(strtotime('2015-12-31'), $a);
    }

    public function testHolidaysEaster2015OrthodoxEasterFirstDay()
    {
        // Arrange
        $a = $this->setHolidays(strtotime('2015-04-01'), true);
        // Assert
        $this->assertContains(strtotime('2015-04-12'), $a);
    }

    public function testHolidaysEaster2015OrthodoxEasterSecondDay()
    {
        // Arrange
        $a = $this->setHolidays(strtotime('2015-04-01'), true);
        // Assert
        $this->assertContains(strtotime('2015-04-13'), $a);
    }

    public function testHolidaysInMonthForMonthWithCatholicEaster()
    {
        // Arrange
        $a = $this->setHolidaysInMonth(strtotime('2015-04-01'), true);
        // Assert
        $this->assertEquals(4, $a);
    }

    public function testHolidaysInMonthForMonthWithoutCatholicEaster()
    {
        // Arrange
        $a = $this->setHolidaysInMonth(strtotime('2015-04-01'), false);
        // Assert
        $this->assertEquals(2, $a);
    }

    public function testWorkingDaysInMonthForMonthOf19Days()
    {
        // Arrange
        $a = $this->setWorkingDaysInMonth(strtotime('2001-12-01'));
        // Assert
        $this->assertEquals(19, $a);
    }

    public function testWorkingDaysInMonthForMonthOf20Days()
    {
        // Arrange
        $a = $this->setWorkingDaysInMonth(strtotime('2001-02-01'));
        // Assert
        $this->assertEquals(20, $a);
    }

    public function testWorkingDaysInMonthForMonthOf20DaysWithCatholicEaster()
    {
        // Arrange
        $a = $this->setWorkingDaysInMonth(strtotime('2015-04-01'), true);
        // Assert
        $this->assertEquals(20, $a);
    }

    public function testWorkingDaysInMonthForMonthOf21Days()
    {
        // Arrange
        $a = $this->setWorkingDaysInMonth(strtotime('2006-01-01'));
        // Assert
        $this->assertEquals(21, $a);
    }

    public function testWorkingDaysInMonthForMonthOf22Days()
    {
        // Arrange
        $a = $this->setWorkingDaysInMonth(strtotime('2016-09-01'), false);
        // Assert
        $this->assertEquals(22, $a);
    }

    public function testWorkingDaysInMonthForMonthOf23Days()
    {
        // Arrange
        $a = $this->setWorkingDaysInMonth(strtotime('2015-07-01'));
        // Assert
        $this->assertEquals(23, $a);
    }
}
