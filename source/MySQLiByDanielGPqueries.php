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

    use MySQLiByDanielGPqueriesBasic;

    /**
     * prepares the query to detect FKs
     *
     * @param array $value
     * @return string
     */
    protected function getForeignKeysQuery($value)
    {
        $flt = [
            'TABLE_SCHEMA' => $value['REFERENCED_TABLE_SCHEMA'],
            'TABLE_NAME'   => $value['REFERENCED_TABLE_NAME'],
            'DATA_TYPE'    => ['char', 'varchar', 'text'],
        ];
        if (array_key_exists('LIMIT', $value)) {
            $flt['LIMIT'] = $value['LIMIT'];
        }
        return $this->sQueryMySqlColumns($flt);
    }

    /**
     * Internal function to manage concatenation for filters
     *
     * @param array $filterValue
     * @return string
     */
    private function sGlueFilterValIntoWhereStr($filterValue)
    {
        if (is_array($filterValue)) {
            return 'IN ("' . implode('", "', $filterValue) . '")';
        }
        return $this->sGlueFilterValueIntoWhereStringFinal($filterValue);
    }

    private function sGlueFilterValueIntoWhereStringFinal($filterValue)
    {
        $kFields = [
            'CONNECTION_ID()|CURDATE()|CURRENT_USER|CURRENT_USER()|CURRENT_DATETIME|DATABASE()|NOW()|USER()',
            'IS NULL|IS NOT NULL',
            'NOT NULL|NULL',
        ];
        if (in_array($filterValue, explode('|', $kFields[0]))) {
            return '= ' . $filterValue;
        } elseif (in_array($filterValue, explode('|', $kFields[1]))) {
            return $filterValue;
        } elseif (in_array($filterValue, explode('|', $kFields[2]))) {
            return 'IS ' . $filterValue;
        }
        return '= "' . $filterValue . '"';
    }

    /**
     * Internal function to concatenate filters
     *
     * @param array $filters
     * @return string
     */
    private function sGlueFiltersIntoWhereArrayFilter($filters)
    {
        return '(' . implode(') AND (', $filters) . ')';
    }

    /**
     * Internal function to manage the filters passed to the query
     *
     * @param array $filterArray
     * @param string $tableToApplyFilterTo
     * @return string
     */
    private function sManageDynamicFilters($filterArray = null, $tableToApplyFilterTo = '')
    {
        if (is_null($filterArray)) {
            return '';
        }
        $fltr = [];
        if (is_array($filterArray)) {
            unset($filterArray['LIMIT']);
            foreach ($filterArray as $key => $value) {
                $fltr[] = '`' . $tableToApplyFilterTo . '`.`' . $key . '` ' . $this->sGlueFilterValIntoWhereStr($value);
            }
        }
        return $this->sManageDynamicFiltersFinal($fltr);
    }

    private function sManageDynamicFiltersFinal($filters)
    {
        if (count($filters) > 0) {
            $sReturn = ['WHERE', $this->sGlueFiltersIntoWhereArrayFilter($filters)];
            return implode(' ', $sReturn) . ' ';
        }
        return '';
    }

    private function sManageLimit($filters)
    {
        if (array_key_exists('LIMIT', $filters)) {
            return implode(' ', [
                'LIMIT',
                $filters['LIMIT'],
            ]);
        }
        return '';
    }

    protected function sQueryMySqlColumns($filterArray = null)
    {
        $filterArray = (is_array($filterArray) ? $filterArray : ['' => '']);
        return 'SELECT '
            . '`C`.`TABLE_SCHEMA`, '
            . $this->sQueryMySqlColumnsColumns() . ' '
            . 'FROM `information_schema`.`COLUMNS` `C` '
            . 'LEFT JOIN `information_schema`.`KEY_COLUMN_USAGE` `KCU` ON ((' . implode(') AND (', [
                '`C`.`TABLE_SCHEMA` = `KCU`.`TABLE_SCHEMA`',
                '`C`.`TABLE_NAME` = `KCU`.`TABLE_NAME`',
                '`C`.`COLUMN_NAME` = `KCU`.`COLUMN_NAME`',
            ]) . ')) '
            . $this->sManageDynamicFilters($filterArray, 'C')
            . 'GROUP BY `C`.`TABLE_SCHEMA`, `C`.`TABLE_NAME`, `C`.`COLUMN_NAME` '
            . 'ORDER BY `C`.`TABLE_SCHEMA`, `C`.`TABLE_NAME`, `C`.`ORDINAL_POSITION` '
            . $this->sManageLimit($filterArray)
            . ';';
    }

    private function sQueryMySqlColumnsColumns()
    {
        return '`C`.`TABLE_NAME`, `C`.`COLUMN_NAME`, `C`.`ORDINAL_POSITION` '
            . ', `C`.`COLUMN_DEFAULT`, `C`.`IS_NULLABLE`, `C`.`DATA_TYPE`, `C`.`CHARACTER_MAXIMUM_LENGTH` '
            . ', `C`.`NUMERIC_PRECISION`, `C`.`NUMERIC_SCALE`, `C`.`DATETIME_PRECISION` '
            . ', `C`.`CHARACTER_SET_NAME`, `C`.`COLLATION_NAME`, `C`.`COLUMN_TYPE` '
            . ', `C`.`COLUMN_KEY`, `C`.`COLUMN_COMMENT`, `C`.`EXTRA`';
    }

    /**
     * Query
     *
     * @param array $filterArray
     * @return string
     */
    protected function sQueryMySqlIndexes($filterArray = null)
    {
        return 'SELECT '
            . '`KCU`.`CONSTRAINT_SCHEMA`, '
            . $this->sQueryMySqlIndexesColumns() . ' '
            . 'FROM `information_schema`.`KEY_COLUMN_USAGE` `KCU` '
            . 'INNER JOIN `information_schema`.`COLUMNS` `C` ON ((' . implode(') AND (', [
                '`C`.`TABLE_SCHEMA` = `KCU`.`TABLE_SCHEMA`',
                '`C`.`TABLE_NAME` = `KCU`.`TABLE_NAME`',
                '`C`.`COLUMN_NAME` = `KCU`.`COLUMN_NAME`',
            ]) . ')) '
            . 'LEFT JOIN `information_schema`.`REFERENTIAL_CONSTRAINTS` `RC` ON ((' . implode(') AND (', [
                '`KCU`.`CONSTRAINT_SCHEMA` = `RC`.`CONSTRAINT_SCHEMA`',
                '`KCU`.`CONSTRAINT_NAME` = `RC`.`CONSTRAINT_NAME`',
            ]) . ')) '
            . $this->sManageDynamicFilters($filterArray, 'KCU')
            . 'ORDER BY `KCU`.`TABLE_SCHEMA`, `KCU`.`TABLE_NAME`'
            . $this->xtraSoring($filterArray, 'COLUMN_NAME') . ';';
    }

    private function sQueryMySqlIndexesColumns()
    {
        return '`KCU`.`CONSTRAINT_NAME`, `KCU`.`TABLE_SCHEMA`, `KCU`.`TABLE_NAME`, '
            . '`KCU`.`COLUMN_NAME`, `C`.`ORDINAL_POSITION` AS `COLUMN_POSITION`, `KCU`.`ORDINAL_POSITION`, '
            . '`KCU`.`POSITION_IN_UNIQUE_CONSTRAINT`, `KCU`.`REFERENCED_TABLE_SCHEMA`, '
            . '`KCU`.`REFERENCED_TABLE_NAME`, `KCU`.`REFERENCED_COLUMN_NAME`, '
            . '`RC`.`UPDATE_RULE`, `RC`.`DELETE_RULE`';
    }

    private function sQueryMySqlStatisticPattern($tblName, $lnkDbCol, $adtnlCol = null, $adtnlFltr = null)
    {
        $tblAls  = substr($tblName, 0, 1);
        $colName = (is_null($adtnlCol) ? $tblName : $adtnlFltr);
        return '(SELECT COUNT(*) AS `No. of records` FROM `information_schema`.`' . $tblName . '` `' . $tblAls . '` '
            . 'WHERE (`' . $tblAls . '`.`' . $lnkDbCol . '` = `S`.`SCHEMA_NAME`)'
            . (!is_null($adtnlCol) ? ' AND (`' . $tblAls . '`.`' . $adtnlCol . '` = "' . $adtnlFltr . '")' : '')
            . ') AS `' . ucwords(strtolower($colName)) . '`';
    }

    protected function sQueryMySqlStatistics($filterArray = null)
    {
        return 'SELECT '
            . '`S`.`SCHEMA_NAME`, '
            . $this->sQueryMySqlStatisticPattern('TABLES', 'TABLE_SCHEMA', 'TABLE_TYPE', 'BASE TABLE') . ', '
            . $this->sQueryMySqlStatisticPattern('TABLES', 'TABLE_SCHEMA', 'TABLE_TYPE', 'VIEW') . ', '
            . $this->sQueryMySqlStatisticPattern('COLUMNS', 'TABLE_SCHEMA') . ', '
            . $this->sQueryMySqlStatisticPattern('TRIGGERS', 'EVENT_OBJECT_SCHEMA') . ', '
            . $this->sQueryMySqlStatisticPattern('ROUTINES', 'ROUTINE_SCHEMA', 'ROUTINE_TYPE', 'Function') . ', '
            . $this->sQueryMySqlStatisticPattern('ROUTINES', 'ROUTINE_SCHEMA', 'ROUTINE_TYPE', 'Procedure') . ', '
            . $this->sQueryMySqlStatisticPattern('EVENTS', 'EVENT_SCHEMA') . ' '
            . 'FROM `information_schema`.`SCHEMATA` `S` '
            . 'WHERE (`S`.`SCHEMA_NAME` NOT IN ("information_schema", "mysql", "performance_schema", "sys")) '
            . str_replace('WHERE', 'AND', $this->sManageDynamicFilters($filterArray, 'S'))
            . 'ORDER BY `S`.`SCHEMA_NAME`;';
    }

    /**
     * Query to get list of tables
     *
     * @param array $filterArray
     * @return string
     */
    protected function sQueryMySqlTables($filterArray = null)
    {
        return 'SELECT '
            . '`T`.`TABLE_SCHEMA`, `T`.`TABLE_NAME`, `T`.`TABLE_TYPE`, `T`.`ENGINE`, `T`.`VERSION` '
            . ', `T`.`ROW_FORMAT`, `T`.`AUTO_INCREMENT`, `T`.`TABLE_COLLATION`, `T`.`CREATE_TIME` '
            . ', `T`.`CREATE_OPTIONS`, `T`.`TABLE_COMMENT` '
            . 'FROM `information_schema`.`TABLES` `T` '
            . $this->sManageDynamicFilters($filterArray, 'T')
            . $this->xtraSoring($filterArray, 'TABLE_SCHEMA') . ';';
    }

    private function xtraSoring($filterArray, $filterValueToDecide)
    {
        $defaults = [
            'COLUMN_NAME'  => [
                ', `C`.`ORDINAL_POSITION`, `KCU`.`CONSTRAINT_NAME`',
                '',
            ],
            'TABLE_SCHEMA' => [
                'ORDER BY `T`.`TABLE_SCHEMA`, `T`.`TABLE_NAME`',
                'ORDER BY `T`.`TABLE_NAME`',
            ],
        ];
        if (!is_null($filterArray) && is_array($filterArray) && array_key_exists($filterValueToDecide, $filterArray)) {
            return $defaults[$filterValueToDecide][1];
        }
        return $defaults[$filterValueToDecide][0];
    }

}
