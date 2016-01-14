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

    private function getCommonLocaleFolder()
    {
        $pathes     = explode(DIRECTORY_SEPARATOR, __DIR__);
        $pathDepth  = count($pathes);
        $localePath = [];
        foreach ($pathes as $key => $value) {
            if ($key < ($pathDepth - 1)) {
                $localePath[] = $value;
            }
        }
        return implode(DIRECTORY_SEPARATOR, $localePath);
    }

    /**
     * Stores given language or default one into global session variable
     *
     * @return NOTHING
     */
    private function handleLanguageIntoSession()
    {
        $this->settingsCommonLib();
        if (isset($_GET['lang'])) {
            $_SESSION['lang'] = filter_var($_GET['lang'], FILTER_SANITIZE_STRING);
        } elseif (!isset($_SESSION['lang'])) {
            $_SESSION['lang'] = $this->commonLibFlags['default_language'];
        }
        /* to avoid potential language injections from other applications that do not applies here */
        if (!in_array($_SESSION['lang'], array_keys($this->commonLibFlags['available_languages']))) {
            $_SESSION['lang'] = $this->commonLibFlags['default_language'];
        }
    }

    /**
     * Takes care of instatiation of localization libraries
     * used within current module for multi-languages support
     *
     * @return NOTHING
     */
    private function handleLocalizationCommon()
    {
        $this->handleLanguageIntoSession();
        $localizationFile = $this->getCommonLocaleFolder() . '/locale/'
                . $_SESSION['lang'] . '/LC_MESSAGES/'
                . $this->commonLibFlags['localization_domain']
                . '.mo';
        $extrClass        = new \Gettext\Extractors\Mo();
        $translations     = $extrClass->fromFile($localizationFile);
        $this->tCmnLb     = new \Gettext\Translator();
        $this->tCmnLb->loadTranslations($translations);
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
            $this->settingsCommonLib();
            $this->handleLocalizationCommon();
        }
        return $this->tCmnLb->gettext($localizedStringCode);
    }

    protected function lclMsgCmnNumber($singularString, $pluralString, $numberToEvaluate)
    {
        if (is_null($this->commonLibFlags)) {
            $this->settingsCommonLib();
            $this->handleLocalizationCommon();
        }
        return $this->tCmnLb->ngettext($singularString, $pluralString, $numberToEvaluate);
    }

    protected function setNumberFormat($content, $features = null)
    {
        $features = $this->setNumberFormatFeatures($features);
        $fmt      = new \NumberFormatter($features['locale'], $features['style']);
        $fmt->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $features['MinFractionDigits']);
        $fmt->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $features['MaxFractionDigits']);
        return $fmt->format($content);
    }

    private function setNumberFormatFeatures($features)
    {
        if (is_null($features)) {
            $features = [
                'locale'            => $_SESSION['lang'],
                'style'             => \NumberFormatter::DECIMAL,
                'MinFractionDigits' => 0,
                'MaxFractionDigits' => 0,
            ];
        }
        if (!isset($features['locale'])) {
            $features['locale'] = $_SESSION['lang'];
        }
        if (!isset($features['style'])) {
            $features['style'] = \NumberFormatter::DECIMAL;
        }
        return $features;
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
                . '<span class="flag-icon flag-icon-' . strtolower(substr($_SESSION['lang'], -2))
                . '" style="margin-right:2px;">&nbsp;</span>'
                . $aAvailableLanguages[$_SESSION['lang']]
                . '</div><!-- default Language -->'
                . $this->setVisibleOnHoverLanguages($aAvailableLanguages)
                . '</div><!-- upperRightBox end -->';
    }

    private function setVisibleOnHoverLanguages($aAvailableLanguages)
    {
        $linkWithoutLanguage = '';
        if (isset($_REQUEST)) {
            $linkWithoutLanguage = $this->setArrayToStringForUrl('&amp;', $_REQUEST, ['lang']) . '&amp;';
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
        return implode('', $sReturn);
    }

    /**
     * Settings
     *
     * @return NOTHING
     */
    private function settingsCommonLib()
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
    }
}
