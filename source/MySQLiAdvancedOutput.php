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

    use DomComponentsByDanielGP,
        MySQLiByDanielGP;

    protected $advCache = null;

    /**
     * Establish Database and Table intended to work with
     * (in case the DB is ommited get the default one)
     *
     * @param string $tblSrc
     */
    private function establishDatabaseAndTable($tblSrc)
    {
        if (strpos($tblSrc, '.') === false) {
            if (!defined('MYSQL_DATABASE')) {
                define('MYSQL_DATABASE', 'information_schema');
            }
            return [$tblSrc, MYSQL_DATABASE];
        }
        return explode('.', str_replace('`', '', $tblSrc));
    }

    private function establishDefaultEnumSet($fldType)
    {
        $dfltArray = [
            'enum' => ['additional' => ['size' => 1], 'suffix' => ''],
            'set'  => ['additional' => ['size' => 5, 'multiselect'], 'suffix' => '[]'],
        ];
        return $dfltArray[$fldType];
    }

    private function getFieldCompletionType($details)
    {
        $inputFeatures = ['display' => '***', 'ftrs' => ['title' => 'Mandatory', 'class' => 'inputMandatory']];
        if ($details['IS_NULLABLE'] == 'YES') {
            $inputFeatures = ['display' => '~', 'ftrs' => ['title' => 'Optional', 'class' => 'inputOptional']];
        }
        return $this->setStringIntoTag($inputFeatures['display'], 'span', $inputFeatures['ftrs']);
    }

    /**
     * Returns the name of a field for displaying
     *
     * @param array $details
     * @return string
     */
    private function getFieldNameForDisplay($details)
    {
        $tableUniqueId = $details['TABLE_SCHEMA'] . '.' . $details['TABLE_NAME'];
        if ($details['COLUMN_COMMENT'] != '') {
            return $details['COLUMN_COMMENT'];
        } elseif (isset($this->advCache['tableStructureLocales'][$tableUniqueId][$details['COLUMN_NAME']])) {
            return $this->advCache['tableStructureLocales'][$tableUniqueId][$details['COLUMN_NAME']];
        }
        return $details['COLUMN_NAME'];
    }

    /**
     * Returns a Enum or Set field to use in form
     *
     * @param string $tblSrc
     * @param string $fldType
     * @param array $val
     * @param array $iar
     * @return string
     */
    private function getFieldOutputEnumSet($tblSrc, $fldType, $val, $iar = [])
    {
        $adnlThings = $this->establishDefaultEnumSet($fldType);
        if (array_key_exists('readonly', $val)) {
            return $this->getFieldOutputEnumSetReadOnly($val, $adnlThings);
        }
        $inAdtnl = $adnlThings['additional'];
        if ($iar !== []) {
            $inAdtnl = array_merge($inAdtnl, $iar);
        }
        $vlSlct    = explode(',', $this->getFieldValue($val));
        $slctOptns = $this->getSetOrEnum2Array($tblSrc, $val['COLUMN_NAME']);
        return $this->setArrayToSelect($slctOptns, $vlSlct, $val['COLUMN_NAME'] . $adnlThings['suffix'], $inAdtnl);
    }

    private function getFieldOutputEnumSetReadOnly($val, $adnlThings)
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
     * Returns a Numeric field 2 use in a form
     *
     * @param string $tblSrc
     * @param array $value
     * @param array $features
     * @param array $iar
     * @return string
     */
    private function getFieldOutputNumeric($tblSrc, $value, $iar = [])
    {
        if ($value['EXTRA'] == 'auto_increment') {
            if ($this->getFieldValue($value) == '') {
                return $this->setStringIntoTag('auto-numar', 'span', [
                            'id'    => $value['COLUMN_NAME'],
                            'style' => 'font-style:italic;',
                ]);
            }
            $inAdtnl = [
                'type'  => 'hidden',
                'name'  => $value['COLUMN_NAME'],
                'id'    => $value['COLUMN_NAME'],
                'value' => $this->getFieldValue($value),
            ];
            if ($iar !== []) {
                $inAdtnl = array_merge($inAdtnl, $iar);
            }
            return $this->setStringIntoTag($this->getFieldValue($value), 'b')
                    . $this->setStringIntoShortTag('input', $inAdtnl);
        }
        $database = $this->advCache['workingDatabase'];
        $fkArray  = $this->getForeignKeysToArray($database, $tblSrc, $value['COLUMN_NAME']);
        if (is_null($fkArray)) {
            $fldNos  = $this->setFieldNumbers($value);
            $inAdtnl = [
                'type'      => 'text',
                'name'      => $value['COLUMN_NAME'],
                'id'        => $value['COLUMN_NAME'],
                'value'     => $this->getFieldValue($value),
                'size'      => min(50, $fldNos['l']),
                'maxlength' => min(50, $fldNos['l'])
            ];
            if ($iar !== []) {
                $inAdtnl = array_merge($inAdtnl, $iar);
            }
            return $this->setStringIntoShortTag('input', $inAdtnl);
        }
        $query         = $this->sQueryGenericSelectKeyValue([
            $fkArray[$value['COLUMN_NAME']][1],
            $fkArray[$value['COLUMN_NAME']][2],
            $fkArray[$value['COLUMN_NAME']][0]
        ]);
        $selectOptions = $this->setMySQLquery2Server($query, 'array_key_value')['result'];
        $selectValue   = $this->getFieldValue($value);
        $inAdtnl       = ['size' => 1];
        if ($value['IS_NULLABLE'] == 'YES') {
            $inAdtnl = array_merge($inAdtnl, ['include_null']);
        }
        if ($iar !== []) {
            $inAdtnl = array_merge($inAdtnl, $iar);
        }
        return $this->setArrayToSelect($selectOptions, $selectValue, $value['COLUMN_NAME'], $inAdtnl);
    }

    /**
     * Returns a Char field 2 use in a form
     *
     * @param string $tbl
     * @param string $fieldType
     * @param array $value
     * @param array $features
     * @param array $iar
     * @return string
     */
    private function getFieldOutputText($tbl, $fieldType, $value, $iar = [])
    {
        if (!in_array($fieldType, ['char', 'tinytext', 'varchar'])) {
            return '';
        }
        $database = $this->advCache['workingDatabase'];
        if (strpos($tbl, '`.`')) {
            $database = substr($tbl, 0, strpos($tbl, '`.`'));
        }
        if (($tbl != 'user_rights') && ($value['COLUMN_NAME'] != 'eid')) {
            $foreignKeysArray = $this->getForeignKeysToArray($database, $tbl, $value['COLUMN_NAME']);
            if (is_null($foreignKeysArray)) {
                unset($foreignKeysArray);
            }
        }
        if (isset($foreignKeysArray)) {
            $query   = $this->storedQuery('generic_select_key_value', [
                $foreignKeysArray[$value['COLUMN_NAME']][1],
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
                'Options' => $this->setQuery2Server($query, 'array_key_value'),
                'Value'   => $this->getFieldValue($value),
            ];
            return $this->setArrayToSelect($slct['Options'], $slct['Value'], $value['COLUMN_NAME'], $inAdtnl);
        }
        $fldNos  = $this->setFieldNumbers($value);
        $inAdtnl = [
            'type'      => ($value['COLUMN_NAME'] == 'password' ? 'password' : 'text'),
            'name'      => $value['COLUMN_NAME'],
            'id'        => $value['COLUMN_NAME'],
            'size'      => min(30, $fldNos['l']),
            'maxlength' => min(255, $fldNos['l']),
            'value'     => $this->getFieldValue($value),
        ];
        if ($iar !== []) {
            $inAdtnl = array_merge($inAdtnl, $iar);
        }
        return $this->setStringIntoShortTag('input', $inAdtnl);
    }

    /**
     * Returns a Text field 2 use in a form
     *
     * @param string $table_source
     * @param string $fieldType
     * @param array $value
     * @param array $features
     * @param array $iar
     * @return string
     */
    private function getFieldOutputTextLarge($fieldType, $value, $iar = [])
    {
        if (!in_array($fieldType, ['blob', 'text'])) {
            return '';
        }
        $inAdtnl = [
            'name' => $value['COLUMN_NAME'],
            'id'   => $value['COLUMN_NAME'],
            'rows' => 4,
            'cols' => 55,
        ];
        if ($iar !== []) {
            $inAdtnl = array_merge($inAdtnl, $iar);
        }
        return $this->setStringIntoTag($this->getFieldValue($value), 'textarea', $inAdtnl);
    }

    /**
     * Returns a Time field 2 use in a form
     *
     * @param array $value
     * @param array $iar
     * @return string
     */
    private function getFieldOutputTime($value, $iar = [])
    {
        $inAdtnl = [
            'type'      => 'text',
            'size'      => 9,
            'maxlength' => 9,
            'name'      => $value['COLUMN_NAME'],
            'id'        => $value['COLUMN_NAME'],
            'value'     => $this->getFieldValue($value),
        ];
        if ($iar !== []) {
            $inAdtnl = array_merge($inAdtnl, $iar);
        }
        return $this->setStringIntoShortTag('input', $inAdtnl);
    }

    /**
     * Returns a Timestamp field 2 use in a form
     *
     * @param array $value
     * @param array $iar
     * @return string
     */
    private function getFieldOutputTimestamp($value, $iar = [])
    {
        $inAdtnl = [
            'type'      => 'text',
            'size'      => 19,
            'maxlength' => 19,
            'name'      => $value['COLUMN_NAME'],
            'id'        => $value['COLUMN_NAME'],
            'value'     => $this->getFieldValue($value),
        ];
        if ($iar !== []) {
            $inAdtnl = array_merge($inAdtnl, $iar);
        }
        $input = $this->setStringIntoShortTag('input', $inAdtnl);
        if (!array_key_exists('readonly', $iar)) {
            $input .= $this->setCalendarControlWithTime($value['COLUMN_NAME']);
        }
        return $input;
    }

    /**
     * Returns a Year field 2 use in a form
     *
     * @param array $details
     * @param array $iar
     * @return string
     */
    private function getFieldOutputYear($details, $iar)
    {
        for ($c = 1901; $c <= 2155; $c++) {
            $listOfValues[$c] = $c;
        }
        if ($iar == []) {
            $slDflt = $this->getFieldValue($details);
            return $this->setArrayToSelect($listOfValues, $slDflt, $details['COLUMN_NAME'], ['size' => 1]);
        }
        return $this->getFieldOutputText('varchar', $details, $iar);
    }

    /**
     * Returns given value for a field from $_REQUEST
     *
     * @param array $details
     * @return string
     */
    private function getFieldValue($details)
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
                    $targetTblTxtFlds       = $this->setMySQLquery2Server($query, 'full_array_key_numbered')['result'];
                    $significance           = $targetTblTxtFlds[0]['COLUMN_NAME'];
                    unset($targetTblTxtFlds);
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
     * @param string $refTbl
     * @param string $refCol
     * @return array
     */
    protected function getSetOrEnum2Array($refTbl, $refCol)
    {
        if ((strpos($refTbl, '`') !== false) && (substr($refTbl, 0, 1) != '`')) {
            $refTbl = '`' . $refTbl . '`';
        }
        $dat = $this->establishDatabaseAndTable($refTbl);
        foreach ($this->advCache['tableStructureCache'][$dat[0]][$dat[1]] as $value) {
            if ($value['COLUMN_NAME'] == $refCol) {
                $clndVls = explode(',', str_replace([$value['DATA_TYPE'], '(', "'", ')'], '', $value['COLUMN_TYPE']));
                $enmVls  = array_combine($clndVls, $clndVls);
                if ($value['IS_NULLABLE'] == 'YES') {
                    $enmVls['NULL'] = '';
                }
            }
        }
        ksort($enmVls);
        return $enmVls;
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
     * Manages features flag
     *
     * @param string $fieldName
     * @param array $features
     * @return string
     */
    private function handleFeatures($fieldName, $features)
    {
        $rOly  = $this->handleFeaturesSingle($fieldName, $features, 'readonly');
        $rDbld = $this->handleFeaturesSingle($fieldName, $features, 'disabled');
        $rNl   = [];
        if (isset($features['include_null']) && in_array($fieldName, $features['include_null'])) {
            $rNl = ['include_null'];
        }
        return array_merge([], $rOly, $rDbld, $rNl);
    }

    private function handleFeaturesSingle($fieldName, $features, $featureKey)
    {
        $fMap    = [
            'readonly' => ['readonly', 'class', 'input_readonly'],
            'disabled' => ['disabled']
        ];
        $aReturn = [];
        if (array_key_exists($featureKey, $features)) {
            if (array_key_exists($fieldName, $features[$featureKey])) {
                $aReturn[$featureKey][$fMap[$featureKey][0]] = $fMap[$featureKey][0];
                if (count($fMap[$featureKey]) > 1) {
                    $aReturn[$featureKey][$fMap[$featureKey][1]] = $fMap[$featureKey][2];
                }
            }
        }
        return $aReturn;
    }

    /**
     * Returns a generic form based on a given table
     *
     * @param string $tblSrc Table Source
     * @param array $feat
     * @param string/array $hiddenInfo List of hidden fields
     *
     * @return string Form to add/modify detail for a single row within a table
     */
    protected function setFormGenericSingleRecord($tblSrc, $feat, $hiddenInfo = '')
    {
        echo $this->setStringIntoTag('', 'div', [
            'id' => 'loading'
        ]); // Ajax container
        if (strpos($tblSrc, '.') !== false) {
            $tblSrc = explode('.', str_replace('`', '', $tblSrc))[1];
        }
        $this->setTableCache($tblSrc); // will populate $this->advCache['tableStructureCache'][$dt[0]][$dt[1]]
        if (count($this->advCache['tableStructureCache'][$this->advCache['workingDatabase']][$tblSrc]) != 0) {
            foreach ($this->advCache['tableStructureCache'][$this->advCache['workingDatabase']][$tblSrc] as $value) {
                $sReturn[] = $this->setNeededField($tblSrc, $value, $feat);
            }
        }
        $btn[]                = $this->setStringIntoShortTag('input', [
            'type'  => 'submit',
            'id'    => 'submit',
            'style' => 'margin-left:220px;',
            'value' => $this->lclMsgCmn('i18n_Form_ButtonSave'),
        ]);
        $adtnlScriptAfterForm = $this->setJavascriptContent(implode('', [
            '$(document).ready(function(){',
            '$("form#' . $feat['id'] . '").submit(function(){',
            '$("input").attr("readonly", true);',
            '$("input[type=submit]").attr("disabled", "disabled");',
            '$("input[type=submit]").attr("value", "' . $this->lclMsgCmn('i18n_Form_ButtonSaving') . '");',
            '});',
            '});',
        ]));
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
                ])
                . $adtnlScriptAfterForm;
    }

    protected function setTableLocaleFields($localizationStrings)
    {
        $this->advCache['tableStructureLocales'] = $localizationStrings;
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
        $fieldLabel = $this->getFieldNameForDisplay($details);
        if ($fieldLabel == 'hidden') {
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
                $sReturn['label'] = $this->setStringIntoTag($fieldLabel, 'label', $aLabel);
                $result           = $this->setNeededFieldByType($tableSource, $details, $features);
                if ($details['COLUMN_NAME'] == 'ChoiceId') {
                    $result = $this->setStringIntoShortTag('input', [
                        'type'  => 'text',
                        'name'  => $details['COLUMN_NAME'],
                        'value' => $_REQUEST[$details['COLUMN_NAME']]
                    ]);
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
     * @param string $tblName
     * @param array $details
     * @param array $features
     * @return string|array
     */
    private function setNeededFieldByType($tblName, $details, $features)
    {
        $sReturn = null;
        if (isset($features['special']) && isset($features['special'][$details['COLUMN_NAME']])) {
            $slctOpt = $this->setQuery2Server($features['special'][$details['COLUMN_NAME']], 'array_key_value');
            $sReturn = $this->setArrayToSelect($slctOpt, $this->getFieldValue($details), $details['COLUMN_NAME'], [
                'size' => 1
            ]);
        } else {
            $iar = $this->handleFeatures($details['COLUMN_NAME'], $features);
            switch ($details['DATA_TYPE']) {
                case 'bigint':
                case 'int':
                case 'mediumint':
                case 'smallint':
                case 'tinyint':
                case 'float':
                case 'double':
                case 'decimal':
                case 'numeric':
                    $sReturn = $this->getFieldOutputNumeric($tblName, $details, $iar);
                    break;
                case 'char':
                case 'tinytext':
                case 'varchar':
                    $sReturn = $this->getFieldOutputText($tblName, $details['DATA_TYPE'], $details, $iar);
                    break;
                case 'date':
                    $sReturn = $this->getFieldOutputDate($details['DATA_TYPE'], $details, $iar);
                    break;
                case 'datetime':
                case 'timestamp':
                    $sReturn = $this->getFieldOutputTimestamp($details, $iar);
                    break;
                case 'enum':
                case 'set':
                    $sReturn = $this->getFieldOutputEnumSet($tblName, $details['DATA_TYPE'], $details, $iar);
                    break;
                case 'text':
                case 'blob':
                    $sReturn = $this->getFieldOutputTextLarge($details['DATA_TYPE'], $details, $iar);
                    break;
                case 'time':
                    $sReturn = $this->getFieldOutputTime($details, $iar);
                    break;
                case 'year':
                    $sReturn = $this->getFieldOutputYear($details, $iar);
                    break;
            }
        }
        return $this->getFieldCompletionType($details) . $sReturn;
    }

    /**
     * create a Cache for given table to use it in many places
     *
     * @param type $tblSrc
     */
    private function setTableCache($tblSrc)
    {
        $dat = $this->establishDatabaseAndTable($tblSrc);
        if (!isset($this->advCache['tableStructureCache'][$dat[0]][$dat[1]])) {
            switch ($dat[1]) {
                case 'user_rights':
                    $this->advCache['workingDatabase'] = 'usefull_security';
                    break;
                default:
                    $this->advCache['workingDatabase'] = $dat[0];
                    break;
            }
            $this->advCache['tableStructureCache'][$dat[0]][$dat[1]] = $this->getMySQLlistColumns([
                'TABLE_SCHEMA' => $dat[0],
                'TABLE_NAME'   => $dat[1],
            ]);
            $this->setTableForeginKeyCache($dat[0], $dat[1]);
        }
    }

    private function setTableForeginKeyCache($dbName, $tblName)
    {
        $frgnKs = $this->getMySQLlistIndexes([
            'TABLE_SCHEMA'          => $dbName,
            'TABLE_NAME'            => $tblName,
            'REFERENCED_TABLE_NAME' => 'NOT NULL',
        ]);
        if (!is_null($frgnKs)) {
            $this->advCache['tableFKs'][$dbName][$tblName] = $frgnKs;
            $this->advCache['FKcol'][$dbName][$tblName]    = array_column($frgnKs, 'COLUMN_NAME', 'CONSTRAINT_NAME');
        }
    }

    private function setViewDeleteFeedbacks()
    {
        return [
            'Confirmation' => $this->lclMsgCmn('i18n_Action_Confirmation'),
            'Failed'       => $this->lclMsgCmn('i18n_ActionDelete_Failed'),
            'Impossible'   => $this->lclMsgCmn('i18n_ActionDelete_Impossible'),
            'Success'      => $this->lclMsgCmn('i18n_ActionDelete_Success'),
        ];
    }

    private function setViewDeletePackedFinal($sReturn)
    {
        $finalJavascript = $this->setJavascriptContent(implode('', [
            '$("#DeleteFeedback").fadeOut(4000, function() {',
            '$(this).remove();',
            '});',
        ]));
        return '<div id="DeleteFeedback">' . $sReturn . '</div>' . $finalJavascript;
    }

    /**
     * Automatic handler for Record deletion
     *
     * @param string $tbl
     * @param string $idn
     * @return string
     */
    protected function setViewModernDelete($tbl, $idn)
    {
        $tMsg = $this->setViewDeleteFeedbacks();
        if ($tbl == '') {
            $sReturn = $this->setFeedbackModern('error', $tMsg['Confirmation'], $tMsg['Impossible']);
        } else {
            $this->setMySQLquery2Server($this->sQueryToDeleteSingleIdentifier([$tbl, $idn, $_REQUEST[$idn]]));
            $sReturn = $this->setFeedbackModern('error', $tMsg['Confirmation'], $tMsg['Failed'])
                    . '(' . $this->mySQLconnection->error . ')';
            if ($this->mySQLconnection->affected_rows > 0) {
                $sReturn = $this->setFeedbackModern('check', $tMsg['Confirmation'], $tMsg['Success']);
            }
        }
        return $this->setViewDeletePackedFinal($sReturn);
    }
}
