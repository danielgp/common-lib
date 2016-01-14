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
 * DOM component functions
 *
 * @author Daniel Popiniuc
 */
trait DomComponentsByDanielGP
{

    use DomCssAndJavascriptByDanielGP,
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

    /**
     * Builds a <select> based on a given array
     *
     * @version 20080618
     * @param array $aElements
     * @param string/array $sDefaultValue
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
            return $this->setStringIntoShortTag('input', [
                        'name'     => $selectName,
                        'id'       => $this->buildSelectId($selectName, $featArray),
                        'readonly' => 'readonly',
                        'class'    => 'input_readonly',
                        'value'    => $sDefaultValue,
                    ]) . $aElements[$sDefaultValue];
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
        var_dump($aElements);
        echo '<hr/>';
        $outArray   = $this->normalizeArrayForUrl($aElements);
        var_dump($outArray);
        $xptArray   = $this->normalizeArrayForUrl($aExceptedElements);
        $finalArray = array_diff_key($outArray, $xptArray);
        return http_build_query($finalArray, '', $sSeparator);
    }

    /**
     * Returns a table from an query
     *
     * @param array $gArray
     * @param array $features
     * @param boolean $bKeepFullPage
     * @return string
     */
    protected function setArrayToTable($aElements, $ftrs = null, $bKpFlPge = true)
    {
        $rows = count($aElements);
        if ($rows == 0) {
            $divTab = [
                'start' => '',
                'end'   => '',
            ];
            if (array_key_exists('showGroupingCounter', $ftrs)) {
                if (array_key_exists('grouping_cell_type', $ftrs) && ($ftrs['grouping_cell_type'] == 'tab')) {
                    $ditTitle = 'No data found';
                    if (isset($ftrs['showGroupingCounter'])) {
                        $ditTitle .= ' (0)';
                    }
                    $divTab = [
                        'start' => '<div class="tabbertab tabbertabdefault" id="tab_NoData" title="' . $ditTitle . '">',
                        'end'   => '</div><!-- from tab_NoData -->',
                    ];
                    if (!isset($ftrs['noGlobalTab'])) {
                        $divTab = [
                            'start' => '<div class="tabber" id="tab">' . $divTab['start'],
                            'end'   => $divTab['end'] . '</div><!-- from global Tab -->',
                        ];
                    }
                }
            }
            return $divTab['start']
                    . $this->setFeedbackModern('error', 'Error', $this->lclMsgCmn('i18n_NoData'))
                    . $divTab['end'];
        }
        if (isset($ftrs['limits'])) {
            $ftrs['limits'][1] = min($ftrs['limits'][1], $ftrs['limits'][2]);
            if ($ftrs['limits'][2] > $ftrs['limits'][1]) {
                $iStartingPageRecord = 1;
            }
        }
        $sReturn = '';
        if (isset($ftrs['hidden_columns'])) {
            $hdClmns = $this->setArrayValuesAsKey($ftrs['hidden_columns']);
        } else {
            $hdClmns = [''];
        }
        if ((isset($ftrs['actions']['checkbox_inlineEdit'])) || (isset($ftrs['actions']['checkbox']))) {
            $checkboxFormId = 'frm' . date('YmdHis');
            $sReturn .= '<form id="' . $checkboxFormId . '" ' . 'name="' . $checkboxFormId
                    . '" method="post" ' . ' action="' . $_SERVER['PHP_SELF'] . '" >';
        }
        $tbl['Def'] = '<table'
                . (isset($ftrs['table_style']) ? ' style="' . $ftrs['table_style'] . '"' : '')
                . (isset($ftrs['table_class']) ? ' class="' . $ftrs['table_class'] . '"' : '')
                . '>';
        if (!isset($ftrs['grouping_cell_type'])) {
            $ftrs['grouping_cell_type'] = 'row';
        }
        switch ($ftrs['grouping_cell_type']) {
            case 'row':
                $sReturn .= $tbl['Def'];
                break;
            case 'tab':
                if (!isset($ftrs['noGlobalTab'])) {
                    $sReturn .= '<div class="tabber" id="tab">';
                }
                break;
        }
        $iTableColumns    = 0;
        $remebered_value  = -1;
        $remindGroupValue = null;
        $color_no         = null;
        if (!isset($ftrs['headers_breaked'])) {
            $ftrs['headers_breaked'] = true;
        }
        for ($rCntr = 0; $rCntr < $rows; $rCntr++) {
            if ($rCntr == 0) {
                $header        = array_diff_key($aElements[$rCntr], $hdClmns);
                $iTableColumns = count($header);
                if (isset($ftrs['computed_columns'])) {
                    $iTableColumns += count($ftrs['computed_columns']);
                }
                if (isset($ftrs['actions'])) {
                    $iTableColumns += 1;
                }
                if (isset($ftrs['grouping_cell'])) {
                    $iTableColumns -= 1;
                }
                $tbl['Head'] = '<thead>';
                if ($ftrs['grouping_cell_type'] == 'row') {
                    $sReturn .= $tbl['Head'];
                }
                if (isset($iStartingPageRecord)) {
                    $pgn = $this->setPagination($ftrs['limits'][0], $ftrs['limits'][1], $ftrs['limits'][2], $bKpFlPge);
                    $sReturn .= $this->setStringIntoTag($this->setStringIntoTag($pgn, 'th', [
                                'colspan' => $iTableColumns
                            ]), 'tr');
                }
                $tbl['Header'] = '<tr>';
                if (isset($ftrs['grouping_cell'])) { // Grouping columns
                    $header = array_diff_key($header, [$ftrs['grouping_cell'] => '']);
                }
                if (isset($ftrs['actions'])) { // Action column
                    $tbl['Header'] .= '<th>&nbsp;</th>';
                }
                if (isset($ftrs['RowStyle'])) { //Exclude style columns from displaying
                    $tmpClmns = $this->setArrayValuesAsKey([$ftrs['RowStyle']]);
                    $header   = array_diff_key($header, $tmpClmns);
                    $hdClmns  = array_merge($hdClmns, $tmpClmns);
                    unset($tmpClmns);
                }
                $tbl['Header'] .= $this->setTableHeader($header, $ftrs['headers_breaked']); // Regular columns
                if (isset($ftrs['computed_columns'])) { // Computed columns
                    $tbl['Header'] .= $this->setTableHeader($ftrs['computed_columns'], $ftrs['headers_breaked']);
                }
                $tbl['Header'] .= '</tr></thead><tbody>';
                if ($ftrs['grouping_cell_type'] == 'row') {
                    $sReturn .= $tbl['Header'];
                }
            }
            $row_current = array_diff_key($aElements[$rCntr], $hdClmns);
            if (isset($ftrs['row_colored_alternated'])) {
                if ($ftrs['row_colored_alternated'][0] == '#') {
                    $color_column_value = $rCntr;
                } else {
                    $color_column_value = $row_current[$ftrs['row_colored_alternated'][0]];
                }
                if ($remebered_value != $color_column_value) {
                    if (isset($color_no)) {
                        $color_no = 1;
                    } else {
                        $color_no = 2;
                    }
                    $remebered_value = $color_column_value;
                }
                $color = ' style="background-color: ' . $ftrs['row_colored_alternated'][$color_no] . ';"';
            } else {
                if (isset($ftrs['RowStyle'])) {
                    $color = ' style="' . $aElements[$rCntr][$ftrs['RowStyle']] . '"';
                } else {
                    $color = '';
                }
            }
            $tbl['tr_Color'] = '<tr' . $color . '>';
// Grouping column
            if (isset($ftrs['grouping_cell'])) {
                foreach ($aElements[$rCntr] as $key => $value) {
                    if (($ftrs['grouping_cell'] == $key) && ($remindGroupValue != $value)) {
                        switch ($ftrs['grouping_cell_type']) {
                            case 'row':
                                $sReturn .= $tbl['tr_Color'] . '<td ' . 'colspan="' . $iTableColumns . '">'
                                        . $this->setStringIntoTag($value, 'div', ['class' => 'rowGroup rounded'])
                                        . '</td></tr>';
                                break;
                            case 'tab':
                                if (is_null($remindGroupValue)) {
                                    if (isset($ftrs['showGroupingCounter'])) {
                                        $groupCounter = 0;
                                    }
                                } else {
                                    $sReturn .= '</tbody></table>';
                                    if (isset($ftrs['showGroupingCounter'])) {
                                        $sReturn .= $this->updateDivTitleName($remindGroupValue, $groupCounter);
                                        $groupCounter = 0;
                                    }
                                    $sReturn .= '</div>';
                                }
                                $sReturn .= '<div class="tabbertab';
                                if (isset($ftrs['grouping_default_tab'])) {
                                    $sReturn .= ($ftrs['grouping_default_tab'] == $value ? ' tabbertabdefault' : '');
                                }
                                $sReturn .= '" id="tab_' . $this->cleanStringForId($value) . '" '
                                        . 'title="' . $value . '">'
                                        . $tbl['Def'] . $tbl['Head'] . $tbl['Header'];
                                break;
                        }
                        $remindGroupValue = $value;
                    }
                }
            }
            if (isset($ftrs['grouping_cell'])) {
                if ($ftrs['grouping_cell_type'] == 'tab') {
                    if (isset($ftrs['showGroupingCounter'])) {
                        $groupCounter++;
                    }
                }
            }
            $sReturn .= $tbl['tr_Color'];
// Action column
            if (isset($ftrs['actions'])) {
                $sReturn .= '<td style="white-space:nowrap;">';
                $action_argument = 0;
                if (isset($ftrs['actions']['key'])) {
                    $action_key = $ftrs['actions']['key'];
                } else {
                    $action_key = 'view';
                }
                if (isset($ftrs['action_prefix'])) {
                    $actPrfx    = $ftrs['action_prefix'] . '&amp;';
                    $action_key = 'view2';
                } else {
                    $actPrfx = '';
                }
                foreach ($ftrs['actions'] as $key => $value) {
                    if ($action_argument != 0) {
                        $sReturn .= '&nbsp;';
                    }
                    switch ($key) {
                        case 'checkbox':
                            $checkboxName  = $value . '[]';
                            $checkboxNameS = $value;
                            $sReturn .= '&nbsp;<input type="checkbox" name="' . $checkboxName
                                    . '" id="n' . $aElements[$rCntr][$value]
                                    . '" value="' . $aElements[$rCntr][$value] . '" ';
                            if (isset($_REQUEST[$checkboxNameS])) {
                                if (is_array($_REQUEST[$checkboxNameS])) {
                                    if (in_array($aElements[$rCntr][$value], $_REQUEST[$checkboxNameS])) {
                                        $sReturn .= 'checked="checked" ';
                                    }
                                } else {
                                    if ($aElements[$rCntr][$value] == $_REQUEST[$checkboxNameS]) {
                                        $sReturn .= 'checked="checked" ';
                                    }
                                }
                            }
                            if (strpos($_REQUEST['view'], 'multiEdit') !== false) {
                                $sReturn .= 'disabled="disabled" ';
                            }
                            $sReturn .= '/>';
                            break;
                        case 'checkbox_inlineEdit':
                            $checkboxName  = $value . '[]';
                            $checkboxNameS = $value;
                            $sReturn .= '&nbsp;<input type="checkbox" name="' . $checkboxName
                                    . '" id="n' . $aElements[$rCntr][$value] . '" value="'
                                    . $aElements[$rCntr][$value] . '"/>';
                            break;
                        case 'edit':
                            $edt           = '';
                            if (isset($ftrs['NoAjaxEditing'])) {
                                $edt .= $_SERVER['PHP_SELF'] . '?' . $actPrfx
                                        . $action_key . '=' . $value[0] . '&amp;';
                                $iActArgs = count($value[1]);
                                for ($cntr2 = 0; $cntr2 < $iActArgs; $cntr2++) {
                                    $edt .= $value[1][$cntr2] . '=' . $aElements[$rCntr][$value[1][$cntr2]];
                                }
                                $sReturn .= '<a href="' . $edt . '"><i class="fa fa-pencil">&nbsp;</i></a>';
                            } else {
                                $edt .= 'javascript:loadAE(\'' . $_SERVER['PHP_SELF'] . '?'
                                        . $actPrfx . $action_key . '=' . $value[0] . '&amp;';
                                $iActArgs = count($value[1]);
                                for ($cntr2 = 0; $cntr2 < $iActArgs; $cntr2++) {
                                    $edt .= $value[1][$cntr2] . '=' . $aElements[$rCntr][$value[1][$cntr2]];
                                }
                                $edt .= '\');';
                                $sReturn .= '<a href="#" onclick="' . $edt . '">'
                                        . '<i class="fa fa-pencil">&nbsp;</i></a>';
                            }
                            break;
                        case 'list2':
                            $edt = '';
                            if (isset($ftrs['NoAjaxEditing'])) {
                                $sReturn .= '<a href="?' . $actPrfx . $action_key . '=' . $value[0] . '&amp;';
                                $iActArgs = count($value[1]);
                                for ($cntr2 = 0; $cntr2 < $iActArgs; $cntr2++) {
                                    $sReturn .= $value[1][$cntr2] . '=' . $aElements[$rCntr][$value[1][$cntr2]];
                                }
                                $sReturn .= '"><i class="fa fa-list">&nbsp;</i></a>';
                            } else {
                                $edt .= 'javascript:loadAE(\'' . $_SERVER['PHP_SELF'] . '?'
                                        . $actPrfx . $action_key . '=' . $value[0] . '&amp;';
                                $iActArgs = count($value[1]);
                                for ($cntr2 = 0; $cntr2 < $iActArgs; $cntr2++) {
                                    $edt .= $value[1][$cntr2] . '=' . $aElements[$rCntr][$value[1][$cntr2]];
                                }
                                $edt .= '\');';
                                $sReturn .= '<a href="#" onclick="' . $edt . '">'
                                        . '<i class="fa fa-list">&nbsp;</i></a>';
                            }
                            break;
                        case 'delete':
                            $sReturn .= '<a href="javascript:setQuest(\'' . $value[0] . '\',\'';
                            $iActArgs = count($value[1]);
                            for ($cntr2 = 0; $cntr2 < $iActArgs; $cntr2++) {
                                $sReturn .= $value[1][$cntr2] . '=' . $aElements[$rCntr][$value[1][$cntr2]];
                            }
                            $sReturn .= '\');"><i class="fa fa-times">&nbsp;</i></a>';
                            break;
                    }
                    $action_argument += 1;
                }
                $sReturn .= '</td>';
            }
// Regular columns
            $sReturn .= $this->setTableCell($row_current, $ftrs);
// Computed columns
            if (isset($ftrs['computed_columns'])) {
                foreach ($ftrs['computed_columns'] as $key => $value) {
                    if ($value[0] == '%') {
                        $dec = $value[2] + 2;
                    } else {
                        $dec = $value[2];
                    }
                    switch ($value[1]) {
                        case '/':
                            // next variable is only to avoid a long line
                            $shorter                 = [
                                $aElements[$rCntr][$value[3]],
                                $aElements[$rCntr][$value[4]],
                            ];
                            $aElements[$rCntr][$key] = $this->setDividedResult($shorter[0], $shorter[1], $dec);
                            break;
                        case '+':
                            // next variable is only to avoid a long line
                            $iTemp                   = $this->setArrayValuesAsKey([
                                $value[0],
                                $value[1],
                                $value[2]
                            ]);
                            $aTemp                   = array_diff($value, $iTemp);
                            $aElements[$rCntr][$key] = 0;
                            foreach ($aTemp as $sValue) {
                                $aElements[$rCntr][$key] += $aElements[$rCntr][$sValue];
                            }
                            break;
                        default:
                            $row_computed[$key] = '';
                            break;
                    }
                    if ($value[0] == '%') {
                        $row_computed[$key] = ($aElements[$rCntr][$key] * 100);
                        $dec -= 2;
                    } else {
                        $row_computed[$key] = $aElements[$rCntr][$key];
                    }
                    $decimals[$key] = $dec;
                }
// displaying them
                $sReturn .= $this->setTableCell($row_computed, ['decimals' => $decimals]);
            }
            $sReturn .= '</tr>';
        }
        if (isset($iStartingPageRecord)) {
            $pgn = $this->setPagination($ftrs['limits'][0], $ftrs['limits'][1], $ftrs['limits'][2]);
            $sReturn .= '<tr>' . $this->setStringIntoTag($pgn, 'th', ['colspan' => $iTableColumns]) . '</tr>';
        }
        $sReturn .= '</tbody></table>';
        if ($ftrs['grouping_cell_type'] == 'tab') {
            if (isset($ftrs['showGroupingCounter'])) {
                $sReturn .= $this->updateDivTitleName($remindGroupValue, $groupCounter);
            }
            $sReturn .= '</div><!-- from ' . $remindGroupValue . ' -->';
            if (!isset($ftrs['noGlobalTab'])) {
                $sReturn .= '</div><!-- from global tab -->';
            }
        }
        if (isset($ftrs['actions']['checkbox'])) {
            if (strpos($_REQUEST['view'], 'multiEdit') === false) {
                $sReturn .= '<a href="#" onclick="javascript:checking(\'' . $checkboxFormId
                        . '\',\'' . $checkboxName . '\',true);">Check All</a>&nbsp;&nbsp;'
                        . '<a href="#" onclick="javascript:checking(\'' . $checkboxFormId
                        . '\',\'' . $checkboxName . '\',false);">Uncheck All</a>&nbsp;&nbsp;'
                        . '<input type="hidden" name="action" value="multiEdit_' . $checkboxNameS . '" />';
                if (isset($ftrs['hiddenInput'])) {
                    if (is_array($ftrs['hiddenInput'])) {
                        foreach ($ftrs['hiddenInput'] as $valueF) {
                            $sReturn .= '<input type="hidden" name="' . $valueF
                                    . '" value="' . $_REQUEST[$valueF] . '" />';
                        }
                    } else {
                        $sReturn .= '<input type="hidden" name="' . $ftrs['hiddenInput']
                                . '" value="' . $_REQUEST[$ftrs['hiddenInput']] . '" />';
                    }
                }
                $sReturn .= '<input style="margin: 0 3em 0 3em;" type="submit" ' . 'value="Edit selected" />';
            }
            $sReturn .= '</form>';
        }
        if (isset($ftrs['actions']['checkbox_inlineEdit'])) {
            $sReturn .= '<a href="#" onclick="javascript:checking(\'' . $checkboxFormId
                    . '\',\'' . $checkboxName . '\',true);">Check All</a>&nbsp;&nbsp;'
                    . '<a href="#" onclick="javascript:checking(\'' . $checkboxFormId
                    . '\',\'' . $checkboxName . '\',false);">Uncheck All</a>&nbsp;&nbsp;';
            if (isset($ftrs['visibleInput'])) {
                $sReturn .= $ftrs['visibleInput'];
            }
            $sReturn .= '<input type="hidden" name="view" value="save_' . $checkboxNameS . '" />';
            if (isset($ftrs['hiddenInput'])) {
                if (is_array($ftrs['hiddenInput'])) {
                    foreach ($ftrs['hiddenInput'] as $valueF) {
                        $sReturn .= '<input type="hidden" name="' . $valueF
                                . '" value="' . $_REQUEST[$valueF] . '" />';
                    }
                } else {
                    $sReturn .= '<input type="hidden" name="' . $ftrs['hiddenInput']
                            . '" value="' . $_REQUEST[$ftrs['hiddenInput']] . '" />';
                }
            }
            $sReturn .= '<input style="margin: 0 3em 0 3em;" type="submit" value="Store the modification" />';
            $sReturn .= '</form>';
        }
        return $sReturn;
    }

