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

class Clipboard
{
    /**
     * Checks if the underlying platform is supported by this library
     * @return bool
     */
    public function isSupported(): bool
    {
        if (PHP_OS === 'Darwin') {
            return $this->commandExists('pbcopy');
        } elseif (PHP_OS === 'Linux') {
            return $this->commandExists('xclip');
        }

        return false;
    }

    /**
     * Copies the given $string to system clipboard
     *
     * @param string $string The string to copy to clipboard
     * @return bool true on success, false on failure
     * @throws \RuntimeException
     * @throws \Exception
     */
    public function copy($string) : bool
    {
        if (PHP_OS === 'Darwin') {
            return $this->copy2clipboardMac($string);
        }

        if (PHP_OS === 'Linux') {
            return $this->copy2clipboardLinux($string);
        }

        throw new \RuntimeException(sprintf('Your operating system %s is not supported by Clipboard', PHP_OS));
    }

    /**
     * @param string $string
     * @return bool
     */
    protected function copy2clipboardMac(string $string) : bool
    {
        if (!$this->commandExists('pbcopy')) {
            throw new \RuntimeException('Please install pbcopy to copy password directly to clipboard');
        }

        return $this->openProcess('pbcopy', $string);
    }

    /**
     * @param string $string
     * @return bool
     * @throws \RuntimeException
     */
    protected function copy2clipboardLinux(string $string) : bool
    {
        if (!$this->commandExists('xclip')) {
            throw new \RuntimeException('Please install xclip to copy password directly to clipboard');
        }

        return $this->openProcess('xclip -selection clipboard -f | xclip -selection primary', $string);
    }

    protected function commandExists(string $command): bool
    {
        $output = null;
        $exit = null;

        exec(escapeshellcmd('which '.$command), $output, $exit);

        return $exit === 0;
    }

    /**
     * @param string $command
     * @param string $input
     * @return bool
     */
    protected function openProcess(string $command, string $input) : bool
    {
        $stdErr = sys_get_temp_dir().'/syspassclient_stderr.txt';
        $descriptor = [
            0 => ['pipe', 'r'],  // stdin is a pipe that the child will read from
            1 => ['pipe', 'w'],  // stdout is a pipe that the child will write to
            2 => ['file', $stdErr, 'a'] // stderr is a file to write to
        ];

        $process = proc_open($command, $descriptor, $pipes);
        if (\is_resource($process)) {
            fwrite($pipes[0], $input);
            fclose($pipes[0]);
            fclose($pipes[1]);

            $return_value = proc_close($process);
            if ($return_value === 0) {
                return true;
            }
        }

        return false;
    }
}
