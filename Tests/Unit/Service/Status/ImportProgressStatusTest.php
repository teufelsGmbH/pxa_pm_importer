<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Service\Status;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pixelant\PxaPmImporter\Domain\Model\DTO\ImportStatusInfo;
use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Registry\RegistryCore;
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
        $mockedRegistry = $this->createPartialMock(RegistryCore::class, ['remove']);
        $mockedRegistry
            ->expects($this->once())
            ->method('remove');

        $this->inject($this->subject, 'registry', $mockedRegistry);

        $import = new Import();
        $import->_setProperty('uid', 21);

        $this->subject->endImport($import);
    }

    /**
     * @test
     */
    public function updateImportProgressWillSetNewProgressValue()
    {
        $uid = 2233;
        $import = new Import();
        $import->_setProperty('uid', $uid);

        $runningInfo = [
            'running_2233' => [
                'progress' => 34
            ]
        ];

        $this->subject
            ->expects($this->once())
            ->method('getFromRegistry')
            ->with($import)
            ->willReturn($runningInfo);

        $newRunningInfo = $runningInfo;
        $newProgress = 45.45;
        $newRunningInfo['progress'] = $newProgress;

        $this->subject
            ->expects($this->once())
            ->method('registrySet')
            ->with($import, $newRunningInfo);

        $this->subject->updateImportProgress($import, $newProgress);
    }

    /**
     * @test
     */
    public function getImportStatusWillReturnStatusIfSet()
    {
        $uid = 44;
        $import = new Import();
        $import->_setProperty('uid', $uid);

        $runningInfo = [
            'start' => time(),
            'progress' => 12
        ];

        $this->subject
            ->expects($this->once())
            ->method('getFromRegistry')
            ->with($import)
            ->willReturn($runningInfo);

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
        $import = new Import();
        $import->_setProperty('uid', $uid);

        $this->subject
            ->expects($this->once())
            ->method('getFromRegistry')
            ->with($import);

        $importStatus = $this->subject->getImportStatus($import);
        $this->assertInstanceOf(ImportStatusInfo::class, $importStatus);
        $this->assertFalse($importStatus->isAvailable());
    }
}
