<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Service\Status;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pixelant\PxaPmImporter\Domain\Model\DTO\ImportStatusInfo;
use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Service\Status\ImportProgressStatus;

/**
 * Class ImportProgressStatusTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Service\Status
 */
class ImportProgressStatusTest extends UnitTestCase
{

    /**
     * @var ImportProgressStatus|MockObject
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();

        $this->subject = $this->createPartialMock(ImportProgressStatus::class, ['registrySet', 'getFromRegistry']);
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->subject);
    }

    /**
     * @test
     */
    public function startImportWillSetRegistryArrayWithInfo()
    {
        $this->subject
            ->expects($this->once())
            ->method('getFromRegistry')
            ->willReturn([]);

        $import = new Import();
        $import->_setProperty('uid', 12);

        $this->subject
            ->expects($this->once())
            ->method('registrySet');

        $this->subject->startImport($import);
    }

    /**
     * @test
     */
    public function endImportWillSetRegistryArrayWithoutImportInfo()
    {
        $runningInfo = [
            21 => [
                'progress' => 99
            ]
        ];

        $this->subject
            ->expects($this->once())
            ->method('getFromRegistry')
            ->willReturn($runningInfo);

        $import = new Import();
        $import->_setProperty('uid', 21);

        $this->subject
            ->expects($this->once())
            ->method('registrySet')
            ->with([]);

        $this->subject->endImport($import);
    }

    /**
     * @test
     */
    public function updateImportProgressWillSetNewProgressValue()
    {
        $uid = 2233;
        $runningInfo = [
            $uid => [
                'progress' => 34
            ]
        ];

        $this->subject
            ->expects($this->once())
            ->method('getFromRegistry')
            ->willReturn($runningInfo);

        $import = new Import();
        $import->_setProperty('uid', $uid);

        $newRunningInfo = $runningInfo;
        $newProgress = 45.45;
        $newRunningInfo[$uid]['progress'] = $newProgress;

        $this->subject
            ->expects($this->once())
            ->method('registrySet')
            ->with($newRunningInfo);

        $this->subject->updateImportProgress($import, $newProgress);
    }

    /**
     * @test
     */
    public function getImportStatusWillReturnStatusIfSet()
    {
        $uid = 44;
        $runningInfo = [
            $uid => [
                'start' => time(),
                'progress' => 12
            ]
        ];

        $this->subject
            ->expects($this->once())
            ->method('getFromRegistry')
            ->willReturn($runningInfo);

        $import = new Import();
        $import->_setProperty('uid', $uid);

        $importStatus = $this->subject->getImportStatus($import);
        $this->assertInstanceOf(ImportStatusInfo::class, $importStatus);
        $this->assertTrue($importStatus->isAvailable());
    }

    /**
     * @test
     */
    public function getImportStatusWillReturnStatusWithNotAvailableIfNotSet()
    {
        $uid = 44;
        $runningInfo = [
            12 => [
                'progress' => 12
            ]
        ];

        $this->subject
            ->expects($this->once())
            ->method('getFromRegistry')
            ->willReturn($runningInfo);

        $import = new Import();
        $import->_setProperty('uid', $uid);

        $importStatus = $this->subject->getImportStatus($import);
        $this->assertInstanceOf(ImportStatusInfo::class, $importStatus);
        $this->assertFalse($importStatus->isAvailable());
    }
}
