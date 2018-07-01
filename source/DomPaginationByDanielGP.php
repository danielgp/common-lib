<?php

/*
 * The MIT License
 *
 * Copyright 2018 Daniel Popiniuc
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace danielgp\common_lib;

/**
 * DOM component functions
 *
 * @author Daniel Popiniuc
 */
trait DomPaginationByDanielGP
{

    use DomBasicComponentsByDanielGP,
        DomDynamicSelectByDanielGP;

    private function normalizeArrayForUrl($featArray)
    {
        $outArray = [];
        foreach ($featArray as $key => $value) {
            if (is_numeric($key)) {
                $outArray[$value] = 1;
            } else {
                $outArray[$key] = $value;
            }
        }
        return $outArray;
    }

    private function normalizeFeatureArray($featArray)
    {
        $nonAsociative = ['autosubmit', 'disabled', 'hidden', 'include_null', 'multiselect'];
        foreach ($featArray as $key => $value) {
            if (in_array($value, $nonAsociative)) {
                $featArray[$value] = $value;
                unset($featArray[$key]);
            }
        }
        return $featArray;
    }

    /**
     * Builds a <select> based on a given array
     *
     * @version 20080618
     * @param array|null $aElements
     * @param mixed $sDefaultValue
     * @param string $selectName
     * @param array $featArray
     * @return string
     */
    protected function setArrayToSelect($aElements, $sDefaultValue, $selectName, $featArray = null)
    {
        if (!is_array($aElements)) {
            return '';
        }
        if (isset($featArray['readonly'])) {
            $inputFeatures = [
                'name'     => $selectName,
                'id'       => $this->buildSelectId($selectName, $featArray),
                'readonly' => 'readonly',
                'class'    => 'input_readonly',
                'value'    => $sDefaultValue,
            ];
            return $this->setStringIntoShortTag('input', $inputFeatures) . $aElements[$sDefaultValue];
        }
        return $this->setArrayToSelectNotReadOnly($aElements, $sDefaultValue, $selectName, $featArray);
    }

    /**
     * Converts an array to string
     *
     * @param string $sSeparator
     * @param array $aElements
     * @return string
     */
    protected function setArrayToStringForUrl($sSeparator, $aElements, $aExceptedElements = [''])
    {
        $outArray = $this->normalizeArrayForUrl($aElements);
        if (count($outArray) < 1) {
            return '';
        }
        $xptArray   = $this->normalizeArrayForUrl($aExceptedElements);
        $finalArray = array_diff_key($outArray, $xptArray);
        return http_build_query($finalArray, '', $sSeparator);
    }

