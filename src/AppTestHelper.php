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

use Symfony\Component\Filesystem\Filesystem;

/**
 * A wip class to wip up MakerBundle style app tests...
 *
 * This helper only works with files that have been commited
 * for now. Adding in symlink to the actual project is planned.
 *
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class AppTestHelper
{
    /** Use these ENV's when running "git" in a process to ignore the hosts global git configuration. */
    private const GIT_CMD_ENV = ['GIT_CONFIG_SYSTEM' => '/dev/null', 'GIT_CONFIG_GLOBAL' => '/dev/null'];

    public readonly Filesystem $fs;

    /** The root dir of the project using this helper. */
    public readonly string $rootPath;

    /** The temp. path used to store test data, skeleton, apps, etc... */
    public readonly string $cachePath;

    /** The path used to store a fresh copy of the symfony/skeleton */
    public readonly string $skeletonPath;

    /** The path used to store a fresh copy of the project/bundle we are testing. */
    public readonly string $projectPath;

    /**
     * @TODO - reword this to make it better....
     * To get the "root path" of the project, we need the "bundle" class,
     * which we then parse and return an absolute path. In reality, this
     * can be any class that is a direct child of the "src/" directory.
     *
     * E.g. passing in "SymfonyCastsResetPasswordBundle::class" will
     * result in "/your/dev/dir/reset-password-bundle
     *
     * @param string   $bundleClassName      the name of the bundle class
     * @param string[] $skeletonDependencies an array of composer packages to be
     *                                       installed into the skeleton. e.g.
     *                                       ['symfonycasts/reset-password-bundle']
     *                                       or
     *                                       ['symfonycasts/reset-password-bundle:2.0.0']
     */
    public function __construct(
        public readonly string $bundleClassName,
        public array $skeletonDependencies = [],
    ) {
        $this->fs = new Filesystem();

        $r = new \ReflectionClass($this->bundleClassName);
        $offset = \strlen(\sprintf('/src/%s.php', $r->getShortName()));

        $this->rootPath = substr_replace($r->getFileName(), '', (int) \sprintf('-%d', $offset));
        $this->cachePath = \sprintf('%s/tests/tmp/cache', $this->rootPath);
        $this->skeletonPath = \sprintf('%s/skeleton', $this->cachePath);
        $this->projectPath = \sprintf('%s/project', $this->cachePath);
    }

    /**
     * @param string $packagistName    The name of the project/bundle used when doing a
     *                                 composer install.
     *                                 e.g. "symfonycasts/reset-password-bundle"
     * @param bool   $startFromScratch setting this to true will "rm -rf tmp/cache"
     */
    public function init(string $packagistName, bool $startFromScratch = false): self
    {
        if ($startFromScratch && $this->fs->exists($this->cachePath)) {
            $this->fs->remove($this->cachePath);
        }

        if (!$this->fs->exists($this->cachePath)) {
            $this->fs->mkdir($this->cachePath);
        }

        if ($this->fs->exists($this->skeletonPath) && $this->fs->exists($this->projectPath)) {
            return $this;
        }

        // Get & install symfony/skeleton, so we can clone it for each test
        TestProcessHelper::runNow(
            command: \sprintf('composer create-project symfony/skeleton %s --prefer-dist', $this->skeletonPath),
            workingDir: $this->cachePath
        );

        // Setup the skeleton as a "webapp" (similar to symfony new --webapp)
        TestProcessHelper::runNow(
            command: 'composer require symfony/webapp-pack --prefer-dist',
            workingDir: $this->skeletonPath
        );

        // Copy project/bundle to the "project" dir for testing
        TestProcessHelper::runNow(
            command: \sprintf('git clone %s %s --depth 1 --no-tags', $this->rootPath, $this->projectPath),
            workingDir: $this->cachePath,
            env: self::GIT_CMD_ENV
        );

        // Modify the skeleton to use the cached project/bundle for the composer install.
        $composerFileContents = file_get_contents($composerJsonPath = \sprintf('%s/composer.json', $this->skeletonPath));
        $composerJsonArray = json_decode($composerFileContents, associative: true, flags: \JSON_THROW_ON_ERROR);

        $composerJsonArray['repositories'][$packagistName] = [
            'type' => 'path',
            'url' => $this->projectPath,
            'options' => [
                'versions' => [
                    $packagistName => '99999.99',
                ],
            ],
        ];

        $encodedComposerJson = json_encode($composerJsonArray, flags: \JSON_THROW_ON_ERROR | \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES);
        file_put_contents($composerJsonPath, $encodedComposerJson);

        // Parse the skeleton dependencies array into a usable string
        $this->skeletonDependencies[] = $packagistName;
        $dependencies = implode(' ', $this->skeletonDependencies);

        // Install the cached project/bundle in the cached skeleton
        TestProcessHelper::runNow(
            command: \sprintf('composer require %s', $dependencies),
            workingDir: $this->skeletonPath
        );

        // Let's re-init the git repo for the skeleton and commit the vendor dir
        $this->reinitGitRepository($this->skeletonPath);

        return $this;
    }

    /** Clones the skeleton created in init() for a test and returns its path. */
    public function createAppForTest(): string
    {
        // random_bytes is "overkill" - we just need a random string
        $appId = bin2hex(random_bytes(5));
        $appPath = \sprintf('%s/app/%s', $this->cachePath, $appId);

        TestProcessHelper::runNow(
            command: \sprintf('git clone %s %s', $this->skeletonPath, $appPath),
            workingDir: $this->cachePath,
            env: self::GIT_CMD_ENV
        );

        return $appPath;
    }

    private function reinitGitRepository(string $path): void
    {
        if ($this->fs->exists($gitPath = \sprintf('%s/.git', $path))) {
            $this->fs->remove($gitPath);
        }

        $gitCommands = [
            'git init',
            'git config maintenance.gc.enabled false',
            'git config user.name "symfonycasts"',
            'git config user.email "symfonycasts@example.com"',
            'git add . -f',
            'git commit -a -m "time to make the test donuts" --no-edit',
            'git gc --force --prune=now',
        ];

        TestProcessHelper::runNow(implode(' && ', $gitCommands), $path, self::GIT_CMD_ENV);
    }
}
