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

/**
 * A wip class to wip up MakerBundle style app tests...
 *
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class AppTestHelper
{
    /**
     * @TODO - reword this to make it better....
     * To get the "root path" of the project, we need the "bundle" class,
     * which we then parse and return an absolute path.
     *
     * E.g. passing in "SymfonyCastsResetPasswordBundle::class" will
     * result in "/your/dev/dir/reset-password-bundle
     *
     * @param string $bundleClass the name of the bundle class
     *
     * @return string the root path of the "bundle" being tested without
     *                a trailing "/"
     */
    public function __construct(
        public string $bundleClassName,
    ) {
    }

    public function getRootPath(): string
    {
        $r = new \ReflectionClass($this->bundleClassName);
        $offset = \strlen(sprintf('/src/%s.php', $r->getShortName()));

        return substr_replace($r->getFileName(), '', sprintf('-%d', $offset));
    }

    public function getCachePath(): string
    {
        return sprintf('%s/tests/tmp/cache', $this->getRootPath());
    }

    public function getTestAppPath(): string
    {
        return sprintf('%s/app', $this->getCachePath());
    }

    public function getCachedBundlePath(): string
    {
        return sprintf('%s/bundle', $this->getCachePath());
    }
}