    /**
     * Set a control to a user-friendly calendar
     *
     * @param string $controlName
     * @param string $additionalStyle
     * @return string
     */
    public function setCalendarControl($controlName, $additionalStyle = '')
    {
        return $this->setStringIntoTag('&nbsp;', 'span', [
                    'onclick' => implode('', [
                        'javascript:NewCssCal(\'' . $controlName,
                        '\',\'yyyyMMdd\',\'dropdown\',false,\'24\',false);',
                    ]),
                    'class'   => 'fa fa-calendar',
                    'id'      => $controlName . '_picker',
                    'style'   => 'cursor:pointer;' . $additionalStyle,
        ]);
    }

    /**
     * Set a control to a user-friendly calendar with time included
     *
     * @param string $controlName
     * @param string $additionalStyle
     * @return string
     */
    public function setCalendarControlWithTime($controlName, $additionalStyle = '')
    {
        return $this->setStringIntoTag('&nbsp;', 'span', [
                    'onclick' => implode('', [
                        'javascript:NewCssCal(\'' . $controlName,
                        '\',\'yyyyMMdd\',\'dropdown\',true,\'24\',true);',
                    ]),
                    'class'   => 'fa fa-calendar',
                    'id'      => $controlName . '_picker',
                    'style'   => 'cursor:pointer;' . $additionalStyle,
        ]);
    }

