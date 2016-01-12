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
trait DomBasicComponentsByDanielGP
{

    private function buildAttributesForTag($features)
    {
        if (!is_array($features)) {
            return '';
        }
        $attributes = '';
        foreach ($features as $key => $value) {
            $val = $this->buildAttributesForTagValueArray($value);
            $attributes .= ' ' . $key . '="' . $val . '"';
        }
        return $attributes;
    }

    private function buildAttributesForTagValueArray($value)
    {
        $val = $value;
        if (is_array($value)) {
            $valA = [];
            foreach ($value as $key2 => $value2) {
                $valA[] = $key2 . ':' . $value2;
            }
            $val = implode(';', $valA) . ';';
        }
        return $val;
    }

    /**
     * Capatalize first letter of each word
     * AND filters only letters and numbers
     *
     * @param string $givenString
     * @return string
     */
    protected function cleanStringForId($givenString)
    {
        return preg_replace("/[^a-zA-Z0-9]/", '', ucwords($givenString));
    }

    /**
     * Cleans a string for certain internal rules
     *
     * @param type $urlString
     * @return type
     */
    protected function setCleanUrl($urlString)
    {
        $arrayToReplace = [
            '&#038;'    => '&amp;',
            '&'         => '&amp;',
            '&amp;amp;' => '&amp;',
            ' '         => '%20',
        ];
        $kys            = array_keys($arrayToReplace);
        $vls            = array_values($arrayToReplace);
        return str_replace($kys, $vls, filter_var($urlString, FILTER_SANITIZE_URL));
    }

    /**
     * Returns a div tag that clear any float
     *
     * @param integer $height
     */
    protected function setClearBoth1px($height = 1)
    {
        $divStyle = implode('', [
            'height:' . $height . 'px;',
            'line-height:' . $height . 'px;',
            'float:none;',
            'clear:both;',
            'margin:0px;'
        ]);
        return $this->setStringIntoTag('&nbsp;', 'div', ['style' => $divStyle]);
    }

    /**
     * Sets the no-cache header
     */
    protected function setHeaderNoCache($contentType = 'application/json')
    {
        header("Content-Type: " . $contentType . "; charset=utf-8");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }

    /**
     * Puts a given string into a specific short tag
     *
     * @param string $sTag
     * @param array $features
     * @return string
     */
    protected function setStringIntoShortTag($sTag, $features = null)
    {
        return '<' . $sTag . $this->buildAttributesForTag($features)
                . (isset($features['dont_close']) ? '' : '/') . '>';
    }

    /**
     * Puts a given string into a specific tag
     *
     * @param string $sString
     * @param string $sTag
     * @param array $features
     * @return string
     */
    protected function setStringIntoTag($sString, $sTag, $features = null)
    {
        return '<' . $sTag . $this->buildAttributesForTag($features) . '>' . $sString . '</' . $sTag . '>';
    }
}
