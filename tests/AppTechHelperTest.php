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

namespace SymfonyCasts\InternalTestHelpers\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use SymfonyCasts\InternalFixtures\TestBundleFixture;
use SymfonyCasts\InternalTestHelpers\AppTestHelper;

/**
 * @covers \AppTestHelper
 */
#[CoversClass(AppTestHelper::class)]
class AppTechHelperTest extends TestCase
{
    /** @var bool Set false to keep the `testBundle/tests` dir in development. */
    private bool $cleanupAfterTest = true;

    protected function setUp(): void
    {
        // we need the fixture to be a git repo so the helper can clone it.
        Process::fromShellCommandline(
            'git init && git config user.name "symfonycasts" && git config user.email "symfonycasts@example.com" && git config commit.gpgsign false && git add . && git commit -am "init"',
            \dirname(__DIR__).'/testBundle'
        )->mustRun();
    }

    // we dont want the fixture to be a repo after the test, otherwise we cant maintain it.
    protected function tearDown(): void
    {
        $fixturePath = \dirname(__DIR__).'/testBundle';
        $fs = new Filesystem();

        if ($this->cleanupAfterTest) {
            $fs->remove($fixturePath.'/tests');
            Process::fromShellCommandline('git reset --hard && git clean -fdx', $fixturePath)
                ->mustRun()
            ;
        }

        if ($fs->exists($gitDir = $fixturePath.'/.git')) {
            $fs->remove($gitDir);
        }
    }

    public function testInit(): void
    {
        $helper = new AppTestHelper(TestBundleFixture::class);
        $helper->init('symfonycasts/internal-test-fixture');

        // This dir should not exist in BundleFixture, it's created by the helper
        $cacheDir = \dirname(__DIR__).'/testBundle/tests/tmp/cache';
        self::assertDirectoryExists($cacheDir);

        // Ensure the skeleton was created
        self::assertDirectoryExists(\sprintf('%s/skeleton', $cacheDir));
        self::assertFileExists(\sprintf('%s/skeleton/src/Kernel.php', $cacheDir));

        // Ensure the "project" (our BundleFixture) was cloned
        self::assertDirectoryExists(\sprintf('%s/project', $cacheDir));
        self::assertFileExists(\sprintf('%s/project/src/TestBundleFixture.php', $cacheDir));

        $expectedAppPath = $helper->createAppForTest();

        self::assertDirectoryExists($expectedAppPath);
        self::assertFileExists(\sprintf('%s/composer.json', $expectedAppPath));
    }

    public function testInitHandlesAdditionalSkeletonDependencies(): void
    {
        $helper = new AppTestHelper(TestBundleFixture::class, ['symfonycasts/reset-password-bundle']);
        $helper->init('symfonycasts/internal-test-fixture');

        $cacheDir = \dirname(__DIR__).'/testBundle/tests/tmp/cache';
        self::assertDirectoryExists(\sprintf('%s/skeleton/vendor/symfonycasts/reset-password-bundle', $cacheDir));
    }
}
