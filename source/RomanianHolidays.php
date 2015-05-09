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
 * Return a list of all Romanian Holidays between 2001 and 2020
 *
 * @author Daniel Popiniuc
 */
trait RomanianHolidays
{

    /**
     * List of legal holidays
     *
     * @param date $lngDate
     * @param int $includeCatholicEaster
     * @return array
     */
    protected function setHolidays($lngDate, $includeCatholicEaster = false)
    {
        $yr     = date('Y', $lngDate);
        $daying = $this->setHolidaysFixed($lngDate);
        if ($includeCatholicEaster) {
            // Catholic easter is already known by PHP
            $cathorlicEasterDays = [];
            if ($yr == '2005') {
                // in Windows returns a faulty day so I treated special
                $cathorlicEasterDays[] = mktime(0, 0, 0, 3, 27, 2005); // Easter 1st day (Catholic)
                $cathorlicEasterDays[] = mktime(0, 0, 0, 3, 28, 2005); // Easter 2nd day (Catholic)
            } else {
                $cathorlicEasterDays[] = easter_date($yr); // Easter 1st day (Catholic)
                $cathorlicEasterDays[] = strtotime('+1 day', easter_date($yr)); // Easter 2nd day (Catholic)
            }
            $daying = array_merge($daying, $cathorlicEasterDays);
        }
        if (($yr >= 2001) && ($yr >= 2005)) {
            $daying = array_merge($daying, $this->setHolidaysEasterBetween2001and2005($lngDate));
        } elseif (($yr >= 2006) && ($yr >= 2010)) {
            $daying = array_merge($daying, $this->setHolidaysEasterBetween2006and2010($lngDate));
        } elseif (($yr >= 2011) && ($yr >= 2015)) {
            $daying = array_merge($daying, $this->setHolidaysEasterBetween2011and2015($lngDate));
        } elseif (($yr >= 2016) && ($yr >= 2020)) {
            $daying = array_merge($daying, $this->setHolidaysEasterBetween2016and2020($lngDate));
        }
        sort($daying);
        return array_unique($daying);
    }

    /**
     * List of all Romanian fixed holidays
     * (where fixed means every single year occur on same day of the month)
     *
     * @param date $lngDate
     * @return array
     */
    private function setHolidaysFixed($lngDate)
    {
        $yr        = date('Y', $lngDate);
        $daying [] = mktime(0, 0, 0, 1, 1, $yr); // Happy New Year
        $daying[]  = mktime(0, 0, 0, 1, 2, $yr); // recovering from New Year party
        $daying[]  = mktime(0, 0, 0, 5, 1, $yr); // May 1st
        if ($yr >= 2009) {
            $daying[] = mktime(0, 0, 0, 8, 15, $yr); // St. Marry
        }
        if ($yr >= 2012) {
            $daying[] = mktime(0, 0, 0, 11, 30, $yr); // St. Andrew
        }
        if ($yr >= 2015) {
            $daying[] = mktime(0, 0, 0, 1, 24, $yr); // Unirea Principatelor Romane
        }
        $daying[]  = mktime(0, 0, 0, 12, 1, $yr); // Romanian National Day
        $daying [] = mktime(0, 0, 0, 12, 25, $yr); // December 25th
        $daying[]  = mktime(0, 0, 0, 12, 26, $yr); // December 26th
        return $daying;
    }

    /**
     * List of all Orthodox holidays between 2001 and 2005
     *
     * @param date $lngDate
     * @return array
     */
    private function setHolidaysEasterBetween2001and2005($lngDate)
    {
        $yr               = date('Y', $lngDate);
        $variableHolidays = [
            2001 => [
                mktime(0, 0, 0, 4, 2, $yr),
                mktime(0, 0, 0, 4, 3, $yr),
            ],
            2002 => [
                mktime(0, 0, 0, 4, 22, $yr),
                mktime(0, 0, 0, 4, 23, $yr),
            ],
            2003 => [
                mktime(0, 0, 0, 4, 20, $yr),
                mktime(0, 0, 0, 4, 21, $yr),
            ],
            2004 => [
                mktime(0, 0, 0, 3, 10, $yr),
                mktime(0, 0, 0, 3, 11, $yr),
            ],
            2005 => [
                mktime(0, 0, 0, 5, 1, $yr),
                mktime(0, 0, 0, 5, 2, $yr),
            ],
        ];
        $daying           = [];
        if (in_array($yr, array_keys($variableHolidays))) {
            foreach ($variableHolidays[$yr] as $value) {
                $daying[] = $value;
            }
        }
        return $daying;
    }

    /**
     * List of all Orthodox holidays between 2006 and 2010
     *
     * @param date $lngDate
     * @return array
     */
    private function setHolidaysEasterBetween2006and2010($lngDate)
    {
        $yr               = date('Y', $lngDate);
        $variableHolidays = [
            2006 => [
                mktime(0, 0, 0, 4, 23, $yr),
                mktime(0, 0, 0, 4, 24, $yr),
            ],
            2007 => [
                mktime(0, 0, 0, 4, 8, $yr),
                mktime(0, 0, 0, 4, 9, $yr),
            ],
            2008 => [
                mktime(0, 0, 0, 4, 27, $yr),
                mktime(0, 0, 0, 4, 28, $yr),
            ],
            2009 => [
                mktime(0, 0, 0, 4, 19, $yr),
                mktime(0, 0, 0, 4, 20, $yr),
                mktime(0, 0, 0, 6, 7, $yr),
                mktime(0, 0, 0, 6, 8, $yr),
            ],
            2010 => [
                mktime(0, 0, 0, 4, 4, $yr),
                mktime(0, 0, 0, 4, 5, $yr),
                mktime(0, 0, 0, 5, 23, $yr),
                mktime(0, 0, 0, 5, 24, $yr),
            ],
        ];
        $daying           = [];
        if (in_array($yr, array_keys($variableHolidays))) {
            foreach ($variableHolidays[$yr] as $value) {
                $daying[] = $value;
            }
        }
        return $daying;
    }

