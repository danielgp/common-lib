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

    private function sCleanParameters(&$parameters)
    {
        if (is_array($parameters)) {
            $tmpArray = [];
            foreach ($parameters as &$value) {
                $tmpArray[] = filter_var($value, FILTER_SANITIZE_STRING);
            }
            $parameters = $tmpArray;
        } else {
            $parameters = filter_var($parameters, FILTER_SANITIZE_STRING);
        }
    }

    /**
     * Internal function to manage concatenation for filters
     *
     * @param type $filterValue
     * @return string
     */
    private function sGlueFilterValueIntoWhereString($filterValue)
    {
        if (is_array($filterValue)) {
            return 'IN ("' . implode('", "', $filterValue) . '")';
        }
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
     * @return type
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
        $filters = [];
        if (!is_null($filterArray) && is_array($filterArray)) {
            foreach ($filterArray as $key => $value) {
                $filters[] = '`' . $tableToApplyFilterTo . '`.`' . $key . '` '
                        . $this->sGlueFilterValueIntoWhereString($value);
            }
        }
        if (count($filters) > 0) {
            return 'WHERE ' . $this->sGlueFiltersIntoWhereArrayFilter($filters) . ' ';
        }
        return '';
    }

    /**
     * Query to list Databases
     *
     * @param type $excludeSystemDbs
     * @return type
     */
    protected function sQueryMySqlActiveDatabases($excludeSystemDbs = true)
    {
        $stdDbs = ['information_schema', 'mysql', 'performance_schema', 'sys'];
        return 'SELECT '
                . '`SCHEMA_NAME` As `Db`, '
                . '`DEFAULT_CHARACTER_SET_NAME` AS `DbCharset`, '
                . '`DEFAULT_COLLATION_NAME` AS `DbCollation` '
                . 'FROM `information_schema`.`SCHEMATA` '
                . ($excludeSystemDbs ? 'WHERE `SCHEMA_NAME` NOT IN ("' . implode('", "', $stdDbs) . '") ' : '')
                . 'GROUP BY `SCHEMA_NAME`;';
    }

    /**
     * Query to list MySQL engines
     *
     * @param string $onlyActiveOnes
     * @return type
     */
    protected function sQueryMySqlActiveEngines($onlyActiveOnes = true)
    {
        return 'SELECT '
                . '`ENGINE` AS `Engine`, '
                . '`SUPPORT` AS `Support`, '
                . '`COMMENT` AS `Comment` '
                . 'FROM `information_schema`.`ENGINES` '
                . ($onlyActiveOnes ? 'WHERE (`SUPPORT` IN ("DEFAULT", "YES")) ' : '')
                . 'GROUP BY `ENGINE`;';
    }

    protected function sQueryMySqlColumns($filterArray = null)
    {
        return 'SELECT `C`.`TABLE_SCHEMA` '
                . ', `C`.`TABLE_NAME` '
                . ', `C`.`COLUMN_NAME` '
                . ', `C`.`ORDINAL_POSITION` '
                . ', `C`.`COLUMN_DEFAULT` '
                . ', `C`.`IS_NULLABLE` '
                . ', `C`.`DATA_TYPE` '
                . ', `C`.`CHARACTER_MAXIMUM_LENGTH` '
                . ', `C`.`NUMERIC_PRECISION` '
                . ', `C`.`NUMERIC_SCALE` '
                . ', `C`.`DATETIME_PRECISION` '
                . ', `C`.`CHARACTER_SET_NAME` '
                . ', `C`.`COLLATION_NAME` '
                . ', `C`.`COLUMN_TYPE` '
                . ', `C`.`COLUMN_KEY` '
                . ', `C`.`COLUMN_COMMENT` '
                . ', `C`.`EXTRA` '
                . 'FROM `information_schema`.`COLUMNS` `C` '
                . 'LEFT JOIN `information_schema`.`KEY_COLUMN_USAGE` `KCU` ON ((' . implode(') AND (', [
                    '`C`.`TABLE_SCHEMA` = `KCU`.`TABLE_SCHEMA`',
                    '`C`.`TABLE_NAME` = `KCU`.`TABLE_NAME`',
                    '`C`.`COLUMN_NAME` = `KCU`.`COLUMN_NAME`',
                ]) . ')) '
                . $this->sManageDynamicFilters($filterArray, 'C')
                . 'GROUP BY `C`.`TABLE_SCHEMA`, `C`.`TABLE_NAME`, `C`.`COLUMN_NAME` '
                . 'ORDER BY `C`.`TABLE_SCHEMA`, `C`.`TABLE_NAME`, `C`.`ORDINAL_POSITION`;';
    }

    protected function sQueryGenericSelectKeyValue($parameters)
    {
        $this->sCleanParameters($parameters);
        return 'SELECT' . $parameters[0] . ',' . $parameters[1] . 'FROM' . $parameters[2]
                . 'GROUP BY' . $parameters[1] . ';';
    }

    /**
     * Query to list Global Variables
     *
     * @return string
     */
    protected function sQueryMySqlGlobalVariables()
    {
        return 'SHOW GLOBAL VARIABLES;';
    }

    /**
     * Query
     *
     * @param array $filterArray
     * @return string
     */
    protected function sQueryMySqlIndexes($filterArray = null)
    {
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

    /**
     * The MySQL server time
     *
     * @return string
     */
    protected function sQueryMySqlServerTime()
    {
        return 'SELECT NOW();';
    }

    protected function sQueryMySqlStatisticTSFSPE()
    {
        return '(SELECT COUNT(*) AS `No. of records` FROM `information_schema`.`TRIGGERS` `T` '
                . 'WHERE (`T`.`EVENT_OBJECT_SCHEMA` = `S`.`SCHEMA_NAME`)) '
                . 'AS `Triggers`, '
                . '(SELECT COUNT(*) AS `No. of records` FROM `information_schema`.`ROUTINES` `R` '
                . 'WHERE (`R`.`ROUTINE_SCHEMA` = `S`.`SCHEMA_NAME`) AND (`R`.`ROUTINE_TYPE` = "Function")) '
                . 'AS `Stored Functions`, '
                . '(SELECT COUNT(*) AS `No. of records` FROM `information_schema`.`ROUTINES` `R` '
                . 'WHERE (`R`.`ROUTINE_SCHEMA` = `S`.`SCHEMA_NAME`) AND (`R`.`ROUTINE_TYPE` = "Procedure")) '
                . 'AS `Stored Procedure`, '
                . '(SELECT COUNT(*) AS `No. of records` FROM `information_schema`.`EVENTS` `E` '
                . 'WHERE (`E`.`EVENT_SCHEMA` = `S`.`SCHEMA_NAME`)) '
                . 'AS `Events` ';
    }

    protected function sQueryMySqlStatisticTVC()
    {
        return '(SELECT COUNT(*) AS `No. of records` FROM `information_schema`.`TABLES` `T` '
                . 'WHERE (`T`.`TABLE_SCHEMA` = `S`.`SCHEMA_NAME`) AND (`T`.`TABLE_TYPE` = "BASE TABLE")) '
                . 'AS `Tables`, '
                . '(SELECT COUNT(*) AS `No. of records` FROM `information_schema`.`TABLES` `T` '
                . 'WHERE (`T`.`TABLE_SCHEMA` = `S`.`SCHEMA_NAME`) AND (`T`.`TABLE_TYPE` = "VIEW")) '
                . 'AS `Views`, '
                . '(SELECT COUNT(*) AS `No. of records` FROM `information_schema`.`COLUMNS` `C` '
                . 'WHERE (`C`.`TABLE_SCHEMA` = `S`.`SCHEMA_NAME`)) '
                . 'AS `Columns`, ';
    }

    protected function sQueryMySqlStatistics($filterArray = null)
    {
        return 'SELECT '
                . '`S`.`SCHEMA_NAME`, '
                . $this->sQueryMySqlStatisticTVC()
                . $this->sQueryMySqlStatisticTSFSPE()
                . 'FROM `information_schema`.`SCHEMATA` `S` '
                . 'WHERE (`S`.`SCHEMA_NAME` NOT IN ("information_schema", "mysql", "performance_schema", "sys")) '
                . str_replace('WHERE|AND', $this->sManageDynamicFilters($filterArray, 'S'))
                . 'ORDER BY `S`.`SCHEMA_NAME`;';
    }

    /**
     * Query to get list of tables
     *
     * @param type $filterArray
     * @return string
     */
    protected function sQueryMySqlTables($filterArray = null)
    {
        return 'SELECT `T`.`TABLE_SCHEMA` '
                . ', `T`.`TABLE_NAME` '
                . ', `T`.`TABLE_TYPE` '
                . ', `T`.`ENGINE` '
                . ', `T`.`VERSION` '
                . ', `T`.`ROW_FORMAT` '
                . ', `T`.`AUTO_INCREMENT` '
                . ', `T`.`TABLE_COLLATION` '
                . ', `T`.`CREATE_TIME` '
                . ', `T`.`CREATE_OPTIONS` '
                . ', `T`.`TABLE_COMMENT` '
                . 'FROM `information_schema`.`TABLES` `T` '
                . $this->sManageDynamicFilters($filterArray, 'T')
                . $this->xtraSoring($filterArray, 'TABLE_SCHEMA')
                . ';';
    }

    /**
     * Get all the row details from a table based on given filter
     *
     * @param array $parameters
     * @return string
     */
    protected function sQueryRowsFromTable($parameters)
    {
        $this->sCleanParameters($parameters);
        return 'SELECT * '
                . 'FROM `' . $parameters[0] . '` '
                . 'WHERE ' . $parameters[1] . ';';
    }

    protected function sQueryToDeleteSingleIdentifier($parameters)
    {
        $this->sCleanParameters($parameters);
        return 'DELETE '
                . 'FROM `' . $parameters[0] . '` '
                . 'WHERE `' . $parameters[1] . '` = "' . $parameters[2] . '";';
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
