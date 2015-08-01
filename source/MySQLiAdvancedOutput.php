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
 * usefull functions to get quick results
 *
 * @author Daniel Popiniuc
 */
trait MySQLiAdvancedOutput
{

    private $advCache = null;

    private function getFieldCompletionType($details)
    {
        if ($details['IS_NULLABLE'] == 'YES') {
            return $this->setStringIntoTag('~', 'span', [
                        'title' => 'Optional',
                        'class' => 'inputOptional'
            ]);
        } else {
            return $this->setStringIntoTag('**', 'span', [
                        'title' => 'Obligatoriu',
                        'class' => 'inputMandatory'
            ]);
        }
    }

    /**
     * Returns the name of a field for displaying
     *
     * @param array $details
     * @return string
     */
    private function getFieldNameForDisplay($details)
    {
        if ($details['COLUMN_COMMENT'] != '') {
            $sReturn = $details['COLUMN_COMMENT'];
        } else {
            $sReturn = $details['COLUMN_NAME'];
        }
        return $sReturn;
    }

    /**
     * Returns a Enum or Set field 2 use in a form
     *
     * @param string $table_source
     * @param string $field_type
     * @param array $value
     * @param array $features
     * @param string $iar
     * @return string
     */
    private function getFieldOutputEnumSet($tbl_src, $fld_tp, $val, $iar = null)
    {
        $input = null;
        switch ($fld_tp) {
            case 'enum':
                $ia           = ['size' => 1];
                $value_suffix = '';
                break;
            case 'set':
                $ia           = ['size' => 5, 'multiselect'];
                $value_suffix = '[]';
                break;
        }
        if (isset($ia)) {
            if (isset($iar['readonly'])) {
                $input = $this->setStringIntoShortTag('input', [
                    'name'     => $val['COLUMN_NAME'] . $value_suffix,
                    'id'       => $val['COLUMN_NAME'],
                    'readonly' => 'readonly',
                    'class'    => 'input_readonly',
                    'size'     => 50,
                    'value'    => $_REQUEST[$val['COLUMN_NAME']]
                ]);
            } else {
                $vl = explode(',', $this->getFieldValue($val));
                if (!is_null($iar)) {
                    $ia = array_merge($ia, $iar);
                }
                $selectOptions = $this->getSetOrEnum2Array($tbl_src, $val['COLUMN_NAME']);
                $input         = $this->setArrayToSelect($selectOptions, $vl, $val['COLUMN_NAME'] . $value_suffix, $ia);
            }
        }
        return $input;
    }

    /**
     * Returns a Numeric field 2 use in a form
     *
     * @param string $table_source
     * @param string $field_type
     * @param array $value
     * @param array $features
     * @param string $iar
     * @return string
     */
    private function getFieldOutputNumeric($table_source, $field_type, $value, $iar = null)
    {
        $input = null;
        if ($value['EXTRA'] == 'auto_increment') {
            if ($this->getFieldValue($value) == '') {
                $input = $this->setStringIntoTag('auto-numar', 'span', [
                    'id'    => $value['COLUMN_NAME'],
                    'style' => 'font-style:italic;'
                ]);
            } else {
                $ia = [
                    'type'  => 'hidden',
                    'name'  => $value['COLUMN_NAME'],
                    'id'    => $value['COLUMN_NAME'],
                    'value' => $this->getFieldValue($value)
                ];
                if (!is_null($iar)) {
                    $ia = array_merge($ia, $iar);
                }
                $input = $this->setStringIntoTag($this->getFieldValue($value), 'b')
                        . $this->setStringIntoShortTag('input', $ia);
            }
            return $input;
        } else {
            switch ($field_type) {
                case 'bigint':
                // intentioanlly left open
                case 'int':
                // intentioanlly left open
                case 'mediumint':
                // intentioanlly left open
                case 'smallint':
                // intentioanlly left open
                case 'tinyint':
                    switch ($table_source) {
                        case 'user_rights':
                            $database = 'usefull_security';
                            break;
                        default:
                            $database = $this->advCache['workingDatabase'];
                            break;
                    }
                    if (strpos($table_source, '`.`')) {
                        $database = substr($table_source, 0, strpos($table_source, '`.`'));
                    }
                    $foreign_keys_array = $this->getForeignKeysToArray($database, $table_source, $value['COLUMN_NAME']);
                    if (is_null($foreign_keys_array)) {
                        unset($foreign_keys_array);
                    }
                // intentioanlly left open
                case 'float':
                // intentioanlly left open
                case 'double':
                // intentioanlly left open
                case 'decimal':
                // intentioanlly left open
                case 'numeric':
                    if (isset($foreign_keys_array)) {
                        $q             = $this->sQueryGenericSelectKeyValue([
                            $foreign_keys_array[$value['COLUMN_NAME']][1],
                            $foreign_keys_array[$value['COLUMN_NAME']][2],
                            $foreign_keys_array[$value['COLUMN_NAME']][0]
                        ]);
                        $selectOptions = $this->setMySQLquery2Server($q, 'array_key_value')['result'];
                        $selectValue   = $this->getFieldValue($value);
                        $ia            = ['size' => 1];
                        if ($value['IS_NULLABLE'] == 'YES') {
                            $ia = array_merge($ia, ['include_null']);
                        }
                        if (isset($iar)) {
                            $ia = array_merge($ia, $iar);
                        }
                        $input = $this->setArrayToSelect($selectOptions, $selectValue, $value['COLUMN_NAME'], $ia);
                        unset($foreign_keys_array);
                    } else {
                        $fn = $this->setFieldNumbers($value['COLUMN_TYPE']);
                        $ia = [
                            'type'      => 'text',
                            'name'      => $value['COLUMN_NAME'],
                            'id'        => $value['COLUMN_NAME'],
                            'value'     => $this->getFieldValue($value),
                            'size'      => min(50, $fn['l']),
                            'maxlength' => min(50, $fn['l'])
                        ];
                        if (isset($iar)) {
                            $ia = array_merge($ia, $iar);
                        }
                        $input = $this->setStringIntoShortTag('input', $ia);
                    }
                    break;
            }
        }
        return $input;
    }