    /**
     * Builds a structured modern message
     *
     * @param string $sType
     * @param string $sTitle
     * @param string $sMsg
     * @param boolean $skipBr
     */
    protected function setFeedbackModern($sType, $sTitle, $sMsg, $skipBr = false)
    {
        if ($sTitle == 'light') {
            return $sMsg;
        }
        $stl    = $this->setFeedbackModernStyles($sType);
        $legend = $this->setStringIntoTag($sTitle, 'legend', ['style' => $stl['Ttl']]);
        return implode('', [
            ($skipBr ? '' : '<br/>'),
            $this->setStringIntoTag($legend . $sMsg, 'fieldset', ['style' => $stl['Msg']]),
        ]);
    }

    private function setFeedbackModernStyles($sType)
    {
        $formatTitle   = 'margin-top:-5px;margin-right:20px;padding:5px;';
        $formatMessage = 'display:inline;padding-right:5px;padding-bottom:5px;';
        $styleByType   = [
            'alert' => [
                'border:medium solid orange;background-color:orange;color:navy;',
                'background-color:navy;color:orange;border:medium solid orange;',
            ],
            'check' => [
                'border:medium solid green;background-color:green;color:white;',
                'background-color:yellow;color:green;border:medium solid green;',
            ],
            'error' => [
                'border:medium solid red;background-color:red;color:white;',
                'background-color:yellow;color:red;border:medium solid red;',
            ],
            'info'  => [
                'border:medium solid black;background-color:black;color:white;font-weight:bold;',
                'background-color: white; color: black;border:medium solid black;',
            ],
        ];
        return ['Ttl' => $formatTitle . $styleByType[$sType], 'Msg' => $formatMessage . $styleByType[$sType]];
    }

