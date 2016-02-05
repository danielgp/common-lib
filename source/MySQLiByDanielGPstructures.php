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
 * Usefull functions to get quick MySQL content
 *
 * @author Daniel Popiniuc
 */
trait MySQLiByDanielGPstructures
{

    use MySQLiByDanielGP;

    /**
     * Return the list of Tables from the MySQL server
     *
     * @return string
     */
    protected function getMySQLStatistics($filterArray = null)
    {
        return $this->getMySQLlistMultiple('Statistics', 'full_array_key_numbered', $filterArray);
    }

    /**
     * returns a list of MySQL databases
     *
     * @return array
     */
    protected function getMySQLactiveDatabases()
    {
        return $this->getMySQLlistDatabases(true);
    }

    /**
     * returns a list of active MySQL engines
     *
     * @return array
     */
    protected function getMySQLactiveEngines()
    {
        return $this->getMySQLlistEngines(true);
    }

    /**
     * returns the list of all MySQL generic informations
     *
     * @return array
     */
    protected function getMySQLgenericInformations()
    {
        if (is_null($this->mySQLconnection)) {
            return [];
        }
        return ['Info' => $this->mySQLconnection->server_info, 'Version' => $this->mySQLconnection->server_version];
    }

    /**
     * returns the list of all MySQL global variables
     *
     * @return array
     */
    protected function getMySQLglobalVariables()
    {
        return $this->getMySQLlistMultiple('VariablesGlobal', 'array_key_value');
    }

    /**
     * returns a list of MySQL indexes (w. choice of to choose any combination of db/table/column)
     *
     * @return array
     */
    protected function getMySQLlistColumns($filterArray = null)
    {
        return $this->getMySQLlistMultiple('Columns', 'full_array_key_numbered', $filterArray);
    }

    /**
     * returns a list of MySQL databases (w. choice of exclude/include the system ones)
     *
     * @return array
     */
    protected function getMySQLlistDatabases($excludeSystemDbs = true)
    {
        return $this->getMySQLlistMultiple('Databases', 'array_first_key_rest_values', $excludeSystemDbs);
    }

    /**
     * returns a list of MySQL engines (w. choice of return only the active ones)
     *
     * @return array
     */
    protected function getMySQLlistEngines($onlyActiveOnes = true)
    {
        return $this->getMySQLlistMultiple('Engines', 'array_first_key_rest_values', $onlyActiveOnes);
    }

    /**
     * returns a list of MySQL indexes (w. choice of to choose any combination of db/table/column)
     *
     * @return array
     */
    protected function getMySQLlistIndexes($filterArray = null)
    {
        return $this->getMySQLlistMultiple('Indexes', 'full_array_key_numbered', $filterArray);
    }

    /**
     * Return the list of Tables from the MySQL server
     *
     * @return string
     */
    protected function getMySQLlistTables($filterArray = null)
    {
        return $this->getMySQLlistMultiple('Tables', 'full_array_key_numbered', $filterArray);
    }

    /**
     * Returns maximum length for a given MySQL field
     *
     * @param array $fieldDetails
     * @param boolean $outputFormated
     * @return array
     */
    protected function setFieldNumbers($fieldDetails, $outputFormated = false)
    {
        $sRtrn = $this->setFieldSpecific($fieldDetails);
        if ($outputFormated) {
            if (is_array($sRtrn)) {
                foreach ($sRtrn as $key => $value) {
                    $sRtrn[$key] = $this->setNumberFormat($value);
                }
            }
        }
        return $sRtrn;
    }

    /**
     * Establishes numbers of fields
     *
     * @param array $fieldDetails
     * @return array
     */
    private function setFieldSpecific($fieldDetails)
    {
        if (in_array($fieldDetails['DATA_TYPE'], ['char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext'])) {
            return ['M' => $fieldDetails['CHARACTER_MAXIMUM_LENGTH']];
        } elseif (in_array($fieldDetails['DATA_TYPE'], ['decimal', 'numeric'])) {
            return ['M' => $fieldDetails['NUMERIC_PRECISION'], 'd' => $fieldDetails['NUMERIC_SCALE']];
        } elseif (in_array($fieldDetails['DATA_TYPE'], ['bigint', 'int', 'mediumint', 'smallint', 'tinyint'])) {
            return $this->setFldLmtsExact($fieldDetails['DATA_TYPE']);
        }
        return $this->setFieldSpecificElse($fieldDetails);
    }

    private function setFieldSpecificElse($fieldDetails)
    {
        $map = ['date' => 10, 'datetime' => 19, 'enum' => 65536, 'set' => 64, 'time' => 8, 'timestamp' => 19];
        if (array_key_exists($fieldDetails['DATA_TYPE'], $map)) {
            return ['M' => $map[$fieldDetails['DATA_TYPE']]];
        }
        return ['M' => '???'];
    }

    private function setFldLmtsExact($cTp)
    {
        $xct     = [
            'bigint'    => ['l' => -9223372036854775808, 'L' => 9223372036854775807, 's' => 21, 'sUS' => 20],
            'int'       => ['l' => -2147483648, 'L' => 2147483647, 's' => 11, 'sUS' => 10],
            'mediumint' => ['l' => -8388608, 'L' => 8388607, 's' => 9, 'sUS' => 8],
            'smallint'  => ['l' => -32768, 'L' => 32767, 's' => 6, 'sUS' => 5],
            'tinyint'   => ['l' => -128, 'L' => 127, 's' => 4, 'sUS' => 3],
        ];
        $aReturn = null;
        if (array_key_exists($cTp, $xct)) {
            $aReturn = ['m' => $xct[$cTp]['l'], 'M' => $xct[$cTp]['L'], 'l' => $xct[$cTp]['s']];
            if (strpos($cTp, 'unsigned') !== false) {
                $aReturn = ['m' => 0, 'M' => ($xct[$cTp]['L'] - $xct[$cTp]['l']), 'l' => $xct[$cTp]['sUS']];
            }
        }
        return $aReturn;
    }
}
