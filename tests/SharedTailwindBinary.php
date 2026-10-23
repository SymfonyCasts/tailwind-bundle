<?php

/*
 * This file is part of the SymfonyCasts TailwindBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\TailwindBundle\Tests;

use Symfonycasts\TailwindBundle\TailwindBinary;

/**
 * Real Tailwind binaries shared by the tests that need to run one.
 *
 * Each binary is downloaded once and then reused, instead of being downloaded
 * into every test's own directory and deleted right after it ran: on Windows,
 * a binary that just ran can stay locked for a moment, which made the cleanup fail.
 */
final class SharedTailwindBinary
{
    private const DOWNLOAD_DIR = __DIR__.'/fixtures/var/binaries';

    public static function path(string $version): string
    {
        // downloads the binary unless it's already there
        (new TailwindBinary(self::DOWNLOAD_DIR, self::DOWNLOAD_DIR, null, $version))->createProcess();

        return self::DOWNLOAD_DIR.'/'.$version.'/'.TailwindBinary::getBinaryName(ltrim($version, 'v'));
    }
}