    /**
     * Outputs an HTML footer
     *
     * @param array $footerInjected
     * @return string
     */
    protected function setFooterCommon($footerInjected = null)
    {
        if (isset($_REQUEST['specialHook']) && (in_array('noFooter', $_REQUEST['specialHook']))) {
            return '';
        }
        return $this->setFooterCommonInjected($footerInjected) . '</body></html>';
    }

    protected function setFooterCommonInjected($footerInjected = null)
    {
        $sReturn = '';
        if (!is_null($footerInjected)) {
            $sReturn = $footerInjected;
            if (is_array($footerInjected)) {
                $sReturn = implode('', $footerInjected);
            }
        }
        return $sReturn;
    }

    /**
     * Outputs an HTML header
     *
     * @param array $headerFeatures
     * @return string
     */
    protected function setHeaderCommon($headerFeatures = null)
    {
        $sReturn = [];
        if (isset($_REQUEST['specialHook']) && (in_array('noHeader', $_REQUEST['specialHook']))) {
            $sReturn[] = ''; // no Header
        } else {
            $fixedHeaderElements = [
                'start'    => '<!DOCTYPE html>',
                'lang'     => '<html lang="en-US">',
                'head'     => '<head>',
                'charset'  => '<meta charset="utf-8" />',
                'viewport' => '<meta name="viewport" content="' . implode(', ', [
                    'width=device-width',
                    'height=device-height',
                    'initial-scale=1',
                ]) . '" />',
            ];
            if (!is_null($headerFeatures)) {
                if (is_array($headerFeatures)) {
                    $aFeatures = [];
                    foreach ($headerFeatures as $key => $value) {
                        switch ($key) {
                            case 'css':
                                if (is_array($value)) {
                                    foreach ($value as $value2) {
                                        $aFeatures[] = $this->setCssFile(filter_var($value2, FILTER_SANITIZE_URL));
                                    }
                                } else {
                                    $aFeatures[] = $this->setCssFile(filter_var($value, FILTER_SANITIZE_URL));
                                }
                                break;
                            case 'javascript':
                                if (is_array($value)) {
                                    foreach ($value as $value2) {
                                        $vl          = filter_var($value2, FILTER_SANITIZE_URL);
                                        $aFeatures[] = $this->setJavascriptFile($vl);
                                    }
                                } else {
                                    $aFeatures[] = $this->setJavascriptFile(filter_var($value, FILTER_SANITIZE_URL));
                                }
                                break;
                            case 'lang':
                                $fixedHeaderElements['lang'] = '<html lang="'
                                        . filter_var($value, FILTER_SANITIZE_STRING) . '">';
                                break;
                            case 'title':
                                $aFeatures[]                 = '<title>'
                                        . filter_var($value, FILTER_SANITIZE_STRING) . '</title>';
                                break;
                        }
                    }
                    $sReturn[] = implode('', $fixedHeaderElements)
                            . implode('', $aFeatures)
                            . '</head>'
                            . '<body>';
                } else {
                    $sReturn[] = implode('', $fixedHeaderElements)
                            . '</head>'
                            . '<body>'
                            . '<p style="background-color:red;color:#FFF;">The parameter sent to '
                            . __FUNCTION__ . ' must be an array</p>'
                            . $this->setFooterCommon();
                    throw new \Exception($sReturn);
                }
            }
        }
        return implode('', $sReturn);
    }