    /**
     * Returns a pagination bar
     *
     * @param int $iCrtPgNo
     * @param int $inRecPrPg
     * @param int $iAllRec
     * @param boolean $bKpFlPg
     * returns string
     */
    protected function setPagination($iCrtPgNo, $inRecPrPg, $iAllRec, $bKpFlPg = true)
    {
        $sReturn             = null;
        $iRecPrPg            = min($inRecPrPg, $iAllRec);
        $iStartingPageRecord = $this->setStartingPageRecord($iCrtPgNo, $iRecPrPg, $iAllRec, $bKpFlPg);
        $sReturn             .= '<span style="float:left;font-size:smaller;margin-top:1px; margin-right:1px;">'
            . $this->setStringIntoTag($iAllRec, 'b')
            . $this->lclMsgCmn('i18n_RecordsAvailableNowDisplaying')
            . $this->setStringIntoTag(($iStartingPageRecord + 1), 'b')
            . ' - ' . $this->setStringIntoTag(min($iAllRec, ($iStartingPageRecord + $iRecPrPg)), 'b')
            . ' </span>';
        switch ($iCrtPgNo) {
            case 'first':
                $iCrtPgNo = ceil(($iStartingPageRecord + 1 ) / $iRecPrPg);
                break;
            case 'last':
                $iCrtPgNo = ceil($iAllRec / $iRecPrPg);
                break;
        }
        $sReturn              .= '<span style="float:right;font-size:smaller;margin-top:1px; margin-right:1px;">';
        $iNumberOfPages       = ceil($iAllRec / $iRecPrPg);
        $sAdditionalArguments = '';
        if (isset($_GET)) {
            if ($_GET != ['page' => $_GET['page']]) {
                $sAdditionalArguments = '&amp;'
                    . $this->setArrayToStringForUrl('&amp;', $_GET, ['page', 'action', 'server_action']);
            }
            if (!is_null($this->tCmnSuperGlobals->get('page'))) {
                $iCrtPgNo = $this->tCmnSuperGlobals->get('page');
            }
        }
        if ($iCrtPgNo != 1) {
            $sReturn .= $this->setStringIntoTag($this->lclMsgCmn('i18n_Previous'), 'a', [
                'href'  => ('?page=' . ($iCrtPgNo - 1 ) . $sAdditionalArguments ),
                'class' => 'pagination'
            ]);
        } else {
            $sReturn .= $this->setStringIntoTag($this->lclMsgCmn('i18n_Previous'), 'span', [
                'class' => 'pagination_inactive'
            ]);
        }
        $pages2display = [];
        for ($counter = 1; $counter <= $iNumberOfPages; $counter++) {
            $pages2display[$counter] = $counter;
        }
        $sReturn .= '<span class="pagination"><form method="get" action="' . $this->tCmnSuperGlobals->getScriptName() . '">';
        $sReturn .= $this->setArrayToSelect($pages2display, $this->tCmnSuperGlobals->get('page')
            , 'page', ['size' => 1, 'autosubmit', 'id_no' => mt_rand()]);
        if (isset($_GET)) {
            foreach ($_GET as $key => $value) {
                if ($key != 'page') {
                    if (is_array($value)) {
                        foreach ($value as $value2) {
                            $sReturn .= $this->setStringIntoShortTag('input', [
                                'type'  => 'hidden',
                                'name'  => $key . '[]',
                                'value' => $value2,
                            ]);
                        }
                    } else {
                        $sReturn .= $this->setStringIntoShortTag('input', [
                            'type'  => 'hidden',
                            'name'  => $key,
                            'value' => $value,
                        ]);
                    }
                }
            }
        }
        $sReturn .= '</form></span>';
        if ($iCrtPgNo != $iNumberOfPages) {
            $sReturn .= $this->setStringIntoTag($this->lclMsgCmn('i18n_Next'), 'a', [
                'href'  => ('?page=' . ($iCrtPgNo + 1 ) . $sAdditionalArguments ),
                'class' => 'pagination',
            ]);
        } else {
            $sReturn .= $this->setStringIntoTag($this->lclMsgCmn('i18n_Next'), 'span', [
                'class' => 'pagination_inactive',
            ]);
        }
        $sReturn .= '</span>';
        return $sReturn;
    }

    /**
     * Returns starting records for LIMIT clause on SQL interrogation
     *
     * @version 20080521
     * @param string $sDefaultPageNo
     * @param int $iRecordsPerPage
     * @param int $iAllRecords
     * @param boolean $bKeepFullPage
     * @return int
     */
    private function setStartingPageRecord($sDefaultPageNo, $iRecordsPerPage
        , $iAllRecords, $bKeepFullPage = true)
    {
        if (is_null($this->tCmnSuperGlobals->get('page'))) {
            switch ($sDefaultPageNo) {
                case 'last':
                    $iStartingPageRecord = $iAllRecords - $iRecordsPerPage;
                    break;
                case 'first':
                default:
                    $iStartingPageRecord = 0;
                    break;
            }
        } else {
            $iStartingPageRecord = ($this->tCmnSuperGlobals->get('page') - 1 ) * $iRecordsPerPage;
        }
        if (($bKeepFullPage ) && (($iStartingPageRecord + $iRecordsPerPage ) > $iAllRecords)) {
            $iStartingPageRecord = $iAllRecords - $iRecordsPerPage;
        }
        return max(0, $iStartingPageRecord);
    }

}
