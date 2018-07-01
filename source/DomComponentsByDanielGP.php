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

    use CommonBasic,
        DomHeaderFooterByDanielGP;

    /**
     * Returns a table from an query
     *
     * @param array $aElements
     * @param array $ftrs
     * @param boolean $bKpFlPge
     * @return string
     */
    protected function setArrayToTable($aElements, $ftrs = [], $bKpFlPge = true)
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
        $checkboxFormId = '';
        if ((isset($ftrs['actions']['checkbox_inlineEdit'])) || (isset($ftrs['actions']['checkbox']))) {
            $checkboxFormId = 'frm' . date('YmdHis');
            $sReturn        .= '<form id="' . $checkboxFormId . '" name="' . $checkboxFormId
                . '" method="post" ' . ' action="' . $this->tCmnRequest->server->get('PHP_SELF') . '" >';
        }
        $tbl        = [];
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
        $groupCounter     = 0;
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
                    $pgn     = $this->setPagination($ftrs['limits'][0], $ftrs['limits'][1], $ftrs['limits'][2], $bKpFlPge);
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
                                        $sReturn      .= $this->updateDivTitleName($remindGroupValue, $groupCounter);
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
            $sReturn       .= $tbl['tr_Color'];
// Action column
            $checkboxName  = '';
            $checkboxNameS = '';
            if (isset($ftrs['actions'])) {
                $sReturn         .= '<td style="white-space:nowrap;">';
                $action_argument = 0;
                if (isset($ftrs['actions']['key'])) {
                    $actionKey = $ftrs['actions']['key'];
                } else {
                    $actionKey = 'view';
                }
                if (isset($ftrs['action_prefix'])) {
                    $actPrfx   = $ftrs['action_prefix'] . '&amp;';
                    $actionKey = 'view2';
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
                            $sReturn       .= '&nbsp;<input type="checkbox" name="' . $checkboxName
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
                            $sReturn       .= '/>';
                            break;
                        case 'checkbox_inlineEdit':
                            $checkboxName  = $value . '[]';
                            $checkboxNameS = $value;
                            $sReturn       .= '&nbsp;<input type="checkbox" name="' . $checkboxName
                                . '" id="n' . $aElements[$rCntr][$value] . '" value="'
                                . $aElements[$rCntr][$value] . '"/>';
                            break;
                        case 'delete':
                            $sReturn       .= '<a href="#" onclick="javascript:setQuest(\'' . $value[0] . '\',\'';
                            $iActArgs      = count($value[1]);
                            for ($cntr2 = 0; $cntr2 < $iActArgs; $cntr2++) {
                                $sReturn .= $value[1][$cntr2] . '=' . $aElements[$rCntr][$value[1][$cntr2]];
                            }
                            $sReturn .= '\');" id="' . $key . $rCntr . '"><i class="fa fa-times">&nbsp;</i></a>';
                            break;
                        case 'edit':
                        case 'list2':
                        case 'schedule':
                            $vIc     = ($key == 'edit' ? 'pencil' : ($key == 'list2' ? 'list' : 'hourglass-half'));
                            $sReturn .= $this->setDynamicActionToSpecialCell($value, $aElements, [
                                'vIcon'    => 'fa fa-' . $vIc,
                                'aPrefix'  => $actPrfx,
                                'aKey'     => $actionKey,
                                'rCounter' => $rCntr,
                                'Features' => $ftrs,
                                'key'      => $key,
                            ]);
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
                $rowComputed = [];
                $decimals    = [];
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
                            $rowComputed[$key] = '';
                            break;
                    }
                    if ($value[0] == '%') {
                        $rowComputed[$key] = ($aElements[$rCntr][$key] * 100);
                        $dec               -= 2;
                    } else {
                        $rowComputed[$key] = $aElements[$rCntr][$key];
                    }
                    $decimals[$key] = $dec;
                }
// displaying them
                $sReturn .= $this->setTableCell($rowComputed, ['decimals' => $decimals]);
            }
            $sReturn .= '</tr>';
        }
        if (isset($iStartingPageRecord)) {
            $pgn     = $this->setPagination($ftrs['limits'][0], $ftrs['limits'][1], $ftrs['limits'][2]);
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
                        $sReturn .= '<input type="hidden" name="' . $valueF . '" value="' . $_REQUEST[$valueF] . '" />';
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

    private function setDynamicActionToSpecialCell($val, $aElements, $inP)
    {
        $aArgumemts   = [];
        $aArgumemts[] = $this->tCmnSuperGlobals->getScriptName() . '?' . $inP['aPrefix'] . $inP['aKey'] . '=' . $val[0];
        $iActArgs     = count($val[1]);
        for ($counter = 0; $counter < $iActArgs; $counter++) {
            $aArgumemts[] = $val[1][$counter] . '=' . $aElements[$inP['rCounter']][$val[1][$counter]];
        }
        $id = $inP['key'] . $inP['rCounter'];
        if (isset($inP['Features']['NoAjaxEditing'])) {
            return '<a href="' . implode('&amp;', $aArgumemts) . '" id="' . $id . '"><i class="'
                . $inP['vIcon'] . '">&nbsp;</i></a>';
        }
        return '<a href="#" onclick="javascript:loadAE(\'' . implode('&amp;', $aArgumemts) . '\');"'
            . ' id="' . $id . '"><i class="' . $inP['vIcon'] . '">&nbsp;</i></a>';
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
                        $sReturn .= $this->setTableCellNumeric($key, $value, $features);
                    } else {
                        $outputet = false;
                        if ((strpos($value, '-') !== false) && (strlen($value) == 10)) {
                            if (preg_match("([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})", $value, $regs)) {
                                $outputet = true;
                                $sReturn  .= 'style="text-align:right;width: 10px;">'
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

    private function setTableCellDecimals($key, $features)
    {
        $decimals = 0;
        if (isset($features['no_of_decimals'])) {
            $decimals = $features['no_of_decimals'];
        }
        if (isset($features['decimals']) && array_key_exists($key, $features['decimals'])) {
            $decimals = $features['decimals'][$key];
        }
        return $decimals;
    }

    private function setTableCellNumeric($key, $value, $features)
    {
        $styleToReturn = 'style="text-align: right;">';
        if (substr($value, 0, 1) === '0') {
            return $styleToReturn . $value;
        }
        $decimals = $this->setTableCellDecimals($key, $features);
        $nDc      = ['MinFractionDigits' => $decimals, 'MaxFractionDigits' => $decimals];
        return $styleToReturn . $this->setNumberFormat($value, $nDc);
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
        $aTableHeader = $aElements;
        if ($bHeadersBreaked) {
            $aTableHeader = $this->setArrayToArrayKbr($aElements);
        }
        $sReturn[] = [];
        foreach (array_keys($aTableHeader) as $value) {
            $sReturn[] = $this->setStringIntoTag($value, 'th');
        }
        return implode('', $sReturn);
    }

}
