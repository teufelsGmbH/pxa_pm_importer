<?php
namespace Pixelant\PxaPmImporter\Tests\Unit\Processors\Relation\Traits;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Processors\Traits\ImportListValue;

/**
 * Class ImportListValueTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Processors\Relation\Traits
 */
class ImportListValueTest extends UnitTestCase
{
    use ImportListValue;

    /**
     * @test
     */
    public function convertFilesListValueToArrayThrowExceptionIfNonArrayOrNonStringGiven()
    {
        $list = 123;

        $this->expectException(\InvalidArgumentException::class);

        $this->convertListToArray($list);
    }

    /**
     * @test
     */
    public function convertFilesListValueToArrayReturnGivenArray()
    {
        $list = ['path1', 'path2'];

        $this->assertEquals($list, $this->convertListToArray($list));
    }

    /**
     * @test
     */
    public function convertFilesListValueToArrayConvertStringToArray()
    {
        $list = 'path1,path2, path3';
        $expect = ['path1', 'path2', 'path3'];

        $this->assertEquals($expect, $this->convertListToArray($list));
    }
}
