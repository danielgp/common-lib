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
 * Trait to expose various information from user browser
 *
 * @author Daniel Popiniuc
 */
trait BrowserAgentInfosByDanielGP
{

    /**
     * Return CPU architecture details from given user agent
     *
     * @param string $userAgent
     * @param string $targetToAnalyze
     * @return array
     */
    protected function getArchitectureFromUserAgent($userAgent, $targetToAnalyze = 'os')
    {
        $aReturn = [];
        switch ($targetToAnalyze) {
            case 'browser':
                if (strpos($userAgent, 'i586')) {
                    $aReturn = $this->listOfKnownCpuArchitectures()['ia32'];
                } elseif (strpos($userAgent, 'i586')) {
                    $aReturn = $this->listOfKnownCpuArchitectures()['ia32'];
                } elseif (strpos($userAgent, 'ia32;')) {
                    $aReturn = $this->listOfKnownCpuArchitectures()['ia32'];
                } elseif (strpos($userAgent, 'WOW64')) {
                    $aReturn = $this->listOfKnownCpuArchitectures()['ia32'];
                } elseif (strpos($userAgent, 'x86;')) {
                    $aReturn = $this->listOfKnownCpuArchitectures()['ia32'];
                } elseif (strpos($userAgent, 'Android;')) {
                    $aReturn = $this->listOfKnownCpuArchitectures()['arm'];
                } elseif (strpos($userAgent, 'amd64;')) {
                    $aReturn = $this->listOfKnownCpuArchitectures()['amd64'];
                } elseif (strpos(strtolower($userAgent), 'AMD64')) {
                    $aReturn = $this->listOfKnownCpuArchitectures()['amd64'];
                } elseif (strpos($userAgent, 'x86_64;')) {
                    $aReturn = $this->listOfKnownCpuArchitectures()['amd64'];
                } elseif (strpos($userAgent, 'Win64;') && strpos($userAgent, 'x64;')) {
                    $aReturn = $this->listOfKnownCpuArchitectures()['amd64'];
                } else {
                    $aReturn = $this->listOfKnownCpuArchitectures()['ia32'];
                }
                break;
            case 'os':
                if (strpos($userAgent, 'x86_64;')) {
                    $aReturn = $this->listOfKnownCpuArchitectures()['amd64'];
                } elseif (strpos($userAgent, 'x86-64;')) {
                    $aReturn = $this->listOfKnownCpuArchitectures()['amd64'];
                } elseif (strpos($userAgent, 'Win64;')) {
                    $aReturn = $this->listOfKnownCpuArchitectures()['amd64'];
                } elseif (strpos($userAgent, 'x64;')) {
                    $aReturn = $this->listOfKnownCpuArchitectures()['amd64'];
                } elseif (strpos(strtolower($userAgent), 'amd64;')) {
                    $aReturn = $this->listOfKnownCpuArchitectures()['amd64'];
                } elseif (strpos(strtolower($userAgent), 'AMD64')) {
                    $aReturn = $this->listOfKnownCpuArchitectures()['amd64'];
                } elseif (strpos($userAgent, 'WOW64')) {
                    $aReturn = $this->listOfKnownCpuArchitectures()['amd64'];
                } elseif (strpos($userAgent, 'x64_64;')) {
                    $aReturn = $this->listOfKnownCpuArchitectures()['amd64'];
                } elseif (strpos($userAgent, 'Android;')) {
                    $aReturn = $this->listOfKnownCpuArchitectures()['arm'];
                } else {
                    $aReturn = $this->listOfKnownCpuArchitectures()['ia32'];
                }
                break;
            default:
                $aReturn = [
                    'name' => '---'
                ];
                break;
        }
        return $aReturn;
    }

    /**
     * Provides details about browser
     *
     * @param \DeviceDetector\DeviceDetector $deviceDetectorClass
     * @param string $userAgent
     * @return array
     */
    private function getClientBrowser(\DeviceDetector\DeviceDetector $deviceDetectorClass, $userAgent)
    {
        $br                 = new \DeviceDetector\Parser\Client\Browser();
        $browserFamily      = $br->getBrowserFamily($deviceDetectorClass->getClient('short_name'));
        $browserInformation = array_merge([
            'architecture' => $this->getArchitectureFromUserAgent($userAgent, 'browser'),
            'connection'   => (isset($_SERVER['HTTP_CONNECTION']) ? $_SERVER['HTTP_CONNECTION'] : ''),
            'family'       => ($browserFamily !== false ? $browserFamily : '---'),
            'host'         => (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ''),
            'referrer'     => (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''),
            'user_agent'   => $this->getUserAgentByCommonLib(),
                ], $this->getClientBrowserAccepted());
        $clientDetails      = $deviceDetectorClass->getClient();
        if (is_array($clientDetails)) {
            $browserInformation                  = array_merge($browserInformation, $clientDetails);
            // more digestable details about version
            $browserInformation['version_major'] = explode('.', $browserInformation['version'])[0];
            $browserInformation['version_minor'] = explode('.', $browserInformation['version'])[1];
        }
        ksort($browserInformation);
        return $browserInformation;
    }

