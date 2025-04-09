<?php

/*
 * This file is part of the SymfonyCasts TailwindBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\TailwindBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfonycasts\TailwindBundle\TailwindVersionFinder;

class TailwindVersionFinderTest extends TestCase
{
    /**
     * @dataProvider majorVersionProvider
     */
    public function testGetLatestVersion(int $majorVersion): void
    {
        $versionDetector = new TailwindVersionFinder();
        $latestVersion = $versionDetector->latestVersionFor($majorVersion);

        $this->assertStringStartsWith('v'.$majorVersion.'.', $latestVersion);
    }

    public static function majorVersionProvider(): array
    {
        return [
            [2],
            [3],
            [4],
        ];
    }
}
