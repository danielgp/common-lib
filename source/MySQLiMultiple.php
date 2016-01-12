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
trait MySQLiMultiple
{

    protected $mySQLconnection = null;

    protected function executeMultipleRepetitiveValues($qry, $prmtrs)
    {
        $stmt = $this->mySQLconnection->stmt_init();
        if ($stmt->prepare($qry)) {
            foreach ($prmtrs as $vParams) {
                $paramType = $this->setVariableTypeForMySqlStatementsMany($vParams);
                $aParams   = [];
                $aParams[] = &$paramType;
                for ($counter = 0; $counter < $stmt->param_count; $counter++) {
                    $aParams[] = &$vParams[$counter];
                }
                call_user_func_array([$stmt, 'bind_param'], $aParams);
                $stmt->execute();
            }
            $stmt->close();
            return '';
        }
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
        $sReturn = 'b';
        if (is_int($variabaleValue)) {
            $sReturn = 'i';
        } elseif (is_double($variabaleValue)) {
            $sReturn = 'd';
        } elseif (is_string($variabaleValue)) {
            $sReturn = 's';
        }
        return $sReturn;
    }

    protected function setVariableTypeForMySqlStatementsMany($variabales)
    {
        $types = [];
        foreach ($variabales as $value2) {
            $types[] = $this->setVariableTypeForMySqlStatements($value2);
        }
        return implode('', $types);
    }
}
