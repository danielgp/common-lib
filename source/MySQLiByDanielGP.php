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
trait MySQLiByDanielGP
{

    use DomComponentsByDanielGP,
        MySQLiMultipleExecution,
        MySQLiByDanielGPqueries,
        MySQLiByDanielGPtypes;

    /**
     * Intiates connection to MySQL
     *
     * @param array $mySQLconfig
     *
     * $mySQLconfig           = [
     * 'host'     => MYSQL_HOST,
     * 'port'     => MYSQL_PORT,
     * 'username' => MYSQL_USERNAME,
     * 'password' => MYSQL_PASSWORD,
     * 'database' => MYSQL_DATABASE,
     * ];
     */
    protected function connectToMySql($mySQLconfig)
    {
        if (is_null($this->mySQLconnection)) {
            extract($mySQLconfig);
            $this->mySQLconnection = new \mysqli($host, $username, $password, $database, $port);
            if (is_null($this->mySQLconnection->connect_error)) {
                return '';
            }
            $this->mySQLconnection = null;
            $erNo                  = $this->mySQLconnection->connect_errno;
            $erMsg                 = $this->mySQLconnection->connect_error;
            $msg                   = $this->lclMsgCmn('i18n_Feedback_ConnectionError');
            return sprintf($msg, $erNo, $erMsg, $host, $port, $username, $database);
        }
    }

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
    protected function getMySQLStatistics($filterArray = null)
    {
        return $this->getMySQLlistMultiple('Statistics', 'full_array_key_numbered', $filterArray);
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
     * Provides a detection if given Query does contain a Parameter
     * that may require statement processing later on
     *
     * @param string $sQuery
     * @param string $paramIdentifier
     * @return boolean
     */
    protected function getMySQLqueryWithParameterIdentifier($sQuery, $paramIdentifier)
    {
        $sReturn = true;
        if (strpos($sQuery, $paramIdentifier) === false) {
            $sReturn = false;
        }
        return $sReturn;
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

    private function setFldLmts($colType, $loLmt, $upLmt, $szN, $szUS)
    {
        $aReturn = ['m' => $loLmt, 'M' => $upLmt, 'l' => $szN];
        if (strpos($colType, 'unsigned') !== false) {
            $aReturn = ['m' => 0, 'M' => ($upLmt - $loLmt), 'l' => $szUS];
        }
        return $aReturn;
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
        $sReturn = null;
        if (array_key_exists($cTp, $xct)) {
            $sReturn = $this->setFldLmts($cTp, $xct[$cTp]['l'], $xct[$cTp]['L'], $xct[$cTp]['s'], $xct[$cTp]['sUS']);
        }
        return $sReturn;
    }

    /**
     * Transmit Query to MySQL server and get results back
     *
     * @param string $sQuery
     * @param string $sReturnType
     * @param array $ftrs
     * @return boolean|array|string
     */
    protected function setMySQLquery2Server($sQuery, $sReturnType = null, $ftrs = null)
    {
        if (is_null($sReturnType)) {
            $this->mySQLconnection->query(html_entity_decode($sQuery));
            return '';
        } elseif (is_null($this->mySQLconnection)) {
            return ['customError' => $this->lclMsgCmn('i18n_MySQL_ConnectionNotExisting'), 'result' => null];
        }
        $result = $this->mySQLconnection->query(html_entity_decode($sQuery));
        if ($result) {
            return $this->setMySQLquery2ServerConnected(['Result' => $result, 'RType' => $sReturnType, 'F' => $ftrs]);
        }
        $erM  = [$this->mySQLconnection->errno, $this->mySQLconnection->error];
        $cErr = sprintf($this->lclMsgCmn('i18n_MySQL_QueryError'), $erM[0], $erM[1]);
        return ['customError' => $cErr, 'result' => null];
    }

    /**
     * Turns a raw query result into various structures
     * based on different predefined $parameters['returnType'] value
     *
     * @param array $parameters
     * @return array as ['customError' => '...', 'result' => '...']
     */
    private function setMySQLquery2ServerByPattern($parameters)
    {
        $aReturn = $parameters['return'];
        $vld     = $this->setMySQLqueryValidateInputs($parameters);
        if ($vld[1] !== '') {
            return ['customError' => $vld[1], 'result' => ''];
        } elseif (in_array($parameters['returnType'], ['array_key_value', 'array_key_value2', 'array_key2_value'])) {
            return ['customError' => $vld[1], 'result' => $this->setMySQLquery2ServerByPatternKey($parameters)];
        }
        $counter2 = 0;
        for ($counter = 0; $counter < $parameters['NoOfRows']; $counter++) {
            $line = $parameters['QueryResult']->fetch_row();
            switch ($parameters['returnType']) {
                case 'array_first_key_rest_values':
                    $finfo         = $parameters['QueryResult']->fetch_fields();
                    $columnCounter = 0;
                    foreach ($finfo as $value) {
                        if ($columnCounter != 0) {
                            $aReturn['result'][$line[0]][$value->name] = $line[$columnCounter];
                        }
                        $columnCounter++;
                    }
                    break;
                case 'array_numbered':
                    $aReturn['result'][] = $line[0];
                    break;
                case 'array_pairs_key_value':
                    $finfo               = $parameters['QueryResult']->fetch_fields();
                    $columnCounter       = 0;
                    foreach ($finfo as $value) {
                        $aReturn['result'][$value->name] = $line[$columnCounter];
                        $columnCounter++;
                    }
                    break;
                case 'full_array_key_numbered':
                    $finfo         = $parameters['QueryResult']->fetch_fields();
                    $columnCounter = 0;
                    foreach ($finfo as $value) {
                        $aReturn['result'][$counter2][$value->name] = $line[$columnCounter];
                        $columnCounter++;
                    }
                    $counter2++;
                    break;
                case 'full_array_key_numbered_with_record_number_prefix':
                    $parameters['prefix'] = 'RecordNo';
                // intentionally left open
                case 'full_array_key_numbered_with_prefix':
                    $finfo                = $parameters['QueryResult']->fetch_fields();
                    $columnCounter        = 0;
                    foreach ($finfo as $value) {
                        $aReturn['result'][$parameters['prefix']][$counter2][$value->name] = $line[$columnCounter];
                        $columnCounter++;
                    }
                    $counter2++;
                    break;
                case 'value':
                    $aReturn['result'] = $line[0];
                    break;
            }
        }
        return ['customError' => '', 'result' => $aReturn['result']];
    }

    private function setMySQLquery2ServerByPatternKey($parameters)
    {
        $aReturn = [];
        for ($counter = 0; $counter < $parameters['NoOfRows']; $counter++) {
            $line = $parameters['QueryResult']->fetch_row();
            switch ($parameters['returnType']) {
                case 'array_key_value':
                    $aReturn[$line[0]]                  = $line[1];
                    break;
                case 'array_key_value2':
                    $aReturn[$line[0]][]                = $line[1];
                    break;
                case 'array_key2_value':
                    $aReturn[$line[0] . '@' . $line[1]] = $line[1];
                    break;
            }
        }
        return $aReturn;
    }

    protected function setMySQLquery2ServerConnected($inArray)
    {
        if ($inArray['RType'] == 'id') {
            return ['customError' => '', 'result' => $this->mySQLconnection->insert_id];
        } elseif ($inArray['RType'] == 'lines') {
            return ['result' => $inArray['Result']->num_rows, 'customError' => ''];
        }
        $parameters = [
            'NoOfColumns' => $inArray['Result']->field_count,
            'NoOfRows'    => $inArray['Result']->num_rows,
            'QueryResult' => $inArray['Result'],
            'returnType'  => $inArray['RType'],
            'return'      => ['customError' => '', 'result' => null]
        ];
        if (substr($inArray['RType'], -6) == 'prefix') {
            $parameters['prefix'] = $inArray['F']['prefix'];
        }
        return $this->setMySQLquery2ServerByPattern($parameters);
    }

    private function setMySQLqueryValidateInputs($prm)
    {
        $rMap = $this->setMySQLqueryValidationMap();
        if (array_key_exists($prm['returnType'], $rMap)) {
            $elC = [$prm['NoOfRows'], $rMap[$prm['returnType']]['r'][0], $rMap[$prm['returnType']]['r'][1]];
            if (filter_var($elC[0], FILTER_VALIDATE_INT, ['min_range' => $elC[1], 'max_range' => $elC[2]]) === false) {
                $msg = $this->lclMsgCmn('i18n_MySQL_QueryResultExpected' . $rMap[$prm['returnType']][2]);
                return [false, sprintf($msg, $prm['NoOfColumns'])];
            }
            $elR = [$prm['NoOfColumns'], $rMap[$prm['returnType']]['c'][0], $rMap[$prm['returnType']]['c'][1]];
            if (filter_var($elR[0], FILTER_VALIDATE_INT, ['min_range' => $elR[1], 'max_range' => $elR[2]])) {
                return [true, ''];
            }
            $msg = $this->lclMsgCmn('i18n_MySQL_QueryResultExpected' . $rMap[$prm['returnType']][1]);
            return [false, sprintf($msg, $prm['NoOfColumns'])];
        }
        return [false, $prm['returnType'] . ' is not defined!'];
    }

    private function setMySQLqueryValidationMap()
    {
        $lngKey = 'full_array_key_numbered_with_record_number_prefix';
        return [
            'array_first_key_rest_values'         => ['r' => [1, 999999], 'c' => [2, 99], 'AtLeast2ColsResultedOther'],
            'array_key_value'                     => ['r' => [1, 999999], 'c' => [2, 2], '2ColumnsResultedOther'],
            'array_key_value2'                    => ['r' => [1, 999999], 'c' => [2, 2], '2ColumnsResultedOther'],
            'array_key2_value'                    => ['r' => [1, 999999], 'c' => [2, 2], '2ColumnsResultedOther'],
            'array_numbered'                      => ['r' => [1, 999999], 'c' => [1, 1], '1ColumnResultedOther'],
            'array_pairs_key_value'               => ['r' => [1, 1], 'c' => [1, 99], '1RowManyColumnsResultedOther'],
            'full_array_key_numbered'             => ['r' => [1, 999999], 'c' => [1, 99], '1OrMoreRows0Resulted'],
            'full_array_key_numbered_with_prefix' => ['r' => [1, 999999], 'c' => [1, 99], '1OrMoreRows0Resulted'],
            $lngKey                               => ['r' => [1, 999999], 'c' => [1, 99], '1OrMoreRows0Resulted'],
            'value'                               => ['r' => [1, 1], 'c' => [1, 1], '1ResultedOther'],
        ];
    }
}
