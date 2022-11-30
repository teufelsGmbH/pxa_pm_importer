<?php

namespace Pixelant\PxaPmImporter\Tests\Unit\Context;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Context\Context;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Context\ImportContext;
use Pixelant\PxaPmImporter\Exception\ContextDataAlreadyExistException;
use Pixelant\PxaPmImporter\Configuration\ConfigurationInterface;
use Pixelant\PxaPmImporter\Importer\Importer;
use Pixelant\PxaPmImporter\Source\SourceInterface;


class ImportContextTest extends UnitTestCase
{
    /**
     * @var ImportContext
     */
    protected $subject = null;

    protected function setUp()
    {
        GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp') = time();
        $this->subject = new ImportContext();
    }

    protected function tearDown()
    {
        unset($this->subject, GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp'));
    }

    /**
     * @test
     */
    public function importStartTimeStampIfSetFromExecTime()
    {
        $this->assertEquals(GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp'), $this->subject->getImportStartTimeStamp());
    }

    /**
     * @test
     */
    public function canSetImportConfigurationSource()
    {
        $importConfigurationSource = 'testsource';

        $this->subject->setImportConfigurationSource($importConfigurationSource);

        $this->assertEquals($importConfigurationSource, $this->subject->getImportConfigurationSource());
    }

    /**
     * @test
     */
    public function canSetConfigurationService()
    {
        $configurationService = $this->createMock(ConfigurationInterface::class);

        $this->subject->setConfigurationService($configurationService);

        $this->assertSame($configurationService, $this->subject->getConfigurationService());
    }

    /**
     * @test
     */
    public function canSetImporterName()
    {
        $importerName = 'importerName';

        $this->subject->setImporterName($importerName);

        $this->assertEquals($importerName, $this->subject->getImporterName());
    }

    /**
     * @test
     */
    public function canSetSourceName()
    {
        $sourceName = 'sourceName';

        $this->subject->setSourceName($sourceName);

        $this->assertEquals($sourceName, $this->subject->getSourceName());
    }

    /**
     * @test
     */
    public function canSetStoragePids()
    {
        $pids = [12, 14, 15];

        $this->subject->setStoragePids($pids);

        $this->assertEquals($pids, $this->subject->getStoragePids());
    }

    /**
     * @test
     */
    public function canSetNewRecordsPid()
    {
        $newPid = 100;

        $this->subject->setNewRecordsPid($newPid);

        $this->assertEquals($newPid, $this->subject->getNewRecordsPid());
    }

    /**
     * @test
     */
    public function canSetImporter()
    {
        $importer = $this->createMock(Importer::class);

        $this->subject->setImporter($importer);

        $this->assertSame($importer, $this->subject->getImporter());
    }

    /**
     * @test
     */
    public function canSetSource()
    {
        $source = $this->createMock(SourceInterface::class);

        $this->subject->setSource($source);

        $this->assertSame($source, $this->subject->getSource());
    }

    /**
     * @test
     */
    public function setDataWillSetCustomData()
    {
        $key = 'test';
        $data = ['some', 'data'];

        $this->subject->setData($key, $data);

        $this->assertEquals($data, $this->subject->getData($key));
    }

    /**
     * @test
     */
    public function setDataThrowExceptionIfOverrideNotAllowedAndAlreadySet()
    {
        $key = 'test';
        $data = ['some', 'data'];

        $this->expectException(ContextDataAlreadyExistException::class);
        $this->subject->setData($key, $data, false);
        $this->subject->setData($key, $data, false);
    }

    /**
     * @test
     */
    public function setCurrentImportInfoSetDataToContext()
    {
        $source = $this->createMock(SourceInterface::class);
        $sourceName = 'testSource';

        $importer = $this->createMock(Importer::class);
        $importerName = 'testImporter';

        $this->subject->setCurrentImportInfo($sourceName, $source, $importerName, $importer);

        $this->assertEquals($sourceName, $this->subject->getSourceName());
        $this->assertEquals($importerName, $this->subject->getImporterName());

        $this->assertSame($source, $this->subject->getSource());
        $this->assertSame($importer, $this->subject->getImporter());
    }

    /**
     * @test
     */
    public function resetCurrentImportInfoResetData()
    {
        $source = $this->createMock(SourceInterface::class);
        $sourceName = 'testSource1';

        $importer = $this->createMock(Importer::class);
        $importerName = 'testImporter1';

        $this->subject->setData('test', 'some info');
        $this->subject->setCurrentImportInfo($sourceName, $source, $importerName, $importer);
        $this->subject->setNewRecordsPid(1);
        $this->subject->setStoragePids([12, 15]);
        $this->subject->resetCurrentImportInfo();

        $this->assertNull($this->subject->getSourceName());
        $this->assertNull($this->subject->getImporterName());

        $this->assertNull($this->subject->getSource());
        $this->assertNull($this->subject->getImporter());

        $this->assertNull($this->subject->getData('test'));

        $this->assertNull($this->subject->getNewRecordsPid());
        $this->assertNull($this->subject->getStoragePids());
    }
}
