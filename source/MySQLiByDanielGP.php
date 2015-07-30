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

    protected $mySQLconnection = null;

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
            if ($this->mySQLconnection->connect_error) {
                $erNo                  = $this->mySQLconnection->connect_errno;
                $erMsg                 = $this->mySQLconnection->connect_error;
                $this->mySQLconnection = null;
                $msg                   = $this->lclMsgCmn('i18n_Feedback_ConnectionError');
                return sprintf($msg, $erNo, $erMsg, $host, $port, $username, $database);
            } else {
                return '';
            }
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
            $line = [];
        } else {
            $line = [
                'Info'    => $this->mySQLconnection->server_info,
                'Version' => $this->mySQLconnection->server_version
            ];
        }
        return $line;
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
    protected function getMySQLlistDatabases($excludeSystemDatabases = true)
    {
        return $this->getMySQLlistMultiple('Databases', 'array_first_key_rest_values', $excludeSystemDatabases);
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
            switch ($returnType) {
                case 'value':
                    $line = null;
                    break;
                default:
                    $line = [];
                    break;
            }
        } else {
            switch ($returnChoice) {
                case 'Columns':
                    $q = $this->sQueryMySqlColumns($additionalFeatures);
                    break;
                case 'Databases':
                    $q = $this->sQueryMySqlActiveDatabases($additionalFeatures);
                    break;
                case 'Engines':
                    $q = $this->sQueryMySqlActiveEngines($additionalFeatures);
                    break;
                case 'Indexes':
                    $q = $this->sQueryMySqlIndexes($additionalFeatures);
                    break;
                case 'ServerTime':
                    $q = $this->sQueryMySqlServerTime();
                    break;
                case 'Tables':
                    $q = $this->sQueryMySqlTables($additionalFeatures);
                    break;
                case 'VariablesGlobal':
                    $q = $this->sQueryMySqlGlobalVariables();
                    break;
            }
            $line = $this->setMySQLquery2Server($q, $returnType)[
                    'result'
            ];
        }
        return $line;
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
     * Returns the Query language type by scanning the 1st keyword from a given query
     *
     * @param input $sQuery
     */
    protected function getMySQLqueryType($sQuery)
    {
        $queryPieces    = explode(' ', $sQuery);
        $statementTypes = $this->getMySQLqueryStatementType();
        if (in_array($queryPieces[0], array_keys($statementTypes))) {
            $type    = $statementTypes[$queryPieces[0]]['Type'];
            $sReturn = array_merge([
                'detected1stKeywordWithinQuery' => $queryPieces[0],
                $type                           => $this->getMySQLqueryLanguageType()[$type],
                    ], $statementTypes[$queryPieces[0]]);
        } else {
            $sReturn = [
                'detected1stKeywordWithinQuery' => $queryPieces[0],
                'unknown'                       => [
                    'standsFor'   => 'unknown',
                    'description' => 'unknown',
                ],
                'Type'                          => 'unknown',
                'Description'                   => 'unknown',
            ];
        }
        return $sReturn;
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
        if (strpos($sQuery, $paramIdentifier) === false) {
            return false;
        } else {
            return true;
        }
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
     * Reads data from table into $_REQUEST
     *
     * @param string $tableName
     * @param array $filtersArray
     */
    protected function getRowDataFromTable($tableName, $filtersArray)
    {
        $query   = $this->sQueryRowsFromTable([
            $tableName,
            $this->setArrayToFilterValues($filtersArray),
        ]);
        $rawData = $this->setMySQLquery2Server($query, 'array_pairs_key_value');
        if (!is_null($rawData)) {
            foreach ($rawData as $key => $value) {
                $_REQUEST[$key] = str_replace(['\\\\"', '\\"', "\\\\'", "\\'"], ['"', '"', "'", "'"], $value);
            }
        }
    }

    /**
     * Just to keep a list of type of language as array
     *
     * @return array
     */
    private static function listOfMySQLqueryLanguageType()
    {
        return [
            'DCL' => [
                'standsFor'   => 'Data Control Language',
                'description' => implode(', ', [
                    'includes commands such as GRANT',
                    'and mostly concerned with rights',
                    'permissions and other controls of the database system',
                ]),
            ],
            'DDL' => [
                'standsFor'   => 'Data Definition Language',
                'description' => implode(', ', [
                    'deals with database schemas and descriptions',
                    'of how the data should reside in the database',
                ]),
            ],
            'DML' => [
                'standsFor'   => 'Data Manipulation Language',
                'description' => implode(', ', [
                    'deals with data manipulation',
                    'and includes most common SQL statements such as SELECT, INSERT, UPDATE, DELETE etc',
                    'and it is used to store, modify, retrieve, delete and update data in database',
                ]),
            ],
            'DQL' => [
                'standsFor'   => 'Data Query Language',
                'description' => 'deals with data/structure retrieval',
            ],
            'DTL' => [
                'standsFor'   => 'Data Transaction Language',
                'description' => implode('. ', [
                    'statements are used to manage changes made by DML statements',
                    'It allows statements to be grouped together into logical transactions',
                ]),
            ],
        ];
    }

    /**
     * Just to keep a list of statement types as array
     *
     * @return array
     */
    private static function listOfMySQLqueryStatementType()
    {
        return [
            'ALTER'     => [
                'Type'        => 'DDL',
                'Description' => 'create objects in the database',
            ],
            'CALL'      => [
                'Type'        => 'DML',
                'Description' => 'call a stored procedure',
            ],
            'COMMENT'   => [
                'Type'        => 'DDL',
                'Description' => 'add comments to the data dictionary',
            ],
            'COMMIT'    => [
                'Type'        => 'DTL',
                'Description' => 'sends a signal to MySQL to save all un-commited statements',
            ],
            'CREATE'    => [
                'Type'        => 'DDL',
                'Description' => 'create objects within a database',
            ],
            'DELETE'    => [
                'Type'        => 'DML',
                'Description' => 'deletes records from a table (all or partial depending on potential conditions)',
            ],
            'DESC'      => [
                'Type'        => 'DML',
                'Description' => 'interpretation of the data access path (synonym of EXPLAIN)',
            ],
            'DESCRIBE'  => [
                'type'        => 'DML',
                'Description' => 'interpretation of the data access path (synonym of EXPLAIN)',
            ],
            'DO'        => [
                'Type'        => 'DML',
                'Description' => 'executes an expression without returning any result',
            ],
            'DROP'      => [
                'Type'        => 'DDL',
                'Description' => 'delete objects from a database',
            ],
            'EXPLAIN'   => [
                'Type'        => 'DML',
                'Description' => 'interpretation of the data access path',
            ],
            'GRANT'     => [
                'Type'        => 'DCL',
                'Description' => 'allow users access privileges to database',
            ],
            'HANDLER'   => [
                'Type'        => 'DML',
                'Description' => 'statement provides direct access to table storage engine interfaces',
            ],
            'HELP'      => [
                'Type'        => 'DQL',
                'Description' => implode(' ', [
                    'The HELP statement returns online information from the MySQL Reference manual.',
                    'Its proper operation requires that the help tables in the mysql database',
                    'be initialized with help topic information',
                ]),
            ],
            'INSERT'    => [
                'Type'        => 'DML',
                'Description' => 'insert data into a table',
            ],
            'LOAD'      => [
                'Type'        => 'DML',
                'Description' => implode(' ', [
                    'The LOAD DATA INFILE statement reads rows from a text file',
                    'into a table at a very high speed',
                    'or LOAD XML statement reads data from an XML file into a table',
                ]),
            ],
            'LOCK'      => [
                'Type'        => 'DML',
                'Description' => 'concurrency control',
            ],
            'MERGE'     => [
                'Type'        => 'DML',
                'Description' => 'UPSERT operation (insert or update)',
            ],
            'RELEASE'   => [
                'Type'        => 'DTL',
                'Description' => implode(' ', [
                    'The RELEASE SAVEPOINT statement removes the named savepoint',
                    'from the set of savepoints of the current transaction.',
                    'No commit or rollback occurs. It is an error if the savepoint does not exist.',
                ]),
            ],
            'RENAME'    => [
                'Type'        => 'DDL',
                'Description' => 'rename objects from a database',
            ],
            'REPLACE'   => [
                'Type'        => 'DML',
                'Description' => implode(' ', [
                    'REPLACE works exactly like INSERT, except that if an old row in the table',
                    'has the same value as a new row for a PRIMARY KEY or a UNIQUE index,',
                    'the old row is deleted before the new row is inserted',
                ]),
            ],
            'REVOKE'    => [
                'Type'        => 'DCL',
                'description' => 'withdraw users access privileges given by using the GRANT command',
            ],
            'ROLLBACK'  => [
                'Type'        => 'DTL',
                'Description' => 'restore database to original since the last COMMIT',
            ],
            'SELECT'    => [
                'Type'        => 'DQL',
                'Description' => 'retrieve data from the a database',
            ],
            'SAVEPOINT' => [
                'Type'        => 'DTL',
                'Description' => 'identify a point in a transaction to which you can later roll back',
            ],
            'SET'       => [
                'Type'        => 'DTL',
                'Description' => 'change values of global/session variables or transaction characteristics',
            ],
            'SHOW'      => [
                'Type'        => 'DQL',
                'Description' => implode(' ', [
                    'has many forms that provide information about databases, tables, columns,',
                    'or status information about the server',
                ]),
            ],
            'START'     => [
                'Type'        => 'DTL',
                'Description' => 'marks the starting point for a transaction',
            ],
            'TRUNCATE'  => [
                'Type'        => 'DDL',
                'Description' => implode(', ', [
                    'remove all records from a table',
                    'including all spaces allocated for the records are removed'
                ]),
            ],
            'UPDATE'    => [
                'Type'        => 'DML',
                'Description' => 'updates existing data within a table',
            ],
            'USE'       => [
                'Type'        => 'DML',
                'Description' => implode(' ', [
                    'The USE db_name statement tells MySQL to use the db_name database',
                    'as the default (current) database for subsequent statements.',
                ]),
            ],
        ];
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
            return $this->mySQLconnection->query($sQuery);
        } elseif (is_null($this->mySQLconnection)) {
            $aReturn['customError'] = $this->lclMsgCmn('i18n_MySQL_ConnectionNotExisting');
        } else {
            $result = $this->mySQLconnection->query($sQuery);
            if ($result) {
                if (is_object($result)) {
                    $iNoOfRows = $result->num_rows;
                    $iNoOfCols = $result->field_count;
                }
                switch (strtolower($sReturnType)) {
                    case 'array_first_key_rest_values':
                    case 'array_key_value':
                    case 'array_key_value2':
                    case 'array_key2_value':
                    case 'array_numbered':
                    case 'array_pairs_key_value':
                    case 'full_array_key_numbered':
                        $aReturn           = $this->setMySQLquery2ServerByPattern([
                            'NoOfColumns' => $iNoOfCols,
                            'NoOfRows'    => $iNoOfRows,
                            'QueryResult' => $result,
                            'returnType'  => $sReturnType,
                            'return'      => $aReturn
                        ]);
                        break;
                    case 'full_array_key_numbered_with_record_number_prefix':
                    case 'full_array_key_numbered_with_prefix':
                        $aReturn           = $this->setMySQLquery2ServerByPattern([
                            'NoOfColumns' => $iNoOfCols,
                            'NoOfRows'    => $iNoOfRows,
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
                        $aReturn['result'] = $iNoOfRows;
                        break;
                    case 'value':
                        if (($iNoOfRows == 1) && ($iNoOfCols == 1)) {
                            $aReturn['result'] = $result->fetch_row()[0];
                        } else {
                            $msg                    = $this->lclMsgCmn('i18n_MySQL_QueryResultExpected1ResultedOther');
                            $aReturn['customError'] = sprintf($msg, $iNoOfRows);
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
                    $counter2   = 0;
                }
                break;
            default:
                $aReturn['customError'] = $parameters['returnType'] . ' is not defined!';
                break;
        }
        if ($buildArray) {
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

    /**
     * Detects what kind of variable has been transmited
     * to return the identifier needed by MySQL statement preparing
     *
     * @param type $variabaleValue
     * @return string
     */
    protected function setVariableTypeForMySqlStatements($variabaleValue)
    {
        $sReturn = '';
        if (is_int($variabaleValue)) {
            $sReturn = 'i';
        } elseif (is_double($variabaleValue)) {
            $sReturn = 'd';
        } elseif (is_string($variabaleValue)) {
            $sReturn = 's';
        } else {
            $sReturn = 'b';
        }
        return $sReturn;
    }
}
