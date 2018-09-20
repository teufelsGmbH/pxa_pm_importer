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

    /**
     * Convert excel columns A to 0, B to 1 and so on
     * @test
     */
    public function convertAlphabetColumnToNumber()
    {
        $columnToExpect = [
            'A' => 0,
            'b' => 1,
            'Aa' => 26,
            'AB' => 27,
            'AZ' => 51,
            'SZ' => 519,
        ];

        foreach ($columnToExpect as $column => $expect) {
            $this->assertEquals(
                $expect,
                MainUtility::convertAlphabetColumnToNumber($column)
            );
        }
    }
}