    /**
     * Returns accepted things setting from the client browser
     *
     * @return array
     */
    private function getClientBrowserAccepted()
    {
        $sReturn = [];
        if (isset($_SERVER['HTTP_ACCEPT'])) {
            $sReturn['accept'] = $_SERVER['HTTP_ACCEPT'];
        }
        if (isset($_SERVER['HTTP_ACCEPT_CHARSET'])) {
            $sReturn['accept_charset'] = $_SERVER['HTTP_ACCEPT_CHARSET'];
        }
        if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            $sReturn['accept_encoding'] = $_SERVER['HTTP_ACCEPT_ENCODING'];
        }
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $sReturn['accept_language']     = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            preg_match_all('/([a-z]{2})(?:-[a-zA-Z]{2})?/', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $m);
            $sReturn['preferred locale']    = $m[0];
            $sReturn['preferred languages'] = array_values(array_unique(array_values($m[1])));
        }
        return $sReturn;
    }

    /**
     * Provides various details about browser based on user agent
     *
     * @param array $returnType
     * @return array
     */
    protected function getClientBrowserDetails($returnType = ['Browser', 'Device', 'OS'], $tmpFolder = null)
    {
        if (isset($_GET['ua'])) {
            $userAgent = $_GET['ua'];
        } else {
            if (PHP_SAPI === 'cli' || empty($_SERVER['REMOTE_ADDR'])) {  // command line
                $userAgent = 'PHP/' . PHP_VERSION . ' comand-line';
            } else {
                $userAgent = $_SERVER['HTTP_USER_AGENT'];
            }
        }
        $dd = new \DeviceDetector\DeviceDetector($userAgent);
        if (is_null($tmpFolder)) {
            $tmpFolder = '../../tmp/DoctrineCache/';
        }
        $dd->setCache(new \Doctrine\Common\Cache\PhpFileCache($tmpFolder));
        $dd->discardBotInformation();
        $dd->parse();
        if ($dd->isBot()) {
            $aReturn = [
                'Bot' => $dd->getBot(), // handle bots,spiders,crawlers,...
            ];
        } else {
            $aReturn = [];
            foreach ($returnType as $value) {
                switch ($value) {
                    case 'Browser':
                        $aReturn[$value] = $this->getClientBrowser($dd, $userAgent);
                        break;
                    case 'Device':
                        $aReturn[$value] = $this->getClientBrowserDevice($dd);
                        break;
                    case 'OS':
                        $aReturn[$value] = $this->getClientBrowserOperatingSystem($dd, $userAgent);
                        break;
                }
            }
        }
        return $aReturn;
    }

    /**
     * Returns client device details from client browser
     *
     * @param class $deviceDetectorClass
     * @return array
     */
    private function getClientBrowserDevice(\DeviceDetector\DeviceDetector $deviceDetectorClass)
    {
        $clientIp = $this->getClientRealIpAddress();
        return [
            'brand'     => $deviceDetectorClass->getDeviceName(),
            'ip'        => $clientIp,
            'ip direct' => filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP),
            'ip type'   => $this->checkIpIsPrivate($clientIp),
            'ip v4/v6'  => $this->checkIpIsV4OrV6($clientIp),
            'model'     => $deviceDetectorClass->getModel(),
            'name'      => $deviceDetectorClass->getBrandName(),
        ];
    }

    /**
     * Returns client operating system details from client browser
     *
     * @param class $deviceDetectorClass
     * @param string $userAgent
     * @return array
     */
    private function getClientBrowserOperatingSystem(\DeviceDetector\DeviceDetector $deviceDetectorClass, $userAgent)
    {
        $aReturn                 = $deviceDetectorClass->getOs();
        $aReturn['architecture'] = $this->getArchitectureFromUserAgent($userAgent, 'os');
        $os                      = new \DeviceDetector\Parser\OperatingSystem();
        $osFamily                = $os->getOsFamily($deviceDetectorClass->getOs('short_name'));
        $aReturn['family']       = ($osFamily !== false ? $osFamily : 'Unknown');
        ksort($aReturn);
        return $aReturn;
    }

    /**
     * Captures the user agent
     *
     * @return string
     */
    protected function getUserAgentByCommonLib()
    {
        if (filter_has_var(INPUT_SERVER, 'HTTP_USER_AGENT')) {
            $crtUserAgent = filter_input(INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
        } elseif (isset($_SERVER['HTTP_USER_AGENT'])) {
            $crtUserAgent = filter_var($_SERVER['HTTP_USER_AGENT'], FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
        } else {
            $crtUserAgent = null;
        }
        return $crtUserAgent;
    }

    /**
     * Holds a list of Known CPU Srchitectures as array
     *
     * @return array
     */
    private function listOfKnownCpuArchitectures()
    {
        return [
            'amd64' => [
                'bits'          => 64,
                'nick'          => 'x64',
                'name'          => 'AMD/Intel x64',
                'name and bits' => 'AMD/Intel x64 (64 bit)',
            ],
            'arm'   => [
                'bits'          => 32,
                'nick'          => 'ARM',
                'name'          => 'Advanced RISC Machine',
                'name and bits' => 'Advanced RISC Machine (32 bit)',
            ],
            'arm64' => [
                'bits'          => 64,
                'nick'          => 'ARM64',
                'name'          => 'Advanced RISC Machine',
                'name and bits' => 'Advanced RISC Machine (64 bit)',
            ],
            'ia32'  => [
                'bits'          => 32,
                'nick'          => 'x86',
                'name'          => 'Intel x86',
                'name and bits' => 'Intel x86 (32 bit)',
            ],
            'ppc'   => [
                'bits'          => 32,
                'name'          => 'Power PC',
                'name and bits' => 'Power PC (32 bit)',
            ],
            'ppc64' => [
                'bits'          => 64,
                'name'          => 'Power PC',
                'name and bits' => 'Power PC (64 bit)',
            ],
            'sun'   => [
                'bits'          => 64,
                'name'          => 'Sun Sparc',
                'name and bits' => 'Sun Sparc (64 bit)',
            ],
        ];
    }
}