    /**
     * Generates a table cell
     *
     * @param array $aElements
     * @param array $features
     * @return string
     */
    private function setTableCell($aElements, $features = null)
    {
        $sReturn = null;
        foreach ($aElements as $key => $value) {
            $value = str_replace(['& ', '\"', "\'"], ['&amp; ', '"', "'"], $value);
            if ((isset($features['grouping_cell'])) && ($features['grouping_cell'] == $key)) {
                // just skip
            } else {
                $sReturn .= '<td ';
                if (isset($features['column_formatting'][$key])) {
                    switch ($features['column_formatting'][$key]) {
                        case '@':
                            $sReturn .= 'style="text-align:left;">' . $value;
                            break;
                        case 'right':
                            $sReturn .= 'style="text-align:right;">' . $value;
                            break;
                        default:
                            $sReturn .= '???';
                            break;
                    }
                } else {
                    if (is_numeric($value)) {
                        if (substr($value, 0, 1) === '0') {
                            $sReturn .= 'style="text-align: right;">' . $value;
                        } else {
                            $decimals = 0;
                            if (isset($features['no_of_decimals'])) {
                                $decimals = $features['no_of_decimals'];
                            }
                            if (isset($features['decimals']) && array_key_exists($key, $features['decimals'])) {
                                $decimals = $features['decimals'][$key];
                            }
                            $sReturn .= 'style="text-align: right;">';
                            $sReturn .= $this->setNumberFormat($value, [
                                'MinFractionDigits' => $decimals,
                                'MaxFractionDigits' => $decimals
                            ]);
                        }
                    } else {
                        $outputet = false;
                        if ((strpos($value, '-') !== false) && (strlen($value) == 10)) {
                            if (preg_match("([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})", $value, $regs)) {
                                $outputet = true;
                                $sReturn .= 'style="text-align:right;width: 10px;">'
                                        . $regs[3] . '.' . $regs[2] . '.' . $regs[1];
                            }
                        }
                        if (!$outputet) {
                            $sReturn .= 'style="text-align:left;">' . $value;
                        }
                    }
                }
                $sReturn .= '</td>';
            }
        }
        return $sReturn;
    }

