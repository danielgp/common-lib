<?php

/*
 * The MIT License
 *
 * Copyright 2018 Daniel Popiniuc
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace danielgp\common_lib;

trait DomHeaderFooterByDanielGP
{

    use DomCssAndJavascriptByDanielGP;

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
                'onclick' => 'javascript:NewCssCal(\'' . $controlName
                . '\',\'yyyyMMdd\',\'dropdown\',false,\'24\',false);',
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
                'onclick' => 'javascript:NewCssCal(\'' . $controlName
                . '\',\'yyyyMMdd\',\'dropdown\',true,\'24\',true);',
                'class'   => 'fa fa-calendar',
                'id'      => $controlName . '_picker',
                'style'   => 'cursor:pointer;' . $additionalStyle,
        ]);
    }

    /**
     * Outputs an HTML footer
     *
     * @param array $footerInjected
     * @return string
     */
    protected function setFooterCommon($footerInjected = null)
    {
        $sHK = $this->tCmnSuperGlobals->get('specialHook');
        if (!is_null($sHK) && (in_array('noHeader', $sHK))) {
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
    protected function setHeaderCommon($headerFeatures = [])
    {
        $sReturn = [];
        $this->initializeSprGlbAndSession();
        $sHK     = $this->tCmnSuperGlobals->get('specialHook');
        if (!is_null($sHK) && (in_array('noHeader', $sHK))) {
            return ''; // no Header
        }
        $fixedHeaderElements = [
            'start'    => '<!DOCTYPE html>',
            'lang'     => $this->setHeaderLanguage($headerFeatures),
            'head'     => '<head>',
            'charset'  => '<meta charset="utf-8" />',
            'viewport' => '<meta name="viewport" content="width=device-width height=device-height initial-scale=1" />',
        ];
        if ($headerFeatures !== []) {
            $aFeatures = [];
            foreach ($headerFeatures as $key => $value) {
                $aFeatures[] = $this->setHeaderFeatures($key, $value);
            }
            return implode('', $fixedHeaderElements) . implode('', $aFeatures) . '</head>' . '<body>';
        }
        $sReturn[] = implode('', $fixedHeaderElements) . '</head>' . '<body>'
            . '<p style="background-color:red;color:#FFF;">The parameter sent to '
            . __FUNCTION__ . ' must be a non-empty array</p>' . $this->setFooterCommon();
        throw new \Exception(implode('', $sReturn));
    }

    /**
     *
     * @param string|array $value
     * @param string $sCssOrJavascript
     * @return string
     */
    private function setHeaderCssOrJavascript($value, $sCssOrJavascript)
    {
        $strFnToCall = (string) 'set' . ucwords($sCssOrJavascript) . 'File';
        if (is_array($value)) {
            $aFeatures = [];
            foreach ($value as $value2) {
                $fnResult    = call_user_func_array([$this, $strFnToCall], [$this->getSanitizedUrl($value2), null]);
                $aFeatures[] = $fnResult;
            }
            return implode('', $aFeatures);
        }
        return call_user_func_array([$this, $strFnToCall], [$this->getSanitizedUrl($value), null]);
    }

    /**
     *
     * @param string $key
     * @param string|array $value
     * @return string
     */
    private function setHeaderFeatures($key, $value)
    {
        $sReturn = '';
        if (in_array($key, ['css', 'javascript'])) {
            $sReturn = $this->setHeaderCssOrJavascript($value, $key);
        } elseif ($key == 'title') {
            $sReturn = '<title>' . filter_var($value, FILTER_SANITIZE_STRING) . '</title>';
        }
        return $sReturn;
    }

    /**
     *
     * @param array $headerFeatures
     * @return string
     */
    private function setHeaderLanguage($headerFeatures = [])
    {
        $sReturn = '<html lang="en-US">';
        if (array_key_exists('lang', $headerFeatures)) {
            $sReturn = str_replace('en-US', filter_var($headerFeatures['lang'], FILTER_SANITIZE_STRING), $sReturn);
        }
        return $sReturn;
    }

    protected function getSanitizedUrl($strInputUrl)
    {
        return filter_var($strInputUrl, FILTER_SANITIZE_URL);
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
            . '<span class="flag-icon flag-icon-' . strtolower(substr($this->tCmnSession->get('lang'), -2))
            . '" style="margin-right:2px;">&nbsp;</span>'
            . $aAvailableLanguages[$this->tCmnSession->get('lang')]
            . '</div><!-- default Language -->'
            . $this->setUpperRightVisibleOnHoverLanguages($aAvailableLanguages)
            . '</div><!-- upperRightBox end -->';
    }

    /**
     *
     * @param array $aAvailableLanguages
     * @return string
     */
    private function setUpperRightVisibleOnHoverLanguages($aAvailableLanguages)
    {
        $linkWithoutLanguage = '';
        $alR                 = $this->tCmnSuperGlobals->query->all();
        if (count($alR) > 0) {
            $linkWithoutLanguage = $this->setArrayToStringForUrl('&amp;', $alR, ['lang']) . '&amp;';
        }
        $sReturn = [];
        foreach ($aAvailableLanguages as $key => $value) {
            if ($this->tCmnSession->get('lang') !== $key) {
                $sReturn[] = '<a href="?' . $linkWithoutLanguage . 'lang=' . $key . '" style="display:block;">'
                    . '<span class="flag-icon flag-icon-' . strtolower(substr($key, -2))
                    . '" style="margin-right:2px;">&nbsp;</span>' . $value . '</a>';
            }
        }
        return '<div id="visibleOnHover">' . implode('', $sReturn) . '</div><!-- visibleOnHover end -->';
    }
}
