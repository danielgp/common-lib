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
 * Usefull functions to support multi-language feedback
 *
 * @author Daniel Popiniuc
 */
trait CommonLibLocale
{

    protected $commonLibFlags = null;
    protected $tCmnLb         = null;

    /**
     * Takes care of instatiation of localization libraries
     * used within current module for multi-languages support
     *
     * @return NOTHING
     */
    private function handleLocalizationCommon()
    {
        if (isset($_GET['lang'])) {
            $_SESSION['lang'] = filter_var($_GET['lang'], FILTER_SANITIZE_STRING);
        } elseif (!isset($_SESSION['lang'])) {
            $_SESSION['lang'] = $this->commonLibFlags['default_language'];
        }
        /* to avoid potential language injections from other applications that do not applies here */
        if (!in_array($_SESSION['lang'], array_keys($this->commonLibFlags['available_languages']))) {
            $_SESSION['lang'] = $this->commonLibFlags['default_language'];
        }
        $localizationFile = __DIR__ . '/locale/' . $_SESSION['lang'] . '/LC_MESSAGES/'
                . $this->commonLibFlags['localization_domain']
                . '.mo';
        $translations     = \Gettext\Extractors\Mo::fromFile($localizationFile);
        $this->tCmnLb     = new \Gettext\Translator();
        $this->tCmnLb->loadTranslations($translations);
    }

    /**
     * Takes care of instatiation of common flags used internally winthin current trait
     *
     * @returns NOTHING
     */
    protected function initCommomLibParameters()
    {
        $this->commonLibFlags = [
            'available_languages' => [
                'en_US' => 'US English',
                'ro_RO' => 'Română',
                'it_IT' => 'Italiano',
            ],
            'default_language'    => 'en_US',
            'localization_domain' => 'common-locale'
        ];
        $this->handleLocalizationCommon();
    }

    /**
     * Central function to deal with multi-language messages
     *
     * @param string $localizedStringCode
     * @return string
     */
    protected function lclMsgCmn($localizedStringCode)
    {
        if (is_null($this->commonLibFlags)) {
            $this->initCommomLibParameters();
        }
        return $this->tCmnLb->gettext($localizedStringCode);
    }

    /**
     * Create an upper right box with choices for languages
     * (requires flag-icon.min.css to be loaded)
     * (makes usage of custom class "upperRightBox" and id = "visibleOnHover", provided here as scss file)
     *
     * @param array $aAvailableLanguages
     * @return string
     */
    protected function setUppeRightBoxLanguages($aAvailableLanguages)
    {
        $sReturn   = [];
        $sReturn[] = '<div style="text-align:right;">'
                . '<span class="flag-icon flag-icon-' . strtolower(substr($_SESSION['lang'], -2))
                . '" style="margin-right:2px;">&nbsp;</span>'
                . $aAvailableLanguages[$_SESSION['lang']]
                . '</div><!-- default Language -->';
        if (isset($_REQUEST)) {
            $linkWithoutLanguage = $this->setArrayToStringForUrl('&amp;', $_REQUEST, ['lang']) . '&amp;';
        } else {
            $linkWithoutLanguage = '';
        }
        $sReturn[] = '<div id="visibleOnHover">';
        foreach ($aAvailableLanguages as $key => $value) {
            if ($_SESSION['lang'] !== $key) {
                $sReturn[] = '<a href="?' . $linkWithoutLanguage . 'lang=' . $key . '" style="display:block;">'
                        . '<span class="flag-icon flag-icon-' . strtolower(substr($key, -2))
                        . '" style="margin-right:2px;">&nbsp;</span>'
                        . $value . '</a>';
            }
        }
        $sReturn[] = '</div><!-- visibleOnHover end -->';
        return '<div class="upperRightBox">'
                . implode('', $sReturn)
                . '</div><!-- upperRightBox end -->';
    }
}
