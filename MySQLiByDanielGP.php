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

    protected $commonLibFlags  = null;
    protected $mySQLconnection = null;
    protected $tCmnLb          = null;

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
            if (is_null($this->commonLibFlags)) {
                $this->initCommomLibParameters();
            }
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
     * Return the time from the MySQL server
     *
     * @return string
     */
    protected function getMySQLserverTime()
    {
        return $this->getMySQLlistMultiple('ServerTime', 'value');
    }

    private function handleLocalizationCommon()
    {
        if (isset($_GET['lang'])) {
            $_SESSION['lang'] = filter_var($_GET['lang'], FILTER_SANITIZE_STRING);
        } elseif (!isset($_SESSION['lang'])) {
            $_SESSION['lang'] = $this->commonLibFlags['default_language'];
        }
        /* to avoid potential language injections from other applications that do not applies here */
        if (!in_array($_SESSION['lang'], array_keys($this->commonLibFlags['available_languages']))) {
            $_SESSION['lang'] = $this->commonLibFlags['default_language'];
        }
        $localizationFile = __DIR__ . '/locale/' . $_SESSION['lang'] . '/LC_MESSAGES/'
                . $this->commonLibFlags['localization_domain']
                . '.mo';
        $translations     = \Gettext\Extractors\Mo::fromFile($localizationFile);
        $this->tCmnLb     = new \Gettext\Translator();
        $this->tCmnLb->loadTranslations($translations);
    }

    private function initCommomLibParameters()
    {
        $this->commonLibFlags = [
            'available_languages' => [
                'en_US' => 'EN',
                'ro_RO' => 'RO',
            ],
            'default_language'    => 'en_US',
            'localization_domain' => 'common-locale'
        ];
        $this->handleLocalizationCommon();
    }

    protected function lclMsgCmn($localizedStringCode)
    {
        return $this->tCmnLb->gettext($localizedStringCode);
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
                $erNo                   = $this->mySQLconnection->connect_errno;
                $erMsg                  = $this->mySQLconnection->connect_error;
                $aReturn['customError'] = sprintf($this->lclMsgCmn('i18n_MySQL_QueryError'), $erNo, $erMsg);
            }
        }
        return $aReturn;
    }

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
                if ($parameters['NoOfColumns'] == 0) {
                    $aReturn['customError'] = $this->lclMsgCmn('i18n_MySQL_QueryResultExpected1OrMoreRows0Resulted');
                    if ($parameters['returnType'] == 'full_array_key_numbered_with_prefix') {
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
                    case 'full_array_key_numbered_with_prefix':
                        $finfo         = $parameters['QueryResult']->fetch_fields();
                        $columnCounter = 0;
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

    protected function setVariableTypeForMySqlStatements($variabaleValue)
    {
        if (is_int($variabaleValue)) {
            return 'i';
        } else if (is_double($variabaleValue)) {
            return 'd';
        } else if (is_string($variabaleValue)) {
            return 's';
        } else {
            return 'b';
        }
    }
}
