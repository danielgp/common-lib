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
trait DomDynamicSelectByDanielGP
{

    protected function buildSelectId($selectName, $featArray)
    {
        $selectId = str_replace(['[', ']'], ['', ''], $selectName);
        if (isset($featArray['id_no'])) {
            $selectId .= $featArray['id_no'];
        }
        return $selectId;
    }

    private function eventOnChange($featArray)
    {
        $tempString = '';
        if (in_array('autosubmit', $featArray)) {
            return ' onchange="javascript:' . $tempString . 'submit();"';
        }
        if (in_array('additional_javascript_action', $featArray)) {
            return ' onchange="javascript:' . $featArray['additional_javascript_action'] . '"';
        }
        return '';
    }

    private function featureArraySimpleDirect($featArray, $identifier)
    {
        if (in_array($identifier, $featArray)) {
            return ' ' . $identifier . '="' . $identifier . '"';
        }
        return '';
    }

    private function featureArraySimpleTranslated($featArray, $identifier)
    {
        $translation = [
            'hidden'      => ' style="visibility: hidden;',
            'multiselect' => ' multiple="multiple"',
        ];
        if (in_array($identifier, $featArray)) {
            return $translation[$identifier];
        }
        return '';
    }

    private function featureArrayAssociative($featArray, $identifier)
    {
        if (in_array($identifier, $featArray)) {
            return ' ' . $identifier . '="' . $featArray[$identifier] . '"';
        }
        return '';
    }

    protected function setArrayToSelectNotReadOnly($aElements, $sDefaultValue, $selectName, $featArray = null)
    {
        if (!is_array($featArray)) {
            $featArray = [];
        }
        return '<select name="' . $selectName . '" '
                . 'id="' . $this->buildSelectId($selectName, $featArray) . '" '
                . 'size="' . $this->calculateSelectOptionsSize($aElements, $featArray) . '"'
                . $this->eventOnChange($featArray)
                . $this->featureArraySimpleDirect($featArray, 'disabled')
                . $this->featureArraySimpleTranslated($featArray, 'hidden')
                . $this->featureArraySimpleTranslated($featArray, 'multiselect')
                . $this->featureArrayAssociative($featArray, 'style')
                . '>'
                . $this->setOptionsForSelect($aElements, $sDefaultValue, $featArray) . '</select>';
    }

    private function setOptionGroupEnd($crtGroup, $featArray)
    {
        if (isset($featArray['grouping'])) {
            if ($crtGroup != '') {
                return '</optgroup>';
            }
        }
        return '';
    }

    private function setOptionGroupFooterHeader($featArray, $crtValue, $crtGroup)
    {
        $sReturn = [];
        if (isset($featArray['grouping'])) {
            $tempString = substr($crtValue, 0, strpos($crtValue, $featArray['grouping']) + 1);
            if ($crtGroup != $tempString) {
                $sReturn[] = $this->setOptionGroupEnd($crtGroup, $featArray);
                $crtGroup  = $tempString;
                $sReturn[] = '<optgroup label="' . str_replace($featArray['grouping'], '', $crtGroup) . '">';
            }
        }
        return ['crtGroup' => $crtGroup, 'groupFooterHeader' => implode('', $sReturn)];
    }

    private function setOptionEmptyWithNullValue($featArray)
    {
        if (in_array('include_null', $featArray)) {
            return '<option value="NULL">&nbsp;</option>';
        }
        return '';
    }

    private function setOptionSelected($optionValue, $sDefaultValue, $featArray)
    {
        if (is_array($sDefaultValue)) {
            if (in_array($optionValue, $sDefaultValue)) {
                return ' selected="selected"';
            }
        }
        if (strcasecmp($optionValue, $sDefaultValue) === 0) {
            return ' selected="selected"';
        }
        if (isset($featArray['defaultValue_isSubstring'])) {
            $defaultValueArray = explode($featArray['defaultValue_isSubstring'], $sDefaultValue);
            if (in_array($optionValue, $defaultValueArray)) {
                return ' selected="selected"';
            }
        }
        return '';
    }

    private function setOptionStyle($featArray)
    {
        if (array_key_exists('styleForOption', $featArray)) {
            return ' style="' . $featArray['style'] . '"';
        }
        return '';
    }

    /**
     * Creates all the child tags required to populate a SELECT tag
     *
     * @param array $aElements
     * @param string|array $sDefaultValue
     * @param array $featArray
     * @return string
     */
    private function setOptionsForSelect($aElements, $sDefaultValue, $featArray = null)
    {
        if (is_null($featArray)) {
            $featArray = [];
        }
        $sReturn  = [];
        $crtGroup = null;
        foreach ($aElements as $key => $value) {
            $aFH       = $this->setOptionGroupFooterHeader($featArray, $value, $crtGroup);
            $crtGroup  = $aFH['crtGroup'];
            $sReturn[] = $aFH['groupFooterHeader']
                    . '<option value="' . $key . '"' . $this->setOptionSelected($key, $sDefaultValue, $featArray)
                    . $this->setOptionStyle($featArray) . '>'
                    . str_replace(['&', $crtGroup], ['&amp;', ''], $value) . '</option>';
        }
        $sReturn[] = $this->setOptionGroupEnd($crtGroup, $featArray);
        return $this->setOptionEmptyWithNullValue($featArray) . implode('', $sReturn);
    }
}