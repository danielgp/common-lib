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
            $erNo                  = $this->mySQLconnection->connect_errno;
            $erMsg                 = $this->mySQLconnection->connect_error;
            $this->mySQLconnection = null;
            $msg                   = $this->lclMsgCmn('i18n_Feedback_ConnectionError');
            return sprintf($msg, $erNo, $erMsg, $host, $port, $username, $database);
        }
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
     * @return string
     */
    private function getMySQLlistMultiple($returnChoice, $returnType, $additionalFeatures = null)
    {
        if (is_null($this->mySQLconnection)) {
            if ($returnType == 'value') {
                return null;
            }
            return [];
        }
        $query = '';
        switch ($returnChoice) {
            case 'Columns':
                $query = $this->sQueryMySqlColumns($additionalFeatures);
                break;
            case 'Databases':
                $query = $this->sQueryMySqlActiveDatabases($additionalFeatures);
                break;
            case 'Engines':
                $query = $this->sQueryMySqlActiveEngines($additionalFeatures);
                break;
            case 'Indexes':
                $query = $this->sQueryMySqlIndexes($additionalFeatures);
                break;
            case 'ServerTime':
                $query = $this->sQueryMySqlServerTime();
                break;
            case 'Statistics':
                $query = $this->sQueryMySqlStatistics($additionalFeatures);
                break;
            case 'Tables':
                $query = $this->sQueryMySqlTables($additionalFeatures);
                break;
            case 'VariablesGlobal':
                $query = $this->sQueryMySqlGlobalVariables();
                break;
        }
        return $this->setMySQLquery2Server($query, $returnType)['result'];
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
                $this->tCmnRequest->request->get($key, $vToSet);
            }
        }
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
        $filters = '';
        if ($referenceTable != '') {
            $referenceTable = '`' . $referenceTable . '`.';
        }
        foreach ($entryArray as $key => $value) {
            if (is_array($value)) {
                $filters2 = '';
                foreach ($value as $value2) {
                    if ($value2 != '') {
                        if ($filters2 != '') {
                            $filters2 .= ',';
                        }
                        $filters2 .= '"' . $value2 . '"';
                    }
                }
                if ($filters2 != '') {
                    if ($filters != '') {
                        $filters .= ' AND ';
                    }
                    $filters .= ' ' . $referenceTable . '`' . $key
                            . '` IN ("' . str_replace(',', '","', str_replace(["'", '"'], '', $filters2))
                            . '")';
                }
            } else {
                if (($filters != '') && (!in_array($value, ['', '%%']))) {
                    $filters .= ' AND ';
                }
                if (!in_array($value, ['', '%%'])) {
                    if ((substr($value, 0, 1) == '%') && (substr($value, -1) == '%')) {
                        $filters .= ' ' . $key . ' LIKE "' . $value . '"';
                    } else {
                        $filters .= ' ' . $key . ' = "' . $value . '"';
                    }
                }
            }
        }
        return $filters;
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

    private function setFieldSpecific($fieldDetails)
    {
        $sRtrn = '';
        if (in_array($fieldDetails['DATA_TYPE'], ['char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext'])) {
            $sRtrn = ['M' => $fieldDetails['CHARACTER_MAXIMUM_LENGTH']];
        } elseif (in_array($fieldDetails['DATA_TYPE'], ['date'])) {
            $sRtrn = ['M' => 10];
        } elseif (in_array($fieldDetails['DATA_TYPE'], ['time'])) {
            $sRtrn = ['M' => 8];
        } elseif (in_array($fieldDetails['DATA_TYPE'], ['datetime', 'timestamp'])) {
            $sRtrn = ['M' => 19];
        } elseif (in_array($fieldDetails['DATA_TYPE'], ['decimal', 'numeric'])) {
            $sRtrn = ['M' => $fieldDetails['NUMERIC_PRECISION'], 'd' => $fieldDetails['NUMERIC_SCALE']];
        } elseif (in_array($fieldDetails['DATA_TYPE'], ['bigint', 'int', 'mediumint', 'smallint', 'tinyint'])) {
            $sRtrn = $this->setFldLmtsExact($fieldDetails['DATA_TYPE']);
        }
        return $sRtrn;
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
        $aReturn = [
            'customError' => '',
            'result'      => null
        ];
        if (is_null($sReturnType)) {
            return $this->mySQLconnection->query(html_entity_decode($sQuery));
        } elseif (is_null($this->mySQLconnection)) {
            $aReturn['customError'] = $this->lclMsgCmn('i18n_MySQL_ConnectionNotExisting');
        } else {
            $result = $this->mySQLconnection->query(html_entity_decode($sQuery));
            if ($result) {
                switch (strtolower($sReturnType)) {
                    case 'array_first_key_rest_values':
                    case 'array_key_value':
                    case 'array_key_value2':
                    case 'array_key2_value':
                    case 'array_numbered':
                    case 'array_pairs_key_value':
                    case 'full_array_key_numbered':
                        $aReturn           = $this->setMySQLquery2ServerByPattern([
                            'NoOfColumns' => $result->field_count,
                            'NoOfRows'    => $result->num_rows,
                            'QueryResult' => $result,
                            'returnType'  => $sReturnType,
                            'return'      => $aReturn
                        ]);
                        break;
                    case 'full_array_key_numbered_with_record_number_prefix':
                    case 'full_array_key_numbered_with_prefix':
                        $aReturn           = $this->setMySQLquery2ServerByPattern([
                            'NoOfColumns' => $result->field_count,
                            'NoOfRows'    => $result->num_rows,
                            'QueryResult' => $result,
                            'returnType'  => $sReturnType,
                            'prefix'      => $ftrs['prefix'],
                            'return'      => $aReturn
                        ]);
                        break;
                    case 'id':
                        $aReturn['result'] = $this->mySQLconnection->insert_id;
                        break;
                    case 'lines':
                        $aReturn['result'] = $result->num_rows;
                        break;
                    case 'value':
                        if (($result->num_rows == 1) && ($result->field_count == 1)) {
                            $aReturn['result'] = $result->fetch_row()[0];
                        } else {
                            $msg                    = $this->lclMsgCmn('i18n_MySQL_QueryResultExpected1ResultedOther');
                            $aReturn['customError'] = sprintf($msg, $result->num_rows);
                        }
                        break;
                    default:
                        $msg                    = $this->lclMsgCmn('i18n_MySQL_QueryInvalidReturnTypeSpecified');
                        $aReturn['customError'] = sprintf($msg, $sReturnType, __FUNCTION__);
                        break;
                }
                if (is_object($result)) {
                    $result->close();
                }
            } else {
                $erNo                   = $this->mySQLconnection->errno;
                $erMsg                  = $this->mySQLconnection->error;
                $aReturn['customError'] = sprintf($this->lclMsgCmn('i18n_MySQL_QueryError'), $erNo, $erMsg);
            }
        }
        return $aReturn;
    }

    /**
     * Turns a raw query result into various structures
     * based on different predefined $parameters['returnType'] value
     *
     * @param array $parameters
     * @return array as ['customError' => '...', 'result' => '...']
     */
    protected function setMySQLquery2ServerByPattern($parameters)
    {
        $aReturn    = $parameters['return'];
        $buildArray = false;
        switch ($parameters['returnType']) {
            case 'array_first_key_rest_values':
                if ($parameters['NoOfColumns'] >= 2) {
                    $buildArray = true;
                } else {
                    $msg                    = $this->lclMsgCmn('QueryResultExpectedAtLeast2ColsResultedOther');
                    $aReturn['customError'] = sprintf($msg, $parameters['NoOfColumns']);
                }
                break;
            case 'array_key_value':
            case 'array_key_value2':
            case 'array_key2_value':
                if ($parameters['NoOfColumns'] == 2) {
                    $buildArray = true;
                } else {
                    $msg                    = $this->lclMsgCmn('i18n_MySQL_QueryResultExpected2ColumnsResultedOther');
                    $aReturn['customError'] = sprintf($msg, $parameters['NoOfColumns']);
                }
                break;
            case 'array_numbered':
                if ($parameters['NoOfColumns'] == 1) {
                    $buildArray = true;
                } else {
                    $msg                    = $this->lclMsgCmn('i18n_MySQL_QueryResultExpected1ColumnResultedOther');
                    $aReturn['customError'] = sprintf($msg, $parameters['NoOfColumns']);
                }
                break;
            case 'array_pairs_key_value':
                if (($parameters['NoOfRows'] == 1) && ($parameters['NoOfColumns'] > 1)) {
                    $buildArray = true;
                } else {
                    $shorterLclString       = 'i18n_MySQL_QueryResultExpected1RowManyColumnsResultedOther';
                    $msg                    = $this->lclMsgCmn($shorterLclString);
                    $aReturn['customError'] = sprintf($msg, $parameters['NoOfRows'], $parameters['NoOfColumns']);
                }
                break;
            case 'full_array_key_numbered':
            case 'full_array_key_numbered_with_prefix':
            case 'full_array_key_numbered_with_record_number_prefix':
                if ($parameters['NoOfColumns'] == 0) {
                    $aReturn['customError'] = $this->lclMsgCmn('i18n_MySQL_QueryResultExpected1OrMoreRows0Resulted');
                    if (in_array($parameters['returnType'], [
                                'full_array_key_numbered_with_prefix',
                                'full_array_key_numbered_with_record_number_prefix',
                            ])) {
                        $aReturn['result'][$parameters['prefix']] = null;
                    }
                } else {
                    $buildArray = true;
                }
                break;
            default:
                $aReturn['customError'] = $parameters['returnType'] . ' is not defined!';
                break;
        }
        if ($buildArray) {
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
                    case 'array_key_value':
                        $aReturn['result'][$line[0]]                  = $line[1];
                        break;
                    case 'array_key_value2':
                        $aReturn['result'][$line[0]][]                = $line[1];
                        break;
                    case 'array_key2_value':
                        $aReturn['result'][$line[0] . '@' . $line[1]] = $line[1];
                        break;
                    case 'array_numbered':
                        $aReturn['result'][]                          = $line[0];
                        break;
                    case 'array_pairs_key_value':
                        $finfo                                        = $parameters['QueryResult']->fetch_fields();
                        $columnCounter                                = 0;
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
                }
            }
        }
        return $aReturn;
    }
}