    /**
     * Returns a Char field 2 use in a form
     *
     * @param string $table_source
     * @param string $field_type
     * @param array $value
     * @param array $features
     * @param string $iar
     * @return string
     */
    private function getFieldOutputText($table_source, $field_type, $value, $iar = null)
    {
        $input = null;
        switch ($table_source) {
            case 'user_rights':
                $database = 'usefull_security';
                break;
            default:
                $database = $this->advCache['workingDatabase'];
                break;
        }
        if (strpos($table_source, '`.`')) {
            $database = substr($table_source, 0, strpos($table_source, '`.`'));
        }
        switch ($field_type) {
            case 'char':
            case 'tinytext':
            case 'varchar':
                if (($table_source != 'user_rights') && ($value['COLUMN_NAME'] != 'eid')) {
                    $foreign_keys_array = $this->getForeignKeysToArray($database, $table_source, $value['COLUMN_NAME']);
                    if (is_null($foreign_keys_array)) {
                        unset($foreign_keys_array);
                    }
                }
                if (isset($foreign_keys_array)) {
                    $q  = $this->storedQuery('generic_select_key_value', [
                        $foreign_keys_array[$value['COLUMN_NAME']][1],
                        $foreign_keys_array[$value['COLUMN_NAME']][2],
                        $foreign_keys_array[$value['COLUMN_NAME']][0]
                    ]);
                    $ia = ['size' => 1];
                    if ($value['IS_NULLABLE'] == 'YES') {
                        $ia = array_merge($ia, ['include_null']);
                    }
                    if (isset($iar)) {
                        $ia = array_merge($ia, $iar);
                    }
                    $slct  = [
                        'Options' => $this->setQuery2Server($q, 'array_key_value'),
                        'Value'   => $this->getFieldValue($value),
                    ];
                    $input = $this->setArrayToSelect($slct['Options'], $slct['Value'], $value['COLUMN_NAME'], $ia);
                    unset($foreign_keys_array);
                } else {
                    $fn = $this->setFieldNumbers($value['COLUMN_TYPE']);
                    $ia = [
                        'type'      => ($value['COLUMN_NAME'] == 'password' ? 'password' : 'text'),
                        'name'      => $value['COLUMN_NAME'],
                        'id'        => $value['COLUMN_NAME'],
                        'size'      => min(30, $fn['l']),
                        'maxlength' => min(255, $fn['l']),
                        'value'     => $this->getFieldValue($value),
                    ];
                    if (isset($iar)) {
                        $ia = array_merge($ia, $iar);
                    }
                    $input = $this->setStringIntoShortTag('input', $ia);
                }
                break;
        }
        return $input;
    }

