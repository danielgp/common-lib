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

    use MySQLiByDanielGP;

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

    private function fixTableSource($refTbl)
    {
        $outS = [];
        if (substr($refTbl, 0, 1) !== '`') {
            $outS[] = '`';
        }
        $psT = strpos($refTbl, '.`');
        if ($psT !== false) {
            $refTbl = substr($refTbl, $psT + 2, strlen($refTbl) - $psT);
        }
        $outS[] = $refTbl;
        if (substr($refTbl, -1) !== '`') {
            $outS[] = '`';
        }
        return implode('', $outS);
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
     * Returns a Date field 2 use in a form
     *
     * @param array $value
     * @return string
     */
    private function getFieldOutputDate($value)
    {
        $defaultValue = $this->getFieldValue($value);
        if (is_null($defaultValue)) {
            $defaultValue = date('Y-m-d');
        }
        $inA = [
            'type'      => 'text',
            'name'      => $value['Field'],
            'id'        => $value['Field'],
            'value'     => $defaultValue,
            'size'      => 10,
            'maxlength' => 10,
            'onfocus'   => implode('', [
                'javascript:NewCssCal(\'' . $value['Field'],
                '\',\'yyyyMMdd\',\'dropdown\',false,\'24\',false);',
            ]),
        ];
        return $this->setStringIntoShortTag('input', $inA) . $this->setCalendarControl($value['Field']);
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
     * @param array $iar
     * @return string
     */
    private function getFieldOutputNumeric($tblSrc, $value, $iar = [])
    {
        if ($value['EXTRA'] == 'auto_increment') {
            return $this->getFieldOutputNumericAI($value, $iar);
        }
        $fkArray = $this->getForeignKeysToArray($this->advCache['workingDatabase'], $tblSrc, $value['COLUMN_NAME']);
        if (is_null($fkArray)) {
            $fldNos = $this->setFieldNumbers($value);
            return $this->getFieldOutputTT($value, min(50, $fldNos['l']), $iar);
        }
        return $this->getFieldOutputNumericNonFK($fkArray, $value, $iar);
    }

    private function getFieldOutputNumericAI($value, $iar = [])
    {
        if ($this->getFieldValue($value) == '') {
            $spF = ['id' => $value['COLUMN_NAME'], 'style' => 'font-style:italic;'];
            return $this->setStringIntoTag('auto-numar', 'span', $spF);
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
        return '<b>' . $this->getFieldValue($value) . '</b>' . $this->setStringIntoShortTag('input', $inAdtnl);
    }

    private function getFieldOutputNumericNonFK($fkArray, $value, $iar = [])
    {
        $query         = $this->sQueryGenericSelectKeyValue([
            $fkArray[$value['COLUMN_NAME']][1],
            $fkArray[$value['COLUMN_NAME']][2],
            $fkArray[$value['COLUMN_NAME']][0],
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
     * @param array $iar
     * @return string
     */
    private function getFieldOutputText($tbl, $fieldType, $value, $iar = [])
    {
        if (!in_array($fieldType, ['char', 'tinytext', 'varchar'])) {
            return '';
        }
        $foreignKeysArray = $this->getFieldOutputTextPrerequisites($tbl, $value);
        if (is_null($foreignKeysArray)) {
            return $this->getFieldOutputTextFK($foreignKeysArray, $value, $iar);
        }
        return $this->getFieldOutputTextNonFK($value, $iar);
    }

    private function getFieldOutputTextFK($foreignKeysArray, $value, $iar)
    {
        $query   = $this->sQueryGenericSelectKeyValue([
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
            'Options' => $this->setMySQLquery2Server($query, 'array_key_value'),
            'Value'   => $this->getFieldValue($value),
        ];
        return $this->setArrayToSelect($slct['Options'], $slct['Value'], $value['COLUMN_NAME'], $inAdtnl);
    }

    /**
     * Returns a Text field 2 use in a form
     *
     * @param string $fieldType
     * @param array $value
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

    private function getFieldOutputTextNonFK($value, $iar)
    {
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

    private function getFieldOutputTextPrerequisites($tbl, $value)
    {
        $foreignKeysArray = null;
        if (($tbl != 'user_rights') && ($value['COLUMN_NAME'] != 'eid')) {
            $database = $this->advCache['workingDatabase'];
            if (strpos($tbl, '`.`')) {
                $database = substr($tbl, 0, strpos($tbl, '`.`'));
            }
            $foreignKeysArray = $this->getForeignKeysToArray($database, $tbl, $value['COLUMN_NAME']);
        }
        return $foreignKeysArray;
    }

    private function getFieldOutputTT($value, $szN, $iar = [])
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
     * Returns a Time field 2 use in a form
     *
     * @param array $value
     * @param array $iar
     * @return string
     */
    private function getFieldOutputTime($value, $iar = [])
    {
        return $this->getFieldOutputTT($value, 8, $iar);
    }

    /**
     * Returns a Timestamp field 2 use in a form
     *
     * @param array $dtl
     * @param array $iar
     * @return string
     */
    private function getFieldOutputTimestamp($dtl, $iar = [])
    {
        if (($dtl['COLUMN_DEFAULT'] == 'CURRENT_TIMESTAMP') || ($dtl['EXTRA'] == 'on update CURRENT_TIMESTAMP')) {
            return $this->getTimestamping($dtl);
        }
        $input = $this->getFieldOutputTT($dtl, 19, $iar);
        if (!array_key_exists('readonly', $iar)) {
            $input .= $this->setCalendarControlWithTime($dtl['COLUMN_NAME']);
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
    private function getFieldOutputYear($tblName, $details, $iar)
    {
        $listOfValues = [];
        for ($cntr = 1901; $cntr <= 2155; $cntr++) {
            $listOfValues[$cntr] = $cntr;
        }
        if ($iar == []) {
            $slDflt = $this->getFieldValue($details);
            return $this->setArrayToSelect($listOfValues, $slDflt, $details['COLUMN_NAME'], ['size' => 1]);
        }
        return $this->getFieldOutputText($tblName, 'varchar', $details, $iar);
    }

    /**
     * Returns given value for a field from REQUEST global variable
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

    private function getForeignKeysQuery($value)
    {
        $flt = [
            'TABLE_SCHEMA' => $value['REFERENCED_TABLE_SCHEMA'],
            'TABLE_NAME'   => $value['REFERENCED_TABLE_NAME'],
            'DATA_TYPE'    => ['char', 'varchar', 'text'],
        ];
        return $this->sQueryMySqlColumns($flt);
    }

    /**
     * Returns an array with fields referenced by a Foreign key
     *
     * @param string $database
     * @param string $tblName
     * @param string|array $onlyCol
     * @return array
     */
    private function getForeignKeysToArray($database, $tblName, $onlyCol = '')
    {
        $this->setTableForeginKeyCache($database, $this->fixTableSource($tblName));
        $array2return = null;
        if (isset($this->advCache['tableFKs'][$database][$tblName])) {
            foreach ($this->advCache['tableFKs'][$database][$tblName] as $value) {
                if ($value['COLUMN_NAME'] == $onlyCol) {
                    $query                  = $this->getForeignKeysQuery($value);
                    $targetTblTxtFlds       = $this->setMySQLquery2Server($query, 'full_array_key_numbered')['result'];
                    $array2return[$onlyCol] = [
                        $this->glueDbTb($value['REFERENCED_TABLE_SCHEMA'], $value['REFERENCED_TABLE_NAME']),
                        $value['REFERENCED_COLUMN_NAME'],
                        '`' . $targetTblTxtFlds[0]['COLUMN_NAME'] . '`',
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
        $dat = $this->establishDatabaseAndTable($this->fixTableSource($refTbl));
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
     * @param array $dtl
     * @return array
     */
    private function getTimestamping($dtl)
    {
        $inM = $this->setStringIntoTag($this->getFieldValue($dtl), 'span');
        if (in_array($this->getFieldValue($dtl), ['', 'CURRENT_TIMESTAMP', 'NULL'])) {
            $mCN = [
                'InsertDateTime'        => 'data/timpul ad. informatiei',
                'ModificationDateTime'  => 'data/timpul modificarii inf.',
                'modification_datetime' => 'data/timpul modificarii inf.',
            ];
            if (array_key_exists($dtl['COLUMN_NAME'], $mCN)) {
                $inM = $this->setStringIntoTag($mCN[$dtl['COLUMN_NAME']], 'span', ['style' => 'font-style:italic;']);
            }
        }
        return ['label' => $this->getLabel($dtl), 'input' => $inM];
    }

    private function glueDbTb($dbName, $tbName)
    {
        return '`' . $dbName . '`.`' . $tbName . '`';
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

    private function setField($tableSource, $dtl, $features, $fieldLabel)
    {
        if ($dtl['COLUMN_NAME'] == 'host') {
            $inVl = gethostbyaddr($this->tCmnRequest->server->get('REMOTE_ADDR'));
            return [
                'label' => '<label for="' . $dtl['COLUMN_NAME'] . '">Numele calculatorului</label>',
                'input' => '<input type="text" name="host" size="15" readonly value="' . $inVl . '" />',
            ];
        }
        $result = $this->setFieldInput($tableSource, $dtl, $features);
        return ['label' => $this->setFieldLabel($dtl, $features, $fieldLabel), 'input' => $result];
    }

    private function setFieldInput($tableSource, $dtl, $features)
    {
        if ($dtl['COLUMN_NAME'] == 'ChoiceId') {
            return '<input type="text" name="ChoiceId" value="'
                    . $this->tCmnRequest->request->get($dtl['COLUMN_NAME']) . '" />';
        }
        return $this->setNeededFieldByType($tableSource, $dtl, $features);
    }

    private function setFieldLabel($details, $features, $fieldLabel)
    {
        $aLabel = ['for' => $details['COLUMN_NAME'], 'id' => $details['COLUMN_NAME'] . '_label'];
        if (isset($features['disabled'])) {
            if (in_array($details['COLUMN_NAME'], $features['disabled'])) {
                $aLabel = array_merge($aLabel, ['style' => 'color: grey;']);
            }
        }
        return $this->setStringIntoTag($fieldLabel, 'label', $aLabel);
    }

    private function setFormButtons($feat, $hiddenInfo)
    {
        $btn   = [];
        $btn[] = '<input type="submit" id="submit" style="margin-left:220px;" value="'
                . $this->lclMsgCmn('i18n_Form_ButtonSave') . '" />';
        if (isset($feat['insertAndUpdate'])) {
            $btn[] = '<input type="hidden" id="insertAndUpdate" name="insertAndUpdate" value="insertAndUpdate" />';
        }
        if (is_array($hiddenInfo)) {
            foreach ($hiddenInfo as $key => $value) {
                $btn[] = '<input type="hidden" id="' . $key . '" name="' . $key . '" value="' . $value . '" />';
            }
        }
        return '<div>' . implode('', $btn) . '</div>';
    }

    private function setFormJavascriptFinal($frmId)
    {
        $cnt = implode('', [
            '$(document).ready(function(){',
            '$("form#' . $frmId . '").submit(function(){',
            '$("input").attr("readonly", true);',
            '$("input[type=submit]").attr("disabled", "disabled");',
            '$("input[type=submit]").attr("value", "' . $this->lclMsgCmn('i18n_Form_ButtonSaving') . '");',
            '});',
            '});',
        ]);
        return $this->setJavascriptContent($cnt);
    }

    /**
     * Returns a generic form based on a given table
     *
     * @param string $tblSrc Table Source
     * @param array $feat
     * @param string|array $hdnInf List of hidden fields
     *
     * @return string Form to add/modify detail for a single row within a table
     */
    protected function setFormGenericSingleRecord($tblSrc, $feat, $hdnInf = '')
    {
        echo $this->setStringIntoTag('', 'div', ['id' => 'loading']);
        if (strpos($tblSrc, '.') !== false) {
            $tblSrc = explode('.', str_replace('`', '', $tblSrc))[1];
        }
        $this->setTableCache($tblSrc);
        $sReturn = [];
        if (count($this->advCache['tableStructureCache'][$this->advCache['workingDatabase']][$tblSrc]) != 0) {
            foreach ($this->advCache['tableStructureCache'][$this->advCache['workingDatabase']][$tblSrc] as $value) {
                $sReturn[] = $this->setNeededField($tblSrc, $value, $feat);
            }
        }
        $frmFtrs = ['id' => $feat['id'], 'action' => $feat['action'], 'method' => $feat['method']];
        return $this->setStringIntoTag(implode('', $sReturn) . $this->setFormButtons($feat, $hdnInf), 'form', $frmFtrs)
                . $this->setFormJavascriptFinal($feat['id']);
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
        return $this->setNeededFieldFinal($tableSource, $details, $features, $fieldLabel);
    }

    /**
     * Analyse the field type and returns the proper lines 2 use in forms
     *
     * @param string $tblName
     * @param array $dtls
     * @param array $features
     * @return string|array
     */
    private function setNeededFieldByType($tblName, $dtls, $features)
    {
        if (isset($features['special']) && isset($features['special'][$dtls['COLUMN_NAME']])) {
            $sOpt = $this->setMySQLquery2Server($features['special'][$dtls['COLUMN_NAME']], 'array_key_value');
            return $this->setArrayToSelect($sOpt, $this->getFieldValue($dtls), $dtls['COLUMN_NAME'], ['size' => 1]);
        }
        return $this->setNeededFieldKnown($tblName, $dtls, $features);
    }

    private function setNeededFieldKnown($tblName, $dtls, $features)
    {
        $iar      = $this->handleFeatures($dtls['COLUMN_NAME'], $features);
        $sReturn  = '';
        $numTypes = ['bigint', 'int', 'mediumint', 'smallint', 'tinyint', 'float', 'double', 'decimal', 'numeric'];
        if (in_array($dtls['DATA_TYPE'], $numTypes)) {
            $sReturn = $this->getFieldOutputNumeric($tblName, $dtls, $iar);
        } elseif (in_array($dtls['DATA_TYPE'], ['char', 'tinytext', 'varchar', 'enum', 'set', 'text', 'blob'])) {
            $sReturn = $this->setNeededFieldTextRelated($tblName, $dtls, $iar);
        } elseif (in_array($dtls['DATA_TYPE'], ['date', 'datetime', 'time', 'timestamp', 'year'])) {
            $sReturn = $this->setNeededFieldSingleType($tblName, $dtls, $iar);
        }
        return $this->getFieldCompletionType($dtls) . $sReturn;
    }

    private function setNeededFieldFinal($tableSource, $details, $features, $fieldLabel)
    {
        $sReturn     = $this->setField($tableSource, $details, $features, $fieldLabel);
        $finalReturn = $sReturn['label'] . $this->setStringIntoTag($sReturn['input'], 'span', ['class' => 'labell']);
        $wrkDb       = $this->advCache['workingDatabase'];
        if (isset($this->advCache['tableFKs'][$wrkDb][$tableSource])) {
            if (in_array($details['COLUMN_NAME'], $this->advCache['FKcol'][$wrkDb][$tableSource])) {
                $finalReturn .= $this->setFieldNumbers($details);
            }
        }
        return '<div>' . $finalReturn . '</div>';
    }

    private function setNeededFieldSingleType($tblName, $dtls, $iar)
    {
        if ($dtls['DATA_TYPE'] == 'date') {
            return $this->getFieldOutputDate($dtls);
        } elseif ($dtls['DATA_TYPE'] == 'time') {
            return $this->getFieldOutputTime($dtls, $iar);
        } elseif (in_array($dtls['DATA_TYPE'], ['datetime', 'timestamp'])) {
            return $this->getFieldOutputTimestamp($dtls, $iar);
        }
        return $this->getFieldOutputYear($tblName, $dtls, $iar);
    }

    private function setNeededFieldTextRelated($tblName, $dtls, $iar)
    {
        if (in_array($dtls['DATA_TYPE'], ['char', 'tinytext', 'varchar'])) {
            return $this->getFieldOutputText($tblName, $dtls['DATA_TYPE'], $dtls, $iar);
        } elseif (in_array($dtls['DATA_TYPE'], ['text', 'blob'])) {
            return $this->getFieldOutputTextLarge($dtls['DATA_TYPE'], $dtls, $iar);
        }
        return $this->getFieldOutputEnumSet($tblName, $dtls['DATA_TYPE'], $dtls, $iar);
    }

    /**
     * create a Cache for given table to use it in many places
     *
     * @param type $tblSrc
     */
    private function setTableCache($tblSrc)
    {
        $dat = $this->establishDatabaseAndTable($this->fixTableSource($tblSrc));
        if (!isset($this->advCache['tableStructureCache'][$dat[0]][$dat[1]])) {
            $this->advCache['workingDatabase'] = $dat[0];
            if ($dat[1] == 'user_rights') {
                $this->advCache['workingDatabase'] = 'usefull_security';
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
}
