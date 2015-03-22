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
 * Queries for the MySQL module
 *
 * @author Daniel Popiniuc
 */
trait MySQLiByDanielGPqueries
{

    private function sGlueFilterValueIntoWhereString($filterValue)
    {
        if (is_array($filterValue)) {
            $sReturn = 'IN ("' . implode('", "', $filterValue) . '")';
        } else {
            $sReturn = '= "' . $filterValue . '"';
        }
        return $sReturn;
    }

    private function sGlueFiltersIntoWhereArrayFilter($filters)
    {
        return '(' . implode(') AND (', $filters) . ')';
    }

    private function sManageDynamicFilters($filterArray = null, $tableToApplyFilterTo = '')
    {
        $filters = [];
        if (!is_null($filterArray) && is_array($filterArray)) {
            foreach ($filterArray as $key => $value) {
                $filters[] = '`' . $tableToApplyFilterTo . '`.`' . $key . '` '
                        . $this->sGlueFilterValueIntoWhereString($value);
            }
        }
        if (count($filters) == 0) {
            $finalFilter = '';
        } else {
            $finalFilter = implode(' ', [
                        'WHERE',
                        $this->sGlueFiltersIntoWhereArrayFilter($filters),
                    ]) . ' ';
        }
        return $finalFilter;
    }

    protected function sQueryMySqlActiveDatabases($excludeSystemDatabases = true)
    {
        if ($excludeSystemDatabases) {
            $filterChoice = 'WHERE `SCHEMA_NAME` NOT IN ("information_schema", "mysql", "performance_schema", "sys") ';
        } else {
            $filterChoice = '';
        }
        return 'SELECT '
                . '`SCHEMA_NAME` As `Db`, '
                . '`DEFAULT_CHARACTER_SET_NAME` AS `DbCharset`, '
                . '`DEFAULT_COLLATION_NAME` AS `DbCollation` '
                . 'FROM `information_schema`.`SCHEMATA` '
                . $filterChoice
                . 'GROUP BY `SCHEMA_NAME`;';
    }

    protected function sQueryMySqlActiveEngines($onlyActiveOnes = true)
    {
        if ($onlyActiveOnes) {
            $finalFilter = 'WHERE (`SUPPORT` IN ("DEFAULT", "YES")) ';
        } else {
            $finalFilter = '';
        }
        return 'SELECT '
                . '`ENGINE` AS `Engine`, '
                . '`SUPPORT` AS `Support`, '
                . '`COMMENT` AS `Comment` '
                . 'FROM `information_schema`.`ENGINES` '
                . $finalFilter
                . 'GROUP BY `ENGINE`;';
    }

    protected function sQueryMySqlGlobalVariables()
    {
        return 'SHOW GLOBAL VARIABLES;';
    }

    protected function sQueryMySqlIndexes($filterArray = null)
    {
        $xtraSorting = ', `C`.`ORDINAL_POSITION`, `KCU`.`CONSTRAINT_NAME`';
        if (!is_null($filterArray) && is_array($filterArray)) {
            if (in_array('COLUMN_NAME', array_keys($filterArray))) {
                $xtraSorting = '';
            }
        }
        return 'SELECT `KCU`.`CONSTRAINT_SCHEMA` '
                . ', `KCU`.`CONSTRAINT_NAME` '
                . ', `KCU`.`TABLE_SCHEMA` '
                . ', `KCU`.`TABLE_NAME` '
                . ', `KCU`.`COLUMN_NAME` '
                . ', `C`.`ORDINAL_POSITION` AS `COLUMN_POSITION` '
                . ', `KCU`.`ORDINAL_POSITION` '
                . ', `KCU`.`POSITION_IN_UNIQUE_CONSTRAINT` '
                . ', `KCU`.`REFERENCED_TABLE_SCHEMA` '
                . ', `KCU`.`REFERENCED_TABLE_NAME` '
                . ', `KCU`.`REFERENCED_COLUMN_NAME` '
                . ', `RC`.`UPDATE_RULE` '
                . ', `RC`.`DELETE_RULE` '
                . 'FROM `information_schema`.`KEY_COLUMN_USAGE` `KCU` '
                . 'INNER JOIN `information_schema`.`COLUMNS` `C` ON (' . implode(') AND (', [
                    '`C`.`TABLE_SCHEMA` = `KCU`.`TABLE_SCHEMA`',
                    '`C`.`TABLE_NAME` = `KCU`.`TABLE_NAME`',
                    '`C`.`COLUMN_NAME` = `KCU`.`COLUMN_NAME`',
                ]) . ') '
                . 'LEFT JOIN `information_schema`.`REFERENTIAL_CONSTRAINTS` `RC` ON (' . implode(') AND (', [
                    '`KCU`.`CONSTRAINT_SCHEMA` = `RC`.`CONSTRAINT_SCHEMA`',
                    '`KCU`.`CONSTRAINT_NAME` = `RC`.`CONSTRAINT_NAME`',
                ]) . ') '
                . $this->sManageDynamicFilters($filterArray, 'KCU')
                . 'ORDER BY `KCU`.`TABLE_SCHEMA`, `KCU`.`TABLE_NAME`' . $xtraSorting . ';';
    }

    protected function sQueryMySqlServerTime()
    {
        return 'SELECT NOW();';
    }

    protected function sQueryMySqlTables($filterArray = null)
    {
        $xtraSorting = 'ORDER BY `TABLE_SCHEMA`, `TABLE_NAME`';
        if (!is_null($filterArray) && is_array($filterArray)) {
            if (in_array('TABLE_SCHEMA', array_keys($filterArray))) {
                $xtraSorting = 'ORDER BY `TABLE_NAME`';
            }
        }
        return 'SELECT `TABLE_SCHEMA` '
                . ', `TABLE_NAME` '
                . ', `TABLE_TYPE` '
                . ', `ENGINE` '
                . ', `VERSION` '
                . ', `ROW_FORMAT` '
                . ', `TABLE_COLLATION` '
                . ', `TABLE_COMMENT` '
                . 'FROM `information_schema`.`TABLES` `T` '
                . $this->sManageDynamicFilters($filterArray, 'T')
                . $xtraSorting . ';';
    }
}