    /**
     * Generates a table header
     *
     * @param array $aElements
     * @param boolean $bHeadersBreaked
     * @return string
     */
    private function setTableHeader($aElements, $bHeadersBreaked)
    {
        if ($bHeadersBreaked) {
            $aTableHeader = $this->setArrayToArrayKbr($aElements);
        } else {
            $aTableHeader = $aElements;
        }
        $sReturn[] = null;
        foreach (array_keys($aTableHeader) as $value) {
            $sReturn[] = $this->setStringIntoTag($value, 'th');
        }
        return implode('', $sReturn);
    }

    /**
     * Create an upper right box with choices for languages
     * (requires flag-icon.min.css to be loaded)
     * (makes usage of custom class "upperRightBox" and id = "visibleOnHover", provided here as scss file)
     *
     * @param array $aAvailableLanguages
     * @return string
     */
    protected function setUpperRightBoxLanguages($aAvailableLanguages)
    {
        $this->handleLanguageIntoSession();
        return '<div class="upperRightBox">'
                . '<div style="text-align:right;">'
                . '<span class="flag-icon flag-icon-' . strtolower(substr($this->tCmnSuperGlobals->get('lang'), -2))
                . '" style="margin-right:2px;">&nbsp;</span>'
                . $aAvailableLanguages[$this->tCmnSession->get('lang')]
                . '</div><!-- default Language -->'
                . $this->setUpperRightVisibleOnHoverLanguages($aAvailableLanguages)
                . '</div><!-- upperRightBox end -->';
    }

    private function setUpperRightVisibleOnHoverLanguages($aAvailableLanguages)
    {
        $linkWithoutLanguage = '';
        if (isset($this->tCmnSuperGlobals->request)) {
            $linkWithoutLanguage = $this->setArrayToStringForUrl('&amp;', $this->tCmnSuperGlobals->request, ['lang'])
                    . '&amp;';
        }
        $sReturn = [];
        foreach ($aAvailableLanguages as $key => $value) {
            if ($this->tCmnSession->get('lang') !== $key) {
                $sReturn[] = '<a href="?' . $linkWithoutLanguage . 'lang=' . $key . '" style="display:block;">'
                        . '<span class="flag-icon flag-icon-' . strtolower(substr($key, -2))
                        . '" style="margin-right:2px;">&nbsp;</span>'
                        . $value . '</a>';
            }
        }
        return '<div id="visibleOnHover">' . implode('', $sReturn) . '</div><!-- visibleOnHover end -->';
    }
}
