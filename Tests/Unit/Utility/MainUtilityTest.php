<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Utility;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Utility\MainUtility;

/**
 * Class MainUtilityTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Utility
 */
class MainUtilityTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getImportIdHashReturnImportHash()
    {
        $id = 'test';

        $this->assertEquals(md5($id), MainUtility::getImportIdHash($id));
    }
}
