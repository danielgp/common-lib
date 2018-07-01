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
trait MySQLiByDanieGPtables
{

    use MySQLiByDanielGPstructures;

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
