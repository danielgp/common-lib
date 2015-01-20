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
 * usefull functions to get quick MySQL content
 *
 * @author Daniel Popiniuc
 */
trait MySQLiByDanielGP
{

    protected $mySQLconnection = null;

    /**
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
                return sprintf(_('i18n_Feedback_ConnectionError'), $erNo, $erMsg, $host, $port, $username, $database);
            } else {
                return '';
            }
        }
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
            return '';
        }
        if (is_null($this->mySQLconnection)) {
            die(sprintf(_('i18n_Feedback_ConnectionError'), $erNo, $erMsg, $host, $port, $username, $database));
        }
        $aReturn = [
            'customError' => '',
            'result'      => null
        ];
        $result  = $this->mySQLconnection->query($sQuery);
        if ($result) {
            $iNoOfRows    = $result->num_rows;
            $iNoOfColumns = $result->field_count;
            switch (strtolower($sReturnType)) {
                case 'array_key_value':
                    switch ($iNoOfColumns) {
                        case 2:
                            for ($counter = 0; $counter < $iNoOfRows; $counter++) {
                                $line                        = $result->fetch_row();
                                $aReturn['result'][$line[0]] = $line[1];
                            }
                            break;
                        default:
                            $msg = _('i18n_MySQL_QueryResultExpected2ColumnsResultedOther');
                            $aReturn['customError'] = sprintf($msg, $iNoOfRows);
                            break;
                    }
                    break;
                case 'value':
                    if (($iNoOfRows == 1) && ($iNoOfColumns == 1)) {
                        $aReturn['result']      = $result->fetch_row()[0];
                    } else {
                        $msg = _('i18n_MySQL_QueryResultExpected1ResultedOther');
                        $aReturn['customError'] = sprintf($msg, $iNoOfRows);
                    }
                    break;
                default:
                    break;
            }
            $result->close();
        } else {
            $erNo                   = $this->mySQLconnection->connect_errno;
            $erMsg                  = $this->mySQLconnection->connect_error;
            $aReturn['customError'] = sprintf(_('i18n_MySQL_QueryError'), $erNo, $erMsg);
        }
        return $aReturn;
    }
}
