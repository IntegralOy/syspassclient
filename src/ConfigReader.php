<?php

declare(strict_types=1);

/**
 * This file is part of the sysPassClient package.
 *
 * (c) Integral Oy <integral@integral.fi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Integral\SysPass;

class ConfigReader
{
    /**
     * Reads config in this order:
     *  1: From absolute file given as parameter
     *  2: From file given as parameter relative to cwd
     *  3: From user home dir ~/.syspass/config.json
     *  4: install_dir/config.json
     *
     * @param string|null $configFile
     * @return array
     */
    public function read(string $configFile = null): array
    {
        $homeConfig = isset($_SERVER['HOME']) ? $_SERVER['HOME'] . '/.syspass/config.json' : '';

        if ($configFile !== null && file_exists($configFile)) {
            $file = $configFile;
        } elseif ($configFile !== null) {
            $file = getcwd() . '/' . $configFile;
        } elseif (file_exists($homeConfig)) {
            $file = $homeConfig;
        } else {
            $file = __DIR__ . '/../config.json';
        }

        if (file_exists($file) && is_readable($file)) {
            $config = json_decode(file_get_contents($file), true);

            if (!empty($config['token']) && !empty($config['pass']) && !empty($config['host'])) {
                return $config;
            }
        }

        throw new \RuntimeException('Invalid configuration in: ' . $file);
    }
}