    /**
     * Returns a Text field 2 use in a form
     *
     * @param string $table_source
     * @param string $field_type
     * @param array $value
     * @param array $features
     * @param string $iar
     * @return string
     */
    private function getFieldOutputTextLarge($field_type, $value, $iar = null)
    {
        $input = null;
        switch ($field_type) {
            case 'text':
            // intentioanlly left open
            case 'blob':
                $ia = [
                    'name' => $value['COLUMN_NAME'],
                    'id'   => $value['COLUMN_NAME'],
                    'rows' => 4,
                    'cols' => 55,
                ];
                if (isset($iar)) {
                    $ia = array_merge($ia, $iar);
                }
                $input = $this->setStringIntoTag($this->getFieldValue($value), 'textarea', $ia);
                break;
        }
        return $input;
    }

    /**
     * Returns a Time field 2 use in a form
     *
     * @param string $table_source
     * @param string $field_type
     * @param array $value
     * @param array $features
     * @param string $iar
     * @return string
     */
    private function getFieldOutputTime($field_type, $value, $iar = null)
    {
        $input = null;
        switch ($field_type) {
            case 'time':
                $ia = [
                    'type'      => 'text',
                    'size'      => 9,
                    'maxlength' => 9,
                    'name'      => $value['COLUMN_NAME'],
                    'id'        => $value['COLUMN_NAME'],
                    'value'     => $this->getFieldValue($value),
                ];
                if (isset($iar)) {
                    $ia = array_merge($ia, $iar);
                }
                $input = $this->setStringIntoShortTag('input', $ia);
                break;
        }
        return $input;
    }

    /**
     * Returns a Timestamp field 2 use in a form
     *
     * @param string $table_source
     * @param string $field_type
     * @param array $value
     * @param array $features
     * @param string $iar
     * @return string
     */
    private function getFieldOutputTimestamp($field_type, $value, $iar = null)
    {
        $input = null;
        switch ($field_type) {
            case 'timestamp':
            // intentioanlly left open
            case 'datetime':
                $ia = [
                    'type'      => 'text',
                    'size'      => 19,
                    'maxlength' => 19,
                    'name'      => $value['COLUMN_NAME'],
                    'id'        => $value['COLUMN_NAME'],
                    'value'     => $this->getFieldValue($value),
                ];
                if (isset($iar)) {
                    $ia = array_merge($ia, $iar);
                }
                $input = $this->setStringIntoShortTag('input', $ia);
                if (!isset($iar['readonly'])) {
                    $input .= $this->setCalendarControlWithTime($value['COLUMN_NAME']);
                }
                break;
        }
        return $input;
    }

    /**
     * Returns given value for a field from $_REQUEST
     *
     * @param array $details
     * @return string
     */
    private function getFieldValue($details)
    {
        $sReturn = '';
        if (isset($_REQUEST[$details['COLUMN_NAME']])) {
            $sReturn = $_REQUEST[$details['COLUMN_NAME']];
        } else {
            if (is_null($details['COLUMN_DEFAULT'])) {
                if ($details['IS_NULLABLE'] == 'YES') {
                    $sReturn = 'NULL';
                } else {
                    $sReturn = '';
                }
            } else {
                $sReturn = $details['COLUMN_DEFAULT'];
            }
        }
        return $sReturn;
    }

    /**
     * Returns an array with fields referenced by a Foreign key
     *
     * @param object $db
     * @param string $database
     * @param string $tblName
     * @param string $onlyCols
     * @return array
     */
    private function getForeignKeysToArray($database, $tblName, $onlyCol = '')
    {
        if (strpos($tblName, '.`')) {
            $tblName = substr($tblName, strpos($tblName, '.`') + 2, 64);
        }
        $this->setTableForeginKeyCache($database, $tblName);
        $array2return = null;
        if (isset($this->advCache['tableFKs'][$database][$tblName])) {
            foreach ($this->advCache['tableFKs'][$database][$tblName] as $value) {
                if ($value['COLUMN_NAME'] == $onlyCol) {
                    $query                  = $this->sQueryMySqlColumns([
                        'TABLE_SCHEMA' => $value['REFERENCED_TABLE_SCHEMA'],
                        'TABLE_NAME'   => $value['REFERENCED_TABLE_NAME'],
                        'DATA_TYPE'    => [
                            'char',
                            'varchar',
                            'text',
                        ],
                    ]);
                    $targetTableTextFields  = $this->setMySQLquery2Server($query, 'full_array_key_numbered')['result'];
                    $significance           = $targetTableTextFields[0]['COLUMN_NAME'];
                    unset($targetTableTextFields);
                    $array2return[$onlyCol] = [
                        '`' . implode('`.`', [
                            $value['REFERENCED_TABLE_SCHEMA'],
                            $value['REFERENCED_TABLE_NAME'],
                        ]) . '`',
                        $value['REFERENCED_COLUMN_NAME'],
                        '`' . $significance . '`',
                    ];
                }
            }
        }
        return $array2return;
    }

