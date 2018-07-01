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
 * useful functions to get quick results
 *
 * @author Daniel Popiniuc
 */
trait MySQLiByDanielGPtables
{

    use MySQLiByDanielGPstructures;

    /**
     * Returns a Numeric field 2 use in a form
     *
     * @param string $tblSrc
     * @param array $value
     * @param array $iar
     * @return string
     */
    private function getFieldOutputNumeric($tblSrc, $value, $iar = [])
    {
        if ($value['EXTRA'] == 'auto_increment') {
            return $this->getFieldOutputNumericAI($value, $iar);
        }
        $fkArray = $this->getForeignKeysToArray($this->advCache['workingDatabase'], $tblSrc, $value['COLUMN_NAME']);
        if ($fkArray === []) {
            $fldNos = $this->setFieldNumbers($value);
            return $this->getFieldOutputTT($value, min(50, (array_key_exists('l', $fldNos) ? $fldNos['l'] : 99)), $iar);
        }
        return $this->getFieldOutputNumericNonFK($fkArray, $value, $iar);
    }

    /**
     * Handles creation of Auto Increment numeric field type output
     *
     * @param array $value
     * @param array $iar
     * @return string
     */
    private function getFieldOutputNumericAI($value, $iar = [])
    {
        if ($this->getFieldValue($value) == '') {
            $spF = ['id' => $value['COLUMN_NAME'], 'style' => 'font-style:italic;'];
            return $this->setStringIntoTag('auto-numar', 'span', $spF);
        }
        $inAdtnl = [
            'type'  => 'hidden',
            'name'  => $value['COLUMN_NAME'],
            'id'    => $value['COLUMN_NAME'],
            'value' => $this->getFieldValue($value),
        ];
        if ($iar !== []) {
            $inAdtnl = array_merge($inAdtnl, $iar);
        }
        return '<b>' . $this->getFieldValue($value) . '</b>' . $this->setStringIntoShortTag('input', $inAdtnl);
    }

    /**
     * Builds field output type for numeric types if not FK
     *
     * @param array $fkArray
     * @param array $value
     * @param array $iar
     * @return string
     */
    private function getFieldOutputNumericNonFK($fkArray, $value, $iar = [])
    {
        $query         = $this->sQueryGenericSelectKeyValue([
            '`' . $value['COLUMN_NAME'] . '`',
            $fkArray[$value['COLUMN_NAME']][2],
            $fkArray[$value['COLUMN_NAME']][0],
        ]);
        $selectOptions = $this->setMySQLquery2Server($query, 'array_key_value')['result'];
        $selectValue   = $this->getFieldValue($value);
        $inAdtnl       = ['size' => 1];
        if ($value['IS_NULLABLE'] == 'YES') {
            $inAdtnl = array_merge($inAdtnl, ['include_null']);
        }
        if ($iar !== []) {
            $inAdtnl = array_merge($inAdtnl, $iar);
        }
        return $this->setArrayToSelect($selectOptions, $selectValue, $value['COLUMN_NAME'], $inAdtnl);
    }

    /**
     * Prepares the text output fields
     *
     * @param string $tbl
     * @param array $value
     * @return array
     */
    private function getFieldOutputTextPrerequisites($tbl, $value)
    {
        $foreignKeysArray = [];
        if (($tbl != 'user_rights') && ($value['COLUMN_NAME'] != 'eid')) {
            $database = $this->advCache['workingDatabase'];
            if (strpos($tbl, '`.`')) {
                $database = substr($tbl, 0, strpos($tbl, '`.`'));
            }
            $foreignKeysArray = $this->getForeignKeysToArray($database, $tbl, $value['COLUMN_NAME']);
        }
        return $foreignKeysArray;
    }

    /**
     * Returns an array with fields referenced by a Foreign key
     *
     * @param string $database
     * @param string $tblName
     * @param string|array $oCol Only column(s) considered
     * @return array
     */
    private function getForeignKeysToArray($database, $tblName, $oCol = '')
    {
        if (!isset($this->advCache['tableFKs'][$database][$tblName])) {
            $this->setTableForeignKeyCache($database, $this->fixTableSource($tblName));
        }
        $aRt = [];
        if (isset($this->advCache['tableFKs'][$database][$tblName])) {
            $cnm = ['COLUMN_NAME', 'full_array_key_numbered', 'REFERENCED_TABLE_SCHEMA', 'REFERENCED_TABLE_NAME'];
            foreach ($this->advCache['tableFKs'][$database][$tblName] as $val) {
                if ($val[$cnm[0]] == $oCol) {
                    $vlQ        = array_merge($val, ['LIMIT' => 2]);
                    $tFd        = $this->setMySQLquery2Server($this->getForeignKeysQuery($vlQ), $cnm[1])['result'];
                    $tgtFld     = '`' . ($tFd[0][$cnm[0]] == $val[$cnm[0]] ? $tFd[1][$cnm[0]] : $tFd[0][$cnm[0]]) . '`';
                    $aRt[$oCol] = [$this->glueDbTb($val[$cnm[2]], $val[$cnm[3]]), $val[$cnm[2]], $tgtFld];
                }
            }
        }
        return $aRt;
    }

    /**
     * create a Cache for given table to use it in many places
     *
     * @param string $tblSrc
     */
    protected function setTableCache($tblSrc)
    {
        $dat = $this->establishDatabaseAndTable($tblSrc);
        if (!isset($this->advCache['tableStructureCache'][$dat[0]][$dat[1]])) {
            $this->advCache['workingDatabase']                       = $dat[0];
            $this->advCache['tableStructureCache'][$dat[0]][$dat[1]] = $this->getMySQLlistColumns([
                'TABLE_SCHEMA' => $dat[0],
                'TABLE_NAME'   => $dat[1],
            ]);
            $this->setTableForeignKeyCache($dat[0], $dat[1]);
        }
    }

    /**
     *
     * @param string $dbName
     * @param string $tblName
     */
    private function setTableForeignKeyCache($dbName, $tblName)
    {
        $frgnKs = $this->getMySQLlistIndexes([
            'TABLE_SCHEMA'          => $dbName,
            'TABLE_NAME'            => $tblName,
            'REFERENCED_TABLE_NAME' => 'NOT NULL',
        ]);
        if (is_array($frgnKs)) {
            $this->advCache['tableFKs'][$dbName][$tblName] = $frgnKs;
            $this->advCache['FKcol'][$dbName][$tblName]    = array_column($frgnKs, 'COLUMN_NAME', 'CONSTRAINT_NAME');
        }
    }

}
