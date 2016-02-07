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

    use MySQLiByDanielGPstructures;

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
            if (!array_key_exists('workingDatabase', $this->advCache)) {
                $this->advCache['workingDatabase'] = $this->getMySqlCurrentDatabase();
            }
            return [$this->advCache['workingDatabase'], $tblSrc];
        }
        return explode('.', str_replace('`', '', $tblSrc));
    }

    /**
     * Returns the name of a field for displaying
     *
     * @param array $details
     * @return string
     */
    protected function getFieldNameForDisplay($details)
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

    /**
     * Handles creation of Auto Increment numeric field type output
     *
     * @param array $value
     * @param array $iar
     * @return string
     */
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

    /**
     * Builds field output type for numeric types if not FK
     *
     * @param array $fkArray
     * @param array $value
     * @param array $iar
     * @return string
     */
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
        if (!is_null($foreignKeysArray)) {
            return $this->getFieldOutputTextFK($foreignKeysArray, $value, $iar);
        }
        return $this->getFieldOutputTextNonFK($value, $iar);
    }

    /**
     * Returns a Text field 2 use in a form
     *
     * @param string $fieldType
     * @param array $value
     * @param array $iar
     * @return string
     */
    protected function getFieldOutputTextLarge($fieldType, $value, $iar = [])
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
     * Prepares the text output fields
     *
     * @param string $tbl
     * @param array $value
     * @return null|array
     */
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
            return $this->getTimestamping($dtl)['input'];
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
     * Returns an array with fields referenced by a Foreign key
     *
     * @param string $database
     * @param string $tblName
     * @param string|array $onlyCol
     * @return array
     */
    private function getForeignKeysToArray($database, $tblName, $onlyCol = '')
    {
        $this->setTableForeignKeyCache($database, $this->fixTableSource($tblName));
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

    /**
     * Returns an array with possible values of a SET or ENUM column
     *
     * @param string $refTbl
     * @param string $refCol
     * @return array
     */
    protected function getSetOrEnum2Array($refTbl, $refCol)
    {
        $dat = $this->establishDatabaseAndTable($refTbl);
        foreach ($this->advCache['tableStructureCache'][$dat[0]][$dat[1]] as $value) {
            if ($value['COLUMN_NAME'] == $refCol) {
                $clndVls = explode(',', str_replace([$value['DATA_TYPE'], '(', "'", ')'], '', $value['COLUMN_TYPE']));
                $enmVls  = array_combine($clndVls, $clndVls);
                if ($value['IS_NULLABLE'] === 'YES') {
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
        $fieldValue = $this->getFieldValue($dtl);
        $inM        = $this->setStringIntoTag($fieldValue, 'span');
        if (in_array($fieldValue, ['', 'CURRENT_TIMESTAMP', 'NULL'])) {
            $mCN = [
                'InsertDateTime'        => 'data/timpul ad. informatiei',
                'ModificationDateTime'  => 'data/timpul modificarii inf.',
                'modification_datetime' => 'data/timpul modificarii inf.',
            ];
            if (array_key_exists($dtl['COLUMN_NAME'], $mCN)) {
                $inM = $this->setStringIntoTag($mCN[$dtl['COLUMN_NAME']], 'span', ['style' => 'font-style:italic;']);
            }
        }
        $lbl = '<span class="fake_label">' . $this->getFieldNameForDisplay($details) . '</span>';
        return ['label' => $lbl, 'input' => $inM];
    }

    protected function setNeededFieldKnown($tblName, $dtls, $features)
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
     * @param string $tblSrc
     */
    protected function setTableCache($tblSrc)
    {
        $dat = $this->establishDatabaseAndTable($tblSrc);
        if (!isset($this->advCache['tableStructureCache'][$dat[0]][$dat[1]])) {
            $this->advCache['workingDatabase']                       = $dat[0];
            $this->advCache['tableStructureCache'][$dat[0]][$dat[1]] = $this->getMySQLlistColumns([
                'TABLE_SCHEMA' => $dat[0],
                'TABLE_NAME'   => $dat[1],
            ]);
            $this->setTableForeignKeyCache($dat[0], $dat[1]);
        }
    }

    private function setTableForeignKeyCache($dbName, $tblName)
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
