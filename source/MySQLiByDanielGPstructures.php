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
 * Useful functions to get quick MySQL content
 *
 * @author Daniel Popiniuc
 */
trait MySQLiByDanielGPstructures
{

    use MySQLiByDanielGP,
        MySQLiByDanielGPqueries;

    /**
     * Ensures table has special quotes and DOT as final char
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
     * Prepares the output of text fields defined w. FKs
     *
     * @param array $foreignKeysArray
     * @param array $value
     * @param array $iar
     * @return string
     */
    protected function getFieldOutputTextFK($foreignKeysArray, $value, $iar)
    {
        $query   = $this->sQueryGenericSelectKeyValue([
            '`' . $value['COLUMN_NAME'] . '`',
            $foreignKeysArray[$value['COLUMN_NAME']][2],
            $foreignKeysArray[$value['COLUMN_NAME']][0]
        ]);
        $inAdtnl = ['size' => 1];
        if ($value['IS_NULLABLE'] == 'YES') {
            $inAdtnl = array_merge($inAdtnl, ['include_null']);
        }
        if ($iar !== []) {
            $inAdtnl = array_merge($inAdtnl, $iar);
        }
        $slct = [
            'Options' => $this->setMySQLquery2Server($query, 'array_key_value')['result'],
            'Value'   => $this->getFieldValue($value),
        ];
        return $this->setArrayToSelect($slct['Options'], $slct['Value'], $value['COLUMN_NAME'], $inAdtnl);
    }

    /**
     * Prepares the output of text fields w/o FKs
     *
     * @param array $value
     * @param array $iar
     * @return string
     */
    protected function getFieldOutputTextNonFK($value, $iar)
    {
        $fldNos  = $this->setFieldNumbers($value);
        $inAdtnl = [
            'type'      => ($value['COLUMN_NAME'] == 'password' ? 'password' : 'text'),
            'name'      => $value['COLUMN_NAME'],
            'id'        => $value['COLUMN_NAME'],
            'size'      => min(30, $fldNos['M']),
            'maxlength' => min(255, $fldNos['M']),
            'value'     => $this->getFieldValue($value),
        ];
        if ($iar !== []) {
            $inAdtnl = array_merge($inAdtnl, $iar);
        }
        return $this->setStringIntoShortTag('input', $inAdtnl);
    }

    /**
     * Return the list of Tables from the MySQL server
     *
     * @return array
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
     * Return various information (from predefined list) from the MySQL server
     *
     * @param string $rChoice
     * @param string $returnType
     * @param array $additionalFeatures
     * @return array
     */
    private function getMySQLlistMultipleFinal($rChoice, $returnType, $additionalFeatures = null)
    {
        $qByChoice = [
            'Columns'         => ['sQueryMySqlColumns', $additionalFeatures],
            'Databases'       => ['sQueryMySqlActiveDatabases', $additionalFeatures],
            'Engines'         => ['sQueryMySqlActiveEngines', $additionalFeatures],
            'Indexes'         => ['sQueryMySqlIndexes', $additionalFeatures],
            'ServerTime'      => ['sQueryMySqlServerTime'],
            'Statistics'      => ['sQueryMySqlStatistics', $additionalFeatures],
            'Tables'          => ['sQueryMySqlTables', $additionalFeatures],
            'VariablesGlobal' => ['sQueryMySqlGlobalVariables'],
        ];
        if (array_key_exists($rChoice, $qByChoice)) {
            return $this->setMySQLquery2Server($this->transformStrIntoFn($qByChoice, $rChoice), $returnType)['result'];
        }
        return [];
    }

    /**
     * Return the list of Tables from the MySQL server
     *
     * @return array
     */
    protected function getMySQLlistTables($filterArray = null)
    {
        return $this->getMySQLlistMultiple('Tables', 'full_array_key_numbered', $filterArray);
    }

    /**
     * Return the time from the MySQL server
     *
     * @return array
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
     * @return string
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

    private function transformStrIntoFn($queryByChoice, $rChoice)
    {
        $query = null;
        switch (count($queryByChoice[$rChoice])) {
            case 1:
                $query = call_user_func([$this, $queryByChoice[$rChoice][0]]);
                break;
            case 2:
                $query = call_user_func([$this, $queryByChoice[$rChoice][0]], $queryByChoice[$rChoice][1]);
                break;
        }
        return $query;
    }

}
