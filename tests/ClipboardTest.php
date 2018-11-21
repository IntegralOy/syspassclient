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

use Integral\SysPass\Clipboard;
use PHPUnit\Framework\TestCase;

class ClipboardTest extends TestCase
{
    /** @var Clipboard */
    protected $clipboard;

    protected function setUp()
    {
        parent::setUp();
        $this->clipboard = new Clipboard();
    }

    public function testIsSupported()
    {
        $isSupported = $this->clipboard->isSupported();
        if (!$isSupported) {
            $this->markTestSkipped('Clipboard does not support your platform');
        }

        $this->assertTrue($isSupported);
    }

    /**
     * @depends testIsSupported
     */
    public function testCopy()
    {
        $this->assertTrue($this->clipboard->copy('ClipboardTest'), 'Copy method shous return true on success');
    }
}
