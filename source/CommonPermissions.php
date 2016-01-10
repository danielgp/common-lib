<?php

/**
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2016 Daniel Popiniuc
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
trait CommonPermissions
{

    /**
     * Returns an array with meaningfull content of permissions
     *
     * @param int $permissionNumber
     * @return array
     */
    protected function explainPerms($permissionNumber)
    {
        $firstFlag            = $this->matchFirstFlagSingle($permissionNumber);
        $permissionsString    = substr(sprintf('%o', $permissionNumber), -4);
        $numericalPermissions = $this->numericalPermissionsArray();
        return [
            'Permissions' => $permissionNumber,
            'Code'        => $permissionsString,
            'First'       => $firstFlag,
            'Overall'     => implode('', [
                $firstFlag['code'],
                $numericalPermissions[substr($permissionsString, 1, 1)]['code'],
                $numericalPermissions[substr($permissionsString, 2, 1)]['code'],
                $numericalPermissions[substr($permissionsString, 3, 1)]['code'],
            ]),
            'First'       => $firstFlag,
            'Owner'       => $numericalPermissions[substr($permissionsString, 1, 1)],
            'Group'       => $numericalPermissions[substr($permissionsString, 2, 1)],
            'World/Other' => $numericalPermissions[substr($permissionsString, 3, 1)],
        ];
    }

    private function firstFlagCodeArray()
    {
        return [
            0x8000 => '-',
            0x6000 => 'b',
            0x2000 => 'c',
            0x4000 => 'd',
            0xA000 => 'l',
            0x1000 => 'p',
            0xC000 => 's',
        ];
    }

    private function firstFlagDescriptionArray($codeFlag)
    {
        $aCodes = [
            '-' => 'Regular',
            'b' => 'Block special',
            'c' => 'Character special',
            'd' => 'Directory',
            'l' => 'Symbolic Link',
            'p' => 'FIFO pipe',
            's' => 'Socket',
            'w' => 'Whiteout',
        ];
        return ['code' => $codeFlag, 'name' => $aCodes[$codeFlag]];
    }

    private function matchFirstFlagSingle($permissionNumber)
    {
        $aCodes      = $this->firstFlagCodeArray();
        $matchedCode = 'w';
        foreach ($aCodes as $key => $value) {
            if (($permissionNumber & $key) == $key) {
                $matchedCode = $value;
            }
        }
        return $this->firstFlagDescriptionArray($matchedCode);
    }

    private function numericalPermissionsArray()
    {
        return [
            0 => ['code' => '---', 'name' => 'none'],
            1 => ['code' => '--x', 'name' => 'execute only'],
            2 => ['code' => '-w-', 'name' => 'write only'],
            3 => ['code' => '-wx', 'name' => 'write and execute'],
            4 => ['code' => 'r--', 'name' => 'read only'],
            5 => ['code' => 'r-x', 'name' => 'read and execute'],
            6 => ['code' => 'rw-', 'name' => 'read and write'],
            7 => ['code' => 'rwx', 'name' => 'read, write and execute'],
        ];
    }
}
