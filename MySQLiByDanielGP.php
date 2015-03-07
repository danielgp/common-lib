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
     * returns a list of MySQL databases (except the system ones)
     *
     * @return array
     */
    protected function getMySQLactiveDatabases()
    {
        if (is_null($this->mySQLconnection)) {
            $line = [];
        } else {
            $line = $this->setMySQLquery2Server($this->sQueryMySqlActiveDatabases(), 'array_first_key_rest_values')[
                    'result'
            ];
        }
        return $line;
    }

    /**
     * returns a list of MySQL engines |(except the system ones)
     *
     * @return array
     */
    protected function getMySQLactiveEngines()
    {
        if (is_null($this->mySQLconnection)) {
            $line = [];
        } else {
            $line = $this->setMySQLquery2Server($this->sQueryMySqlActiveEngines(), 'array_first_key_rest_values')[
                    'result'
            ];
        }
        return $line;
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
        if (is_null($this->mySQLconnection)) {
            $line = [];
        } else {
            $line = $this->setMySQLquery2Server($this->sQueryMySqlGlobalVariables(), 'array_key_value')[
                    'result'
            ];
        }
        return $line;
    }

    /**
     * Return the time from the MySQL server
     *
     * @return string
     */
    protected function getMySQLserverTime()
    {
        if (is_null($this->mySQLconnection)) {
            $line = null;
        } else {
            $line = $this->setMySQLquery2Server($this->sQueryMySqlServerTime(), 'value')[
                    'result'
            ];
        }
        return $line;
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
        setlocale(LC_MESSAGES, $_SESSION['lang']);
        if (function_exists('bindtextdomain')) {
            bindtextdomain($this->commonLibFlags['localization_domain'], realpath('./locale'));
            bind_textdomain_codeset($this->commonLibFlags['localization_domain'], 'UTF-8');
        } else {
            echo 'No gettext extension is active in current PHP configuration!';
        }
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
        return dgettext($this->commonLibFlags['localization_domain'], $localizedStringCode);
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
}
