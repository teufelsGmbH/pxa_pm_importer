<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Domain\Model\DTO;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Domain\Model\DTO\ImportStatusInfo;
use Pixelant\PxaPmImporter\Domain\Model\Import;

/**
 * Class ImportStatusInfoTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Domain\Model\DTO
 */
class ImportStatusInfoTest extends UnitTestCase
{
    /**
     * @test
     */
    public function initValuesForImportStatusIsCurrentDateAndProgressZero()
    {
        $import = $this->createMock(Import::class);

        $importStatus = new ImportStatusInfo($import);

        $this->assertInstanceOf(\DateTime::class, $importStatus->getStartDate());
        $this->assertEquals(0.00, $importStatus->getProgress());
    }

    /**
     * @test
     */
    public function importStatusReturnImport()
    {
        $import = $this->createMock(Import::class);

        $importStatus = new ImportStatusInfo($import);

        $this->assertSame($import, $importStatus->getImport());
    }

    /**
     * @test
     */
    public function importStatusReturnStartDate()
    {
        $import = $this->createMock(Import::class);
        $time = time();

        $importStatus = new ImportStatusInfo($import, $time);

        $this->assertEquals($time, $importStatus->getStartDate()->getTimestamp());
    }

    /**
     * @test
     */
    public function importStatusReturnProgress()
    {
        $import = $this->createMock(Import::class);
        $progress = 99.95;

        $importStatus = new ImportStatusInfo($import, time(), $progress);

        $this->assertEquals($progress, $importStatus->getProgress());
    }

    /**
     * @test
     */
    public function isAvailableByDefaultIsTrue()
    {
        $import = $this->createMock(Import::class);

        $importStatus = new ImportStatusInfo($import);

        $this->assertTrue($importStatus->isAvailable());
    }

    /**
     * @test
     */
    public function isAvailableCanBeSet()
    {
        $import = $this->createMock(Import::class);

        $importStatus = new ImportStatusInfo($import);
        $importStatus->setIsAvailable(false);

        $this->assertFalse($importStatus->isAvailable());
    }

    /**
     * @test
     */
    public function importStatusToArrayReturnArrayInfo()
    {
        $import = new Import();
        $import->_setProperty('uid', 12);

        $time = time();
        $progress = 99.95;

        $importStatus = new ImportStatusInfo($import, $time, $progress);

        $expect = [
            'import' => 12,
            'start' => $time,
            'progress' => $progress
        ];

        $this->assertEquals($expect, $importStatus->toArray());
    }
}
