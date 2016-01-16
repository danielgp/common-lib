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

    protected $commonLibFlags   = null;
    protected $tCmnLb           = null;
    protected $tCmnRequest      = null;
    protected $tCmnSession      = null;
    protected $tCmnSuperGlobals = null;

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
     * (In order to avoid potential language injections from other applications session will revert
     * to the default language if application one is not among the one are not supported here)
     *
     * @return NOTHING
     */
    private function handleLanguageIntoSession()
    {
        $this->settingsCommonLib();
        $this->initializeSprGlbAndSession();
        if (is_null($this->tCmnSuperGlobals->get('lang'))) {
            $this->tCmnSession->set('lang', $this->commonLibFlags['default_language']);
        } elseif (!is_null($this->tCmnSuperGlobals->get('lang'))) {
            $this->tCmnSession->set('lang', filter_var($this->tCmnSuperGlobals->get('lang'), FILTER_SANITIZE_STRING));
        }
        if (!array_key_exists($this->tCmnSession->get('lang'), $this->commonLibFlags['available_languages'])) {
            $this->tCmnSession->set('lang', $this->commonLibFlags['default_language']);
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
                . $this->tCmnSession->get('lang') . '/LC_MESSAGES/'
                . $this->commonLibFlags['localization_domain']
                . '.mo';
        $extrClass        = new \Gettext\Extractors\Mo();
        $translations     = $extrClass->fromFile($localizationFile);
        $this->tCmnLb     = new \Gettext\Translator();
        $this->tCmnLb->loadTranslations($translations);
    }

    protected function initializeSprGlbAndSession()
    {
        if (is_null($this->tCmnSuperGlobals)) {
            $this->tCmnRequest      = new \Symfony\Component\HttpFoundation\Request;
            $this->tCmnSuperGlobals = $this->tCmnRequest->createFromGlobals();
        }
        if (is_null($this->tCmnSession)) {
            $sBridge           = new \Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage();
            $this->tCmnSession = new \Symfony\Component\HttpFoundation\Session\Session($sBridge);
            $this->tCmnSession->start();
        }
    }

    /**
     * Central function to deal with multi-language messages
     *
     * @param string $localizedStringCode
     * @return string
     */
    protected function lclMsgCmn($localizedStringCode)
    {
        if (is_null($this->tCmnLb)) {
            $this->settingsCommonLib();
            $this->handleLocalizationCommon();
        }
        return $this->tCmnLb->gettext($localizedStringCode);
    }

    protected function lclMsgCmnNumber($singularString, $pluralString, $numberToEvaluate)
    {
        if (is_null($this->tCmnLb)) {
            $this->settingsCommonLib();
            $this->handleLocalizationCommon();
        }
        return $this->tCmnLb->ngettext($singularString, $pluralString, $numberToEvaluate);
    }

    /**
     * Returns proper result from a mathematical division in order to avoid
     * Zero division erorr or Infinite results
     *
     * @param float $fAbove
     * @param float $fBelow
     * @param mixed $mArguments
     * @return decimal
     */
    protected function setDividedResult($fAbove, $fBelow, $mArguments = 0)
    {
        if (($fAbove == 0) || ($fBelow == 0)) { // prevent infinite result AND division by 0
            return 0;
        }
        if (is_array($mArguments)) {
            $frMinMax = [
                'MinFractionDigits' => $mArguments[1],
                'MaxFractionDigits' => $mArguments[1],
            ];
            return $this->setNumberFormat(($fAbove / $fBelow), $frMinMax);
        }
        return $this->setNumberFormat(round(($fAbove / $fBelow), $mArguments));
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
        $this->handleLanguageIntoSession();
        if (is_null($features)) {
            $features = [
                'locale'            => $this->tCmnSession->get('lang'),
                'style'             => \NumberFormatter::DECIMAL,
                'MinFractionDigits' => 0,
                'MaxFractionDigits' => 0,
            ];
        }
        if (!array_key_exists('locale', $features)) {
            $features['locale'] = $this->tCmnSession->get('lang');
        }
        if (!array_key_exists('style', $features)) {
            $features['style'] = \NumberFormatter::DECIMAL;
        }
        return $features;
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
