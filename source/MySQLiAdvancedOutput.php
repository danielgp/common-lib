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

    /**
     * Returns a Time field 2 use in a form
     *
     * @param string $table_source
     * @param string $field_type
     * @param array $value
     * @param array $features
     * @param string $iar
     * @return string
     */
    private function getFieldOutputTime($field_type, $value, $iar = null)
    {
        $input = null;
        switch ($field_type) {
            case 'time':
                $ia = [
                    'type'      => 'text',
                    'size'      => 9,
                    'maxlength' => 9,
                    'name'      => $value['Field'],
                    'id'        => $value['Field'],
                    'value'     => $this->getFieldValue($value),
                ];
                if (isset($iar)) {
                    $ia = array_merge($ia, $iar);
                }
                $input = $this->setStringIntoShortTag('input', $ia);
                break;
        }
        return $input;
    }

    /**
     * Returns a Timestamp field 2 use in a form
     *
     * @param string $table_source
     * @param string $field_type
     * @param array $value
     * @param array $features
     * @param string $iar
     * @return string
     */
    private function getFieldOutputTimestamp($field_type, $value, $iar = null)
    {
        $input = null;
        switch ($field_type) {
            case 'timestamp':
            case 'datetime':
                $ia = [
                    'type'      => 'text',
                    'size'      => 19,
                    'maxlength' => 19,
                    'name'      => $value['Field'],
                    'id'        => $value['Field'],
                    'value'     => $this->getFieldValue($value),
                ];
                if (isset($iar)) {
                    $ia = array_merge($ia, $iar);
                }
                $input = $this->setStringIntoShortTag('input', $ia);
                if (!isset($iar['readonly'])) {
                    $input .= $this->setCalendarControlWithTime($value['Field']);
                }
                break;
        }
        return $input;
    }

    /**
     * Returns a Text field 2 use in a form
     *
     * @param string $table_source
     * @param string $field_type
     * @param array $value
     * @param array $features
     * @param string $iar
     * @return string
     */
    private function getFieldOutputTextLarge($field_type, $value, $iar = null)
    {
        $input = null;
        switch ($field_type) {
            case 'text':
            case 'blob':
                $ia = [
                    'name' => $value['Field'],
                    'id'   => $value['Field'],
                    'rows' => 4,
                    'cols' => 55,
                ];
                if (isset($iar)) {
                    $ia = array_merge($ia, $iar);
                }
                $input = $this->setStringIntoTag($this->getFieldValue($value), 'textarea', $ia);
                break;
        }
        return $input;
    }

    /**
     * Returns given value for a field from $_REQUEST
     *
     * @param array $details
     * @return string
     */
    private function getFieldValue($details)
    {
        $sReturn = '';
        if (isset($_REQUEST[$details['Field']])) {
            $sReturn = $_REQUEST[$details['Field']];
        } else {
            if ($details['Default'] == 'NULL') {
                if ($details['Null'] == 'YES') {
                    $sReturn = 'NULL';
                } else {
                    $sReturn = '';
                }
            } else {
                $sReturn = $details['Default'];
            }
        }
        return $sReturn;
    }
}
