<?php

namespace SymfonyCasts\InternalTestHelpers\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use SymfonyCasts\InternalTestHelpers\AppTestHelper;
use SymfonyCasts\InternalTestHelpers\Tests\ProjectFixture\TestBundleFixture;

#[CoversClass(AppTestHelper::class)]
class AppTechHelperTest extends TestCase
{
    protected function setUp(): void
    {
//        $this->cleanup();
    }

    // We have to call setUp && tearDown otherwise PHPUnit && the autoloader
    // get confused (nested repositories).
    protected function tearDown(): void
    {
//        $this->cleanup();
    }

    public function testInit(): void
    {
        $helper = new AppTestHelper(\SymfonyCasts\InternalFixtures\TestBundleFixture::class);

        $helper->init('symfonycasts/internal-test-fixture');

        return;
        // This dir should not exist in BundleFixture, it's created by the helper
        $cacheDir = __DIR__.'/Fixture/BundleFixture/tests/tmp/cache';
        self::assertDirectoryExists($cacheDir);

        // Ensure the skeleton was created
        self::assertDirectoryExists(sprintf('%s/skeleton', $cacheDir));
        self::assertFileExists(sprintf('%s/skeleton/src/Kernel.php', $cacheDir));

        // Ensure the "project" (our BundleFixture) was cloned
        self::assertDirectoryExists(sprintf('%s/project', $cacheDir));
        self::assertFileExists(sprintf('%s/project/src/TestBundleFixture.php', $cacheDir));

        $expectedAppPath = $helper->createAppForTest();

        self::assertDirectoryExists($expectedAppPath);
        self::assertFileExists(sprintf('%s/composer.json', $expectedAppPath));
    }

    private function cleanup(): void
    {
        $fixtureBaseDir = __DIR__.'/Fixture/BundleFixture';

        // Git reset && git clean do not remove nested repositories. Use FS to do that.
        $fs = new Filesystem();
        if ($fs->exists($path = sprintf('%s/tests', $fixtureBaseDir))) {
            $fs->remove($path);
        }

        // Slightly overkill, but just to be sure all of our temp stuff is gone...
        Process::fromShellCommandline('git reset --hard && git clean -fdxq', $fixtureBaseDir)
            ->mustRun()
        ;
    }
}