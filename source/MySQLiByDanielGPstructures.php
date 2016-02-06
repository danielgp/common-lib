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

    use MySQLiByDanielGP,
        MySQLiByDanielGPqueries;

    /**
     * Ensures table has special quoes and DOT as final char
     * (if not empty, of course)
     *
     * @param string $referenceTable
     * @return string
     */
    private function correctTableWithQuotesAsFieldPrefix($referenceTable)
    {
        if ($referenceTable != '') {
            return '`' . str_replace('`', '', $referenceTable) . '`.';
        }
        return '';
    }

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
     * Return various informations (from predefined list) from the MySQL server
     *
     * @return int|array
     */
    private function getMySQLlistMultiple($returnChoice, $returnType, $additionalFeatures = null)
    {
        if (is_null($this->mySQLconnection)) {
            if ($returnType == 'value') {
                return null;
            }
            return [];
        }
        return $this->getMySQLlistMultipleFinal($returnChoice, $returnType, $additionalFeatures);
    }

    /**
     * Return various informations (from predefined list) from the MySQL server
     *
     * @return array
     */
    private function getMySQLlistMultipleFinal($returnChoice, $returnType, $additionalFeatures = null)
    {
        $queryByChoice = [
            'Columns'         => $this->sQueryMySqlColumns($additionalFeatures),
            'Databases'       => $this->sQueryMySqlActiveDatabases($additionalFeatures),
            'Engines'         => $this->sQueryMySqlActiveEngines($additionalFeatures),
            'Indexes'         => $this->sQueryMySqlIndexes($additionalFeatures),
            'ServerTime'      => $this->sQueryMySqlServerTime(),
            'Statistics'      => $this->sQueryMySqlStatistics($additionalFeatures),
            'Tables'          => $this->sQueryMySqlTables($additionalFeatures),
            'VariablesGlobal' => $this->sQueryMySqlGlobalVariables(),
        ];
        if (array_key_exists($returnChoice, $queryByChoice)) {
            return $this->setMySQLquery2Server($queryByChoice[$returnChoice], $returnType)['result'];
        }
        return [];
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
     * Return the time from the MySQL server
     *
     * @return string
     */
    protected function getMySQLserverTime()
    {
        return $this->getMySQLlistMultiple('ServerTime', 'value');
    }

    /**
     * Reads data from table into REQUEST super global
     *
     * @param string $tableName
     * @param array $filtersArray
     */
    protected function getRowDataFromTable($tableName, $filtersArray)
    {
        $query   = $this->sQueryRowsFromTable([$tableName, $this->setArrayToFilterValues($filtersArray)]);
        $rawData = $this->setMySQLquery2Server($query, 'array_pairs_key_value')['result'];
        if (!is_null($rawData)) {
            $this->initializeSprGlbAndSession();
            foreach ($rawData as $key => $value) {
                $vToSet = str_replace(['\\\\"', '\\"', "\\\\'", "\\'"], ['"', '"', "'", "'"], $value);
                $this->tCmnRequest->request->set($key, $vToSet);
            }
        }
    }

    /**
     * Builds an filter string from pair of key and value, where value is array
     *
     * @param string $key
     * @param array $value
     * @param string $referenceTable
     * @return string
     */
    private function setArrayLineArrayToFilter($key, $value, $referenceTable)
    {
        $filters2 = implode(', ', array_diff($value, ['']));
        if ($filters2 != '') {
            return '(' . $referenceTable . '`' . $key . '` IN ("'
                    . str_replace(',', '","', str_replace(["'", '"'], '', $filters2)) . '"))';
        }
        return '';
    }

    /**
     * Builds an filter string from pair of key and value, none array
     *
     * @param string $key
     * @param int|float|string $value
     * @return string
     */
    private function setArrayLineToFilter($key, $value)
    {
        $fTemp = '=';
        if ((substr($value, 0, 1) == '%') && (substr($value, -1) == '%')) {
            $fTemp = 'LIKE';
        }
        return '(`' . $key . '` ' . $fTemp . '"' . $value . '")';
    }

    /**
     * Transforms an array into usable filters
     *
     * @param array $entryArray
     * @param string $referenceTable
     * @return array
     */
    private function setArrayToFilterValues($entryArray, $referenceTable = '')
    {
        $filters  = [];
        $refTable = $this->correctTableWithQuotesAsFieldPrefix($referenceTable);
        foreach ($entryArray as $key => $value) {
            if (is_array($value)) {
                $filters[] = $this->setArrayLineArrayToFilter($key, $value, $refTable);
            } elseif (!in_array($value, ['', '%%'])) {
                $filters[] = $this->setArrayLineToFilter($key, $value);
            }
        }
        return implode(' AND ', array_diff($filters, ['']));
    }
}
