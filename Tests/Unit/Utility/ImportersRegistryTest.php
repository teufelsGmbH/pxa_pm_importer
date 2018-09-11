<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Utility;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Utility\ImportersRegistry;

/**
 * Class ImportersRegistryTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Utility
 */
class ImportersRegistryTest extends UnitTestCase
{
    /**
     * Register importer extension
     *
     * @test
     */
    public function registerImporter()
    {
        ImportersRegistry::registerImporter('pxa_test', ['Yaml']);

        $this->assertArrayHasKey('pxa_test', ImportersRegistry::getRegisterImporters());
    }
}
