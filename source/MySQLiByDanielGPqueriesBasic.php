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
trait MySQLiByDanielGPqueriesBasic
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

    protected function sQueryGenericSelectKeyValue($parameters)
    {
        $this->sCleanParameters($parameters);
        return implode(' ', [
            'SELECT',
            $parameters[0] . ', ' . $parameters[1],
            'FROM',
            $parameters[2],
            'GROUP BY',
            $parameters[0],
            'ORDER BY',
            $parameters[1] . ';'
        ]);
    }

    /**
     * Query to list Databases
     *
     * @param boolean $excludeSystemDbs
     * @return string
     */
    protected function sQueryMySqlActiveDatabases($excludeSystemDbs = true)
    {
        $sDBs = 'WHERE '
            . '`SCHEMA_NAME` NOT IN ("'
            . implode('", "', ['information_schema', 'mysql', 'performance_schema', 'sys']) . '") ';
        return 'SELECT '
            . '`SCHEMA_NAME` As `Db`, `DEFAULT_CHARACTER_SET_NAME` AS `DbCharset`, '
            . '`DEFAULT_COLLATION_NAME` AS `DbCollation` '
            . 'FROM `information_schema`.`SCHEMATA` '
            . ($excludeSystemDbs ? $sDBs : '')
            . 'GROUP BY `SCHEMA_NAME`;';
    }

    /**
     * Query to list MySQL engines
     *
     * @param boolean $onlyActiveOnes
     * @return string
     */
    protected function sQueryMySqlActiveEngines($onlyActiveOnes = true)
    {
        return 'SELECT '
            . '`ENGINE` AS `Engine`, `SUPPORT` AS `Support`, `COMMENT` AS `Comment` '
            . 'FROM `information_schema`.`ENGINES` '
            . ($onlyActiveOnes ? 'WHERE (`SUPPORT` IN ("DEFAULT", "YES")) ' : '')
            . 'GROUP BY `ENGINE`;';
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
     * The MySQL server time
     *
     * @return string
     */
    protected function sQueryMySqlServerTime()
    {
        return 'SELECT NOW();';
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

}
