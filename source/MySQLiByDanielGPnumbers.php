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
trait MySQLiByDanielGPnumbers
{

    use DomComponentsByDanielGP;

    /**
     * Creates an input for ENUM or SET if marked Read-Only
     *
     * @param array $val
     * @param array $adnlThings
     * @return string
     */
    protected function getFieldOutputEnumSetReadOnly($val, $adnlThings)
    {
        $inputFeatures = [
            'name'     => $val['COLUMN_NAME'] . $adnlThings['suffix'],
            'id'       => $val['COLUMN_NAME'],
            'readonly' => 'readonly',
            'class'    => 'input_readonly',
            'size'     => 50,
            'value'    => $this->getFieldValue($val),
        ];
        return $this->setStringIntoShortTag('input', $inputFeatures);
    }

    /**
     * Builds output as text input type
     *
     * @param array $value
     * @param integer $szN
     * @param array $iar
     * @return string
     */
    protected function getFieldOutputTT($value, $szN, $iar = [])
    {
        $inAdtnl = [
            'id'        => $value['COLUMN_NAME'],
            'maxlength' => $szN,
            'name'      => $value['COLUMN_NAME'],
            'size'      => $szN,
            'type'      => 'text',
            'value'     => $this->getFieldValue($value),
        ];
        if ($iar !== []) {
            $inAdtnl = array_merge($inAdtnl, $iar);
        }
        return $this->setStringIntoShortTag('input', $inAdtnl);
    }

    /**
     * Returns given value for a field from REQUEST global variable
     *
     * @param array $details
     * @return string
     */
    protected function getFieldValue($details)
    {
        $this->initializeSprGlbAndSession();
        $rqCN = $this->tCmnRequest->request->get($details['COLUMN_NAME']);
        if (!is_null($rqCN)) {
            if (($details['IS_NULLABLE'] == 'YES') && ($rqCN == '')) {
                return 'NULL';
            }
            return $rqCN;
        }
        return $this->getFieldValueWithoutUserInput($details);
    }

    /**
     * Handles field value ignoring any input from the user
     *
     * @param array $details
     * @return string
     */
    private function getFieldValueWithoutUserInput($details)
    {
        if ($details['COLUMN_DEFAULT'] === null) {
            if ($details['IS_NULLABLE'] == 'YES') {
                return 'NULL';
            }
            return '';
        }
        return $details['COLUMN_DEFAULT'];
    }

    /**
     * Prepares the label for inputs
     *
     * @param array $details
     * @param array $features
     * @param string $fieldLabel
     * @return string
     */
    protected function setFieldLabel($details, $features, $fieldLabel)
    {
        $aLabel = ['for' => $details['COLUMN_NAME'], 'id' => $details['COLUMN_NAME'] . '_label'];
        if (isset($features['disabled'])) {
            if (in_array($details['COLUMN_NAME'], $features['disabled'])) {
                $aLabel = array_merge($aLabel, ['style' => 'color: grey;']);
            }
        }
        return $this->setStringIntoTag($fieldLabel, 'label', $aLabel);
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
            foreach ($sRtrn as $key => $value) {
                $sRtrn[$key] = $this->setNumberFormat($value);
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
            return $this->setFldLmtsExact($fieldDetails['DATA_TYPE'], $fieldDetails['COLUMN_TYPE']);
        }
        return $this->setFieldSpecificElse($fieldDetails);
    }

    /**
     *
     * @param array $fieldDetails
     * @return array
     */
    private function setFieldSpecificElse($fieldDetails)
    {
        $map = ['date' => 10, 'datetime' => 19, 'enum' => 65536, 'set' => 64, 'time' => 8, 'timestamp' => 19];
        if (array_key_exists($fieldDetails['DATA_TYPE'], $map)) {
            return ['M' => $map[$fieldDetails['DATA_TYPE']]];
        }
        return ['M' => '???'];
    }

    private function setFldLmtsExact($dTp, $cTp)
    {
        $xct     = [
            'bigint'    => ['l' => -9223372036854775808, 'L' => 9223372036854775807, 's' => 21, 'sUS' => 20],
            'int'       => ['l' => -2147483648, 'L' => 2147483647, 's' => 11, 'sUS' => 10],
            'mediumint' => ['l' => -8388608, 'L' => 8388607, 's' => 9, 'sUS' => 8],
            'smallint'  => ['l' => -32768, 'L' => 32767, 's' => 6, 'sUS' => 5],
            'tinyint'   => ['l' => -128, 'L' => 127, 's' => 4, 'sUS' => 3],
        ];
        $aReturn = null;
        if (array_key_exists($dTp, $xct)) {
            $aReturn = ['m' => $xct[$dTp]['l'], 'M' => $xct[$dTp]['L'], 'l' => $xct[$dTp]['s']];
            if (strpos($cTp, 'unsigned') !== false) {
                $aReturn = ['m' => 0, 'M' => ($xct[$dTp]['L'] - $xct[$dTp]['l']), 'l' => $xct[$dTp]['sUS']];
            }
        }
        return $aReturn;
    }

    /**
     * Form default buttons
     *
     * @param array $feat
     * @param array $hiddenInfo
     * @return string
     */
    protected function setFormButtons($feat, $hiddenInfo = [])
    {
        $btn   = [];
        $btn[] = '<input type="submit" id="submit" style="margin-left:220px;" value="'
            . $this->lclMsgCmn('i18n_Form_ButtonSave') . '" />';
        if (isset($feat['insertAndUpdate'])) {
            $btn[] = '<input type="hidden" id="insertAndUpdate" name="insertAndUpdate" value="insertAndUpdate" />';
        }
        if ($hiddenInfo != []) {
            foreach ($hiddenInfo as $key => $value) {
                $btn[] = '<input type="hidden" id="' . $key . '" name="' . $key . '" value="' . $value . '" />';
            }
        }
        return '<div>' . implode('', $btn) . '</div>';
    }

    /**
     *
     * @param array $prm
     * @return array
     */
    protected function setMySQLqueryValidateInputs($prm)
    {
        $rMap = $this->setMySQLqueryValidationMap();
        if (array_key_exists($prm['returnType'], $rMap)) {
            $elC = [$prm['NoOfRows'], $rMap[$prm['returnType']]['r'][0], $rMap[$prm['returnType']]['r'][1]];
            if (filter_var($elC[0], FILTER_VALIDATE_INT, ['min_range' => $elC[1], 'max_range' => $elC[2]]) === false) {
                $suffix2 = (string) $rMap[$prm['returnType']][2];
                $msg     = $this->lclMsgCmn('i18n_MySQL_QueryResultExpected' . $suffix2);
                return [false, sprintf($msg, $prm['NoOfColumns'])];
            }
            $elR = [$prm['NoOfColumns'], $rMap[$prm['returnType']]['c'][0], $rMap[$prm['returnType']]['c'][1]];
            if (filter_var($elR[0], FILTER_VALIDATE_INT, ['min_range' => $elR[1], 'max_range' => $elR[2]])) {
                return [true, ''];
            }
            $suffix1 = (string) $rMap[$prm['returnType']][1];
            $msg     = $this->lclMsgCmn('i18n_MySQL_QueryResultExpected' . $suffix1);
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
