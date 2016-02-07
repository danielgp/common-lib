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
trait CommonViews
{

    use MySQLiAdvancedOutput;

    /**
     * Builds field output w. special column name
     *
     * @param string $tableSource
     * @param array $dtl
     * @param array $features
     * @param string $fieldLabel
     * @return array
     */
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

    /**
     * Builds field output w. another special column name
     *
     * @param string $tableSource
     * @param array $dtl
     * @param array $features
     * @return string
     */
    private function setFieldInput($tableSource, $dtl, $features)
    {
        if ($dtl['COLUMN_NAME'] == 'ChoiceId') {
            return '<input type="text" name="ChoiceId" value="'
                    . $this->tCmnRequest->request->get($dtl['COLUMN_NAME']) . '" />';
        }
        return $this->setNeededFieldByType($tableSource, $dtl, $features);
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
     * Returns a generic form based on a given table
     *
     * @param string $tblSrc
     * @param array $feat
     * @param array $hdnInf
     *
     * @return string Form to add/modify detail for a single row within a table
     */
    protected function setFormGenericSingleRecord($tblSrc, $feat, $hdnInf = [])
    {
        echo $this->setStringIntoTag('', 'div', ['id' => 'loading']);
        $this->setTableCache($tblSrc);
        if (strpos($tblSrc, '.') !== false) {
            $tblSrc = explode('.', str_replace('`', '', $tblSrc))[1];
        }
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

    private function setNeededFieldFinal($tableSource, $details, $features, $fieldLabel)
    {
        $sReturn = $this->setField($tableSource, $details, $features, $fieldLabel);
        $lmts    = $this->setFieldNumbers($details);
        return '<div>' . $sReturn['label']
                . $this->setStringIntoTag($sReturn['input'], 'span', ['class' => 'labell'])
                . '<span style="font-size:x-small;font-style:italic;">&nbsp;(max. '
                . $lmts['M'] . (isset($lmts['d']) ? ' w. ' . $lmts['d'] . ' decimals' : '') . ')</span>'
                . '</div>';
    }

    protected function setTableLocaleFields($localizationStrings)
    {
        $this->advCache['tableStructureLocales'] = $localizationStrings;
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
            $idFldVal = $this->tCmnRequest->request->get($idn);
            $this->setMySQLquery2Server($this->sQueryToDeleteSingleIdentifier([$tbl, $idn, $idFldVal]));
            $sReturn  = $this->setFeedbackModern('error', $tMsg['Confirmation'], $tMsg['Failed'])
                    . '(' . $this->mySQLconnection->error . ')';
            if ($this->mySQLconnection->affected_rows > 0) {
                $sReturn = $this->setFeedbackModern('check', $tMsg['Confirmation'], $tMsg['Success']);
            }
        }
        return $this->setViewDeletePackedFinal($sReturn);
    }
}