    private function getLabel($details)
    {
        return $this->setStringIntoTag($this->getFieldNameForDisplay($details), 'span', ['class' => 'fake_label']);
    }

    /**
     * Returns an array with possible values of a SET or ENUM column
     *
     * @version 20080423
     * @param string $reference_table
     * @param string $reference_column
     * @return array
     */
    protected function getSetOrEnum2Array($ref_tbl, $ref_col)
    {
        if ((strpos($ref_tbl, '`') !== false) && (substr($ref_tbl, 0, 1) != '`')) {
            $ref_tbl = '`' . $ref_tbl . '`';
        }
        if (strpos($ref_tbl, '.') === false) { // in case the DB is ommited get the default one
            $dt[0] = $this->advCache['workingDatabase'];
            $dt[1] = $ref_tbl;
        } else {
            $dt = explode('.', str_replace('`', '', $ref_tbl));
        }
        if (!is_null($this->advCache['tableStructureCache'][$dt[0]][$dt[1]])) {
            foreach ($this->advCache['tableStructureCache'][$dt[0]][$dt[1]] as $value) {
                if ($value['COLUMN_NAME'] == $ref_col) {
                    $shorterType       = substr($value['COLUMN_TYPE'], 0, strlen($value['COLUMN_TYPE']) - 1);
                    $cleanedColumnType = str_replace(['enum(', 'set(', "'"], '', $shorterType);
                    $enum_values       = explode(',', $cleanedColumnType);
                }
            }
            $enum_values = array_combine($enum_values, $enum_values);
        } else {
            $query  = $this->storedQuery('c', [$dt[0] . '.' . $dt[1], $ref_col]);
            $result = $this->db->query($query);
            if (!$result) {
                echo $this->setFeedback(0, 'error', $query . ' ' . $this->mySQLconnection->error);
                $enum_values = null;
            } else {
                $row    = mysqli_fetch_row($result);
                $row[1] = str_replace("'", '', $row[1]);
                if (substr($row[1], 0, 4) == 'set(') {
                    $enum_values = explode(',', substr($row[1], 4, strlen($row[1]) - 5));
                } else {
                    $enum_values = explode(',', substr($row[1], 5, strlen($row[1]) - 6));
                }
                $enum_values = array_combine($enum_values, $enum_values);
            }
        }
        return $enum_values;
    }

    /**
     * Returns a timestamp field value
     *
     * @param $details
     * @return unknown_type
     */
    private function getTimestamping($details)
    {
        $label = $this->getLabel($details);
        if (in_array($this->getFieldValue($details), ['', 'CURRENT_TIMESTAMP', 'NULL'])) {
            switch ($details['COLUMN_NAME']) {
                case 'InsertDateTime':
                    $input = $this->setStringIntoTag('data/timpul ad. informatiei', 'span', [
                        'style' => 'font-style:italic;'
                    ]);
                    break;
                case 'modification_datetime':
                case 'ModificationDateTime':
                    $input = $this->setStringIntoTag('data/timpul modificarii inf.', 'span', [
                        'style' => 'font-style:italic;'
                    ]);
                    break;
            }
        } else {
            $input = $this->setStringIntoTag($this->getFieldValue($details), 'span');
        }
        return ['label' => $label, 'input' => $input];
    }