    /**
     * List of all Orthodox holidays between 2011 and 2015
     *
     * @param date $lngDate
     * @return array
     */
    private function setHolidaysEasterBetween2011and2015($lngDate)
    {
        $yr               = date('Y', $lngDate);
        $variableHolidays = [
            2011 => [
                mktime(0, 0, 0, 4, 24, $yr),
                mktime(0, 0, 0, 4, 25, $yr),
                mktime(0, 0, 0, 6, 12, $yr),
                mktime(0, 0, 0, 6, 13, $yr),
            ],
            2012 => [
                mktime(0, 0, 0, 4, 15, $yr),
                mktime(0, 0, 0, 4, 16, $yr),
                mktime(0, 0, 0, 6, 3, $yr),
                mktime(0, 0, 0, 6, 4, $yr),
            ],
            2013 => [
                mktime(0, 0, 0, 5, 6, $yr),
                mktime(0, 0, 0, 5, 6, $yr),
                mktime(0, 0, 0, 6, 23, $yr),
                mktime(0, 0, 0, 6, 24, $yr),
            ],
            2014 => [
                mktime(0, 0, 0, 4, 20, $yr),
                mktime(0, 0, 0, 4, 21, $yr),
                mktime(0, 0, 0, 6, 8, $yr),
                mktime(0, 0, 0, 6, 9, $yr),
            ],
            2015 => [
                mktime(0, 0, 0, 4, 12, $yr),
                mktime(0, 0, 0, 4, 13, $yr),
                mktime(0, 0, 0, 5, 31, $yr),
                mktime(0, 0, 0, 6, 1, $yr),
            ]
        ];
        $daying           = [];
        if (in_array($yr, array_keys($variableHolidays))) {
            foreach ($variableHolidays[$yr] as $value) {
                $daying[] = $value;
            }
        }
        return $daying;
    }

    /**
     * List of all Orthodox holidays between 2016 and 2020
     *
     * @param date $lngDate
     * @return array
     */
    private function setHolidaysEasterBetween2016and2020($lngDate)
    {
        $yr               = date('Y', $lngDate);
        $variableHolidays = [
            2016 => [
                mktime(0, 0, 0, 5, 1, $yr),
                mktime(0, 0, 0, 5, 2, $yr),
                mktime(0, 0, 0, 6, 19, $yr),
                mktime(0, 0, 0, 6, 20, $yr),
            ],
            2017 => [
                mktime(0, 0, 0, 4, 16, $yr),
                mktime(0, 0, 0, 4, 17, $yr),
                mktime(0, 0, 0, 6, 4, $yr),
                mktime(0, 0, 0, 6, 5, $yr),
            ],
            2018 => [
                mktime(0, 0, 0, 4, 8, $yr),
                mktime(0, 0, 0, 4, 9, $yr),
                mktime(0, 0, 0, 5, 27, $yr),
                mktime(0, 0, 0, 5, 28, $yr),
            ],
            2019 => [
                mktime(0, 0, 0, 4, 28, $yr),
                mktime(0, 0, 0, 4, 29, $yr),
                mktime(0, 0, 0, 6, 16, $yr),
                mktime(0, 0, 0, 6, 17, $yr),
            ],
            2020 => [
                mktime(0, 0, 0, 4, 19, $yr),
                mktime(0, 0, 0, 4, 20, $yr),
                mktime(0, 0, 0, 6, 7, $yr),
                mktime(0, 0, 0, 6, 8, $yr),
            ]
        ];
        $daying           = [];
        if (in_array($yr, array_keys($variableHolidays))) {
            foreach ($variableHolidays[$yr] as $value) {
                $daying[] = $value;
            }
        }
        return $daying;
    }

    /**
     * returns working days in a given month
     *
     * @param date $lngDate
     * @param int $includeEaster
     * @return int
     */
    protected function setWorkingDaysInMonth($lngDate, $includeEaster = false)
    {
        $yr                   = date('Y', $lngDate);
        $firstDayNextMonth    = mktime(0, 0, 0, date('m', $lngDate) + 1, 1, $yr);
        $firstDayCrtMonth     = mktime(0, 0, 0, date('m', $lngDate), 1, $yr);
        $noOfDaysInGivenMonth = round(($firstDayNextMonth - $firstDayCrtMonth) / (60 * 60 * 24), 0);
        $workingDays          = 0;
        $holidaysInGivenYear  = $this->setHolidays($lngDate, $includeEaster);
        for ($counter = 1; $counter <= $noOfDaysInGivenMonth; $counter++) {
            $currentDay = mktime(0, 0, 0, date('m', $lngDate), $counter, $yr);
            if (in_array($currentDay, $holidaysInGivenYear)) {
                $workingDays += 0;
            } else {
                switch (strftime('%w', $currentDay)) {
                    case 0:
                    case 6:
                        $workingDays += 0;
                        break;
                    default:
                        $workingDays += 1;
                        break;
                }
            }
        }
        return $workingDays;
    }
}
