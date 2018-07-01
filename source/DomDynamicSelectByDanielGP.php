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

    /**
     *
     * @param string $selectName
     * @param array $featArray
     * @return string
     */
    protected function buildSelectId($selectName, $featArray)
    {
        $selectId = str_replace(['[', ']'], ['', ''], $selectName);
        if (isset($featArray['id_no'])) {
            $selectId .= $featArray['id_no'];
        }
        return $selectId;
    }

    /**
     * Calculate the optimal for all options within a select tag
     *
     * @param array $aElements
     * @param array $aFeatures
     * @return string|int
     */
    private function calculateSelectOptionsSize($aElements, $aFeatures = [])
    {
        $selectSize = $this->calculateSelectOptionsSizeForced($aElements, $aFeatures);
        if ((in_array('include_null', $aFeatures)) && ($selectSize != '1')) {
            $selectSize++;
        }
        return $selectSize;
    }

    /**
     *
     * @param array $aElements
     * @param array $aFeatures
     * @return int
     */
    private function calculateSelectOptionsSizeForced($aElements, $aFeatures = [])
    {
        if (isset($aFeatures['size'])) {
            if ($aFeatures['size'] == 0) {
                return count($aElements);
            }
            return min(count($aElements), $aFeatures['size']);
        }
        return 1;
    }

    /**
     *
     * @param array $featArray
     * @return string
     */
    private function eventOnChange($featArray)
    {
        $sReturn = [];
        if (array_key_exists('additional_javascript_action', $featArray)) {
            $sReturn[] = $featArray['additional_javascript_action'];
        }
        if (array_key_exists('autosubmit', $featArray)) {
            $sReturn[] = 'submit();';
        }
        if ($sReturn != []) {
            return ' onchange="javascript:' . implode('', $sReturn) . '"';
        }
        return '';
    }

    private function featureArraySimpleTranslated($featArray, $identifier)
    {
        $translation = [
            'disabled'       => ' disabled',
            'hidden'         => ' style="visibility: hidden;',
            'include_null'   => '<option value="NULL">&nbsp;</option>',
            'multiselect'    => ' multiple="multiple"',
            'style'          => null,
            'styleForOption' => null,
        ];
        if (array_key_exists($identifier, $featArray)) {
            if (is_null($translation[$identifier])) {
                return ' ' . $identifier . '="' . $featArray[$identifier] . '"';
            }
            return $translation[$identifier];
        }
        return '';
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

    protected function setArrayToSelectNotReadOnly($aElements, $sDefaultValue, $selectName, $ftArray = null)
    {
        if (!is_array($ftArray)) {
            $ftArray = [];
        }
        $featArray = $this->normalizeFeatureArray($ftArray);
        return '<select name="' . $selectName . '" '
            . 'id="' . $this->buildSelectId($selectName, $featArray) . '" '
            . 'size="' . $this->calculateSelectOptionsSize($aElements, $featArray) . '"'
            . $this->eventOnChange($featArray)
            . $this->featureArraySimpleTranslated($featArray, 'disabled')
            . $this->featureArraySimpleTranslated($featArray, 'hidden')
            . $this->featureArraySimpleTranslated($featArray, 'multiselect')
            . $this->featureArraySimpleTranslated($featArray, 'style')
            . '>' . $this->setOptionsForSelect($aElements, $sDefaultValue, $featArray) . '</select>';
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

    private function setOptionSelected($optionValue, $sDefaultValue)
    {
        if (is_array($sDefaultValue)) {
            if (in_array($optionValue, $sDefaultValue)) {
                return ' selected="selected"';
            }
        } elseif (strcasecmp($optionValue, $sDefaultValue) === 0) {
            return ' selected="selected"';
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
                . '<option value="' . $key . '"' . $this->setOptionSelected($key, $sDefaultValue)
                . $this->featureArraySimpleTranslated($featArray, 'styleForOption') . '>'
                . str_replace(['&', $crtGroup], ['&amp;', ''], $value) . '</option>';
        }
        $sReturn[] = $this->setOptionGroupEnd($crtGroup, $featArray);
        return $this->featureArraySimpleTranslated($featArray, 'include_null') . implode('', $sReturn);
    }

}
