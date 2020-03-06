<?php

namespace Pixelant\PxaPmImporter\Tests\Unit\Utility;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Utility\HashUtility;

class HashTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getImportIdHashReturnImportHash()
    {
        $id = 'test';

        $this->assertEquals(md5($id), HashUtility::hashImportId($id));
    }
}