    /**
     * Returns a generic form based on a given table
     *
     * @param string $ts Table Source
     * @param array $feat
     */
    protected function setFormGenericSingleRecord($ts, $feat, $hiddenInfo = '')
    {
        echo $this->setStringIntoTag('', 'div', [
            'id' => 'loading'
        ]); // Ajax container
        $this->setTableCache($ts); // will populate $this->advCache['tableStructureCache'][$dt[0]][$dt[1]]
        if (strpos($ts, '.') !== false) {
            $ts = explode('.', str_replace('`', '', $ts))[1];
        }
        if (count($this->advCache['tableStructureCache'][$this->advCache['workingDatabase']][$ts]) != 0) {
            foreach ($this->advCache['tableStructureCache'][$this->advCache['workingDatabase']][$ts] as $value) {
                $sReturn[] = $this->setNeededField($ts, $value, $feat);
            }
        }
        $btn[] = $this->setStringIntoShortTag('input', [
            'type'  => 'submit',
            'id'    => 'submit',
            'style' => 'margin-left:220px;',
            'value' => 'Salveaza',
        ]);
        if (isset($feat['insertAndUpdate'])) {
            $btn[] = $this->setStringIntoShortTag('input', [
                'type'  => 'hidden',
                'id'    => 'insertAndUpdate',
                'name'  => 'insertAndUpdate',
                'value' => 'insertAndUpdate'
            ]);
        }
        $sReturn[] = $this->setStringIntoTag(implode('', $btn), 'div');
        if (isset($hiddenInfo)) {
            if (is_array($hiddenInfo)) {
                foreach ($hiddenInfo as $key => $value) {
                    $hiddenInput = $this->setStringIntoShortTag('input', [
                        'type'  => 'hidden',
                        'name'  => $key,
                        'id'    => $key,
                        'value' => $value,
                    ]);
                    $sReturn[]   = $this->setStringIntoTag($hiddenInput, 'div');
                }
            }
        }
        return $this->setStringIntoTag(implode('', $sReturn), 'form', [
                    'id'     => $feat['id'],
                    'action' => $feat['action'],
                    'method' => $feat['method']
        ]);
    }

    /**
     * Analyse the field and returns the proper line 2 use in forms
     *
     * @param string $tableSource
     * @param array $details
     * @param array $features
     * @return string|array
     */
    private function setNeededField($tableSource, $details, $features)
    {
        if (isset($features['hidden'])) {
            if (in_array($details['COLUMN_NAME'], $features['hidden'])) {
                return null;
            }
        }
        if ($this->getFieldNameForDisplay($details) == 'hidden') {
            return null;
        }
        switch ($details['COLUMN_NAME']) {
            case 'host':
                $sReturn['label'] = $this->setStringIntoTag('Numele calculatorului', 'label', [
                    'for' => $details['COLUMN_NAME']
                ]);
                $sReturn['input'] = $this->setStringIntoShortTag('input', [
                    'type'     => 'input',
                    'size'     => 15,
                    'readonly' => 'readonly',
                    'value'    => gethostbyaddr($_SERVER['REMOTE_ADDR'])
                ]);
                break;
            case 'InsertDateTime':
            case 'modification_datetime':
            case 'ModificationDateTime':
                $sReturn          = call_user_func_array([$this, 'getTimestamping'], [$details]);
                break;
            default:
                $aLabel           = [
                    'for' => $details['COLUMN_NAME'],
                    'id'  => $details['COLUMN_NAME'] . '_label'
                ];
                if (isset($features['disabled'])) {
                    if (in_array($details['COLUMN_NAME'], $features['disabled'])) {
                        $aLabel = array_merge($aLabel, ['style' => 'color: grey;']);
                    }
                }
                $sReturn['label'] = $this->setStringIntoTag($this->getFieldNameForDisplay($details), 'label', $aLabel);
                if ($details['COLUMN_NAME'] == 'ChoiceId') {
                    $result = $this->setStringIntoShortTag('input', [
                        'type'  => 'text',
                        'name'  => $details['COLUMN_NAME'],
                        'value' => $_REQUEST[$details['COLUMN_NAME']]
                    ]);
                } else {
                    $result = $this->setNeededFieldByType($tableSource, $details, $features);
                }
                $sReturn['input'] = $result;
                break;
        }
        $finalReturn[] = $sReturn['label'];
        $finalReturn[] = $this->setStringIntoTag($sReturn['input'], 'span', ['class' => 'labell']);
        $wrkDb         = $this->advCache['workingDatabase'];
        if (isset($this->tableFKsCache[$wrkDb][$tableSource])) {
            if (in_array($details['COLUMN_NAME'], $this->advCache['FKcol'][$wrkDb][$tableSource])) {
                $finalReturn[] = $this->getFieldLength($details);
            }
        }
        return $this->setStringIntoTag(implode('', $finalReturn), 'div');
    }

