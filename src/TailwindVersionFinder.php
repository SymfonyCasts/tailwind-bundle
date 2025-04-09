<?php

/*
 * This file is part of the SymfonyCasts TailwindBundle package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\TailwindBundle;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Finds the latest Tailwind CSS version by major version.
 *
 * @author Kevin Bond <ryan@symfonycasts.com>
 */
final class TailwindVersionFinder
{
    private HttpClientInterface $httpClient;

    public function latestVersionFor(int $majorVersion): string
    {
        foreach ($this->tags() as $tag) {
            if (str_starts_with($tag, "v$majorVersion.")) {
                return $tag;
            }
        }

        throw new \RuntimeException(\sprintf('Could not find a Tailwind CSS %d.x release.', $majorVersion));
    }

    /**
     * @return string[]
     */
    private function tags(int $page = 1): iterable
    {
        $releases = $this->httpClient()
            ->request('GET', 'https://api.github.com/repos/tailwindlabs/tailwindcss/releases', [
                'query' => ['page' => $page],
            ])
            ->toArray()
        ;

        if (!$releases) {
            return;
        }

        foreach ($releases as $release) {
            yield $release['tag_name'];
        }

        yield from $this->tags(++$page);
    }

    private function httpClient(): HttpClientInterface
    {
        return $this->httpClient ??= HttpClient::create();
    }
}
