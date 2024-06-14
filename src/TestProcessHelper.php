<?php

/*
 * ----------------------------------------------------------------------------
 * You shouldn't use this package. We do NOT provide support or BC Guarantees.
 * We will break things between releases, pull requests, commits, and/or merges.
 * ----------------------------------------------------------------------------
 *
 * This file is part of the SymfonyCasts Internal Test Helpers package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCasts\InternalTestHelpers;

use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
final class TestProcessHelper
{
    /**
     * @param string                    $command
     * @param string                    $workingDir
     * @param array<string, string|int> $env        Key-value pair to set when running a process
     *                                              e.g. ['GIT_CONFIG' => '/path']
     */
    public static function runNow(string $command, string $workingDir, array $env = []): void
    {
        Process::fromShellCommandline($command, $workingDir, $env)
            ->mustRun()
        ;
    }

    /** @phpstan-ignore missingType.iterableValue */
    public static function runNowInteractive(string $command, array $input, string $workingDir): void
    {
        $inputStream = new InputStream();
        $inputStream->write(current($input));

        $inputStream->onEmpty(static function () use ($inputStream, &$input) {
            $nextInput = next($input);

            false === $nextInput ? $inputStream->close() : $inputStream->write(sprintf("%s\n", $nextInput));
        });

        $process = Process::fromShellCommandline(command: $command, cwd: $workingDir, env: ['SHELL_INTERACTIVE' => '1'], input: $inputStream);
        $process->mustRun();
    }
}