    /**
     * Analyse the field type and returns the proper lines 2 use in forms
     *
     * @param string $tbl_src
     * @param array $details
     * @param array $features
     * @return string/array
     */
    private function setNeededFieldByType($tbl_src, $details, $features)
    {
        $sReturn = null;
        if (isset($features['special'])) {
            if (isset($features['special'][$details['COLUMN_NAME']])) {
                $slctOpt = $this->setQuery2Server($features['special'][$details['COLUMN_NAME']], 'array_key_value');
                $slctVl  = $this->getFieldValue($details);
                $sReturn = $this->setArrayToSelect($slctOpt, $slctVl, $details['COLUMN_NAME'], ['size' => 1]);
            }
        }
        if (is_null($sReturn)) {
            $iar = null;
            if (isset($features['readonly'])) {
                if (in_array($details['COLUMN_NAME'], $features['readonly'])) {
                    $iar = ['readonly' => 'readonly', 'class' => 'input_readonly'];
                }
            }
            if (isset($features['disabled'])) {
                if (in_array($details['COLUMN_NAME'], $features['disabled'])) {
                    $iar = ['disabled' => 'disabled'];
                }
            }
            if (isset($features['include_null'])) {
                if (in_array($details['COLUMN_NAME'], $features['include_null'])) {
                    $iar = ['include_null'];
                }
            }
            switch ($details['DATA_TYPE']) {
                case 'bigint':
                case 'int':
                case 'mediumint':
                case 'smallint':
                case 'tinyint':
                case 'decimal':
                case 'numeric':
                    $sReturn = $this->getFieldOutputNumeric($tbl_src, $details['DATA_TYPE'], $details, $iar);
                    break;
                case 'char':
                case 'tinytext':
                case 'varchar':
                    $sReturn = $this->getFieldOutputText($tbl_src, $details['DATA_TYPE'], $details, $iar);
                    break;
                case 'date':
                    $sReturn = $this->getFieldOutputDate($details['DATA_TYPE'], $details, $iar);
                    break;
                case 'datetime':
                case 'timestamp':
                    $sReturn = $this->getFieldOutputTimestamp($details['DATA_TYPE'], $details, $iar);
                    break;
                case 'enum':
                case 'set':
                    $sReturn = $this->getFieldOutputEnumSet($tbl_src, $details['DATA_TYPE'], $details, $iar);
                    break;
                case 'text':
                case 'blob':
                    $sReturn = $this->getFieldOutputTextLarge($details['DATA_TYPE'], $details, $iar);
                    break;
                case 'time':
                    $sReturn = $this->getFieldOutputTime($details['DATA_TYPE'], $details, $iar);
                    break;
                case 'year':
                    for ($c = 1901; $c <= 2155; $c++) {
                        $listOfValues[$c] = $c;
                    }
                    if (is_null($iar)) {
                        $slDflt  = $this->getFieldValue($details);
                        $sReturn = $this->setArrayToSelect($listOfValues, $slDflt, $details['COLUMN_NAME'], [
                            'size' => 1
                        ]);
                    } else {
                        $sReturn = $this->getFieldOutputText('varchar', $details, $iar);
                    }
                    break;
            }
        }
        return $this->getFieldCompletionType($details) . $sReturn;
    }

    private function setTableCache($ts)
    {
        if (strpos($ts, '.') === false) { // in case the DB is ommited get the default one
            $dt[1] = $ts;
            $dt[0] = MYSQL_DATABASE;
        } else {
            $dt = explode('.', str_replace('`', '', $ts));
        }
        if (is_null($this->advCache['tableStructureCache'][$dt[0]][$dt[1]])) {
            $this->advCache['workingDatabase']                     = $dt[0];
            $this->advCache['tableStructureCache'][$dt[0]][$dt[1]] = $this->getMySQLlistColumns([
                'TABLE_SCHEMA' => $dt[0],
                'TABLE_NAME'   => $dt[1],
            ]);
            $this->setTableForeginKeyCache($dt[0], $dt[1]);
        }
    }

    private function setTableForeginKeyCache($dtbase, $tblName)
    {
        $frgnKeys = $this->getMySQLlistIndexes([
            'TABLE_SCHEMA'          => $dtbase,
            'TABLE_NAME'            => $tblName,
            'REFERENCED_TABLE_NAME' => 'NOT NULL',
        ]);
        if (!is_null($frgnKeys)) {
            $this->advCache['tableFKs'][$dtbase][$tblName] = $frgnKeys;
            $colWithFK                                     = array_column($frgnKeys, 'COLUMN_NAME', 'CONSTRAINT_NAME');
            $this->advCache['FKcol'][$dtbase][$tblName]    = $colWithFK;
        }
    }
}