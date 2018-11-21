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

namespace Integral\SysPass\Tests;

use Integral\SysPass\ConfigReader;
use PHPUnit\Framework\TestCase;

class ConfigReaderTest extends TestCase
{
    /** @var ConfigReader */
    protected $configReader;

    protected function setUp()
    {
        parent::setUp();
        $this->configReader = new ConfigReader();
    }

    public function testRead()
    {
        $tempFile = null;
        $json = <<<JSON
{
  "host": "https://syspass.example.com",
  "token": "your api token here",
  "pass": "your api password here"
}
JSON;
        try {
            $tempFile = tempnam(sys_get_temp_dir(), 'syspassclienttest.json');
            file_put_contents($tempFile, $json);

            $config = $this->configReader->read($tempFile);

            $this->assertInternalType('array', $config);
            $this->assertArrayHasKey('host', $config);
            $this->assertArrayHasKey('token', $config);
            $this->assertArrayHasKey('pass', $config);

            $this->assertEquals('https://syspass.example.com', $config['host']);
            $this->assertEquals('your api token here', $config['token']);
            $this->assertEquals('your api password here', $config['pass']);
        } finally {
            if ($tempFile !== null && file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
}
