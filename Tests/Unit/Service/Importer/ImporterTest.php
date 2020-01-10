<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Service\Importer;

use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pixelant\PxaPmImporter\Context\ImportContext;
use Pixelant\PxaPmImporter\Exception\MissingImportField;
use Pixelant\PxaPmImporter\Processors\FieldProcessorInterface;
use Pixelant\PxaPmImporter\Service\Importer\Importer;
use Pixelant\PxaPmImporter\Service\Source\CsvSource;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Log\Logger;

/**
 * Class AbstractImporterTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Service\Importer
 */
class ImporterTest extends UnitTestCase
{
    /**
     * @var Importer|MockObject|AccessibleMockObjectInterface
     */
    protected $subject = null;

    protected function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            Importer::class,
            ['emitSignal', 'getDataHandler', 'findRecordByImportIdHash', 'createNewEmptyRecord'],
            [],
            '',
            false
        );

        $logger = $this->createMock(Logger::class);
        $this->subject->_set('logger', $logger);
    }

    protected function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function identifierFieldIsSetFromConfiguration()
    {
        $conf = ['identifierField' => 'test'];

        $this->subject->_call('determinateIdentifierField', $conf);

        $this->assertEquals('test', $this->subject->_get('identifier'));
    }

    /**
     * @test
     */
    public function initializeAdapterThrowsExceptionInCaseOfInvalidConfiguration()
    {
        $this->expectException(\RuntimeException::class);
        $this->subject->_call('initializeAdapter', []);
    }

    /**
     * @test
     */
    public function setMappingWithWrongConfigurationThrowsException()
    {
        $this->expectException(\RuntimeException::class);

        $this->subject->_call('setMappingRules', []);
    }

    /**
     * @test
     */
    public function setMappingWillCreateMappingArray()
    {
        $configuration = [
            'mapping' => [
                'name' => [
                    'processor' => 'TestProcessor'
                ],
                'field' => [
                    'property' => 'customPropertyName',
                    'processor' => 'MegaProcessor'
                ],
                'fieldWithConfiguration' => [
                    'property' => 'customPropertyName',
                    'processor' => 'MegaProcessor',
                    'validation' => 'test,blabla',
                    'conf1' => '123'
                ],
                'empty' => []
            ]
        ];

        $expect = [
            'name' => [
                'property' => 'name',
                'processor' => 'TestProcessor',
                'configuration' => []
            ],
            'field' => [
                'property' => 'customPropertyName',
                'processor' => 'MegaProcessor',
                'configuration' => []
            ],
            'fieldWithConfiguration' => [
                'property' => 'customPropertyName',
                'processor' => 'MegaProcessor',
                'configuration' => [
                    'validation' => 'test,blabla',
                    'conf1' => '123'
                ]
            ],
            'empty' => [
                'property' => 'empty',
                'processor' => false,
                'configuration' => []
            ]
        ];

        $this->subject->_call('setMappingRules', $configuration);
        $this->assertEquals($expect, $this->subject->_get('mapping'));
    }

    /**
     * @test
     */
    public function getImportIdFromRowWhereItIsNotSetThrowsException()
    {
        $this->subject->_set('identifier', 'id');
        $row = [
            'name' => 'test'
        ];

        $this->expectException(\RuntimeException::class);
        $this->subject->_call('getImportIdFromRow', $row);
    }

    /**
     * @test
     */
    public function getImportIdFromRowReturnImportId()
    {
        $this->subject->_set('identifier', 'id');
        $row = [
            'id' => 'sku123',
            'name' => 'test'
        ];

        $this->assertEquals('sku123', $this->subject->_call('getImportIdFromRow', $row));
    }

    /**
     * @test
     */
    public function getImportIdFromRowReturnImportIdRespectCamelCase()
    {
        $this->subject->_set('identifier', 'id');
        $row = [
            'id' => 'sku123loweCamelCase',
            'name' => 'test'
        ];

        $this->assertEquals('sku123loweCamelCase', $this->subject->_call('getImportIdFromRow', $row));
    }

    /**
     * @test
     */
    public function getImportIdFromRowReturnTrimmedImportId()
    {
        $this->subject->_set('identifier', 'id');
        $row = [
            'id' => '   sku123loweCamelCase ',
            'name' => 'test'
        ];

        $this->assertEquals('sku123loweCamelCase', $this->subject->_call('getImportIdFromRow', $row));
    }

    /**
     * @test
     */
    public function getFieldMappingValueForMissingFieldThrowsException()
    {
        $row = [
            'name' => [

            ]
        ];

        $this->expectException(MissingImportField::class);
        $this->subject->_call('getFieldMappingValue', 'none', $row);
    }

    /**
     * @test
     */
    public function getFieldMappingReturnMappingField()
    {
        $row = [
            'name' => 'John'
        ];
        $expect = 'John';

        $this->assertEquals($expect, $this->subject->_call('getFieldMappingValue', 'name', $row));
    }

    /**
     * @test
     */
    public function handleLocalizationIfNoDefaultFoundReturnCorrespondingStatus()
    {
        $hash = '123321';
        $language = 1;

        $this->subject
            ->expects($this->once())
            ->method('findRecordByImportIdHash')
            ->with($hash, 0)
            ->willReturn(null);

        $this->assertEquals(0, $this->subject->_call('handleLocalization', $hash, $language));
    }

    /**
     * @test
     */
    public function handleLocalizationIfLocalizationFailedReturnCorrespondingStatus()
    {
        $hash = '123321';
        $language = 1;

        $this->subject
            ->expects($this->once())
            ->method('findRecordByImportIdHash')
            ->with($hash, 0)
            ->willReturn(['uid' => 12, 'pid' => 1]);

        $dataHandler = $this->createMock(DataHandler::class);
        $dataHandler->errorLog = ['Error'];

        $this->subject
            ->expects($this->once())
            ->method('getDataHandler')
            ->willReturn($dataHandler);

        $this->assertEquals(-1, $this->subject->_call('handleLocalization', $hash, $language));
    }

    /**
     * @test
     */
    public function handleLocalizationIfLocalizationSuccessReturnCorrespondingStatus()
    {
        $hash = '123321';
        $language = 1;

        $this->subject
            ->expects($this->once())
            ->method('findRecordByImportIdHash')
            ->with($hash, 0)
            ->willReturn(['uid' => 12]);

        $dataHandler = $this->createMock(DataHandler::class);
        $dataHandler->errorLog = [];

        $this->subject
            ->expects($this->once())
            ->method('getDataHandler')
            ->willReturn($dataHandler);

        $this->assertEquals(1, $this->subject->_call('handleLocalization', $hash, $language));
    }

    /**
     * @test
     */
    public function getImportProgressReturnMaxResultIfNotAmountSet()
    {
        $this->subject->_set('amountOfImportItems', 0);

        $this->assertEquals(100.00, $this->subject->_call('getImportProgress'));
    }

    /**
     * @test
     */
    public function getImportProgressReturnCurrentImportProgress()
    {
        $this->subject->_set('amountOfImportItems', 35);
        $this->subject->_set('batchProgressCount', 5);

        $expect = round(5 / 35 * 100, 2);
        $this->assertEquals($expect, $this->subject->_call('getImportProgress'));
    }

    /**
     * @test
     */
    public function tryCreateNewRecordWillNotCreateRecordIfNotAllowed()
    {
        $this->subject->_set('allowedOperations', '');

        $this->subject
            ->expects($this->never())
            ->method('createNewEmptyRecord');

        $this->subject->_call('tryCreateNewRecord', 1, 'test', 0);
    }

    /**
     * @test
     */
    public function tryCreateNewRecordWillCreateRecordIfAllowed()
    {
        $this->subject->_set('allowedOperations', 'create');

        $this->subject
            ->expects($this->once())
            ->method('createNewEmptyRecord');

        $this->subject
            ->expects($this->once())
            ->method('findRecordByImportIdHash')
            ->willReturn([]);

        $this->subject->_call('tryCreateNewRecord', 1, 'test', 0);
    }

    /**
     * @test
     */
    public function defaultNewRecordFieldsSetFromConfiguration()
    {
        $defaultFields = ['title' => 'super title'];
        $configuration['importNewRecords']['defaultFields'] = $defaultFields;

        $this->subject->_call('determinateDefaultNewRecordFields', $configuration);

        $this->assertEquals($defaultFields, $this->subject->_get('defaultNewRecordFields'));
    }

    /**
     * @test
     */
    public function determinateAllowedOperationsSetUsingConfiguration()
    {
        $operations = 'test,test2';
        $configuration['allowedOperations'] = $operations;

        $this->subject->_call('determinateAllowedOperations', $configuration);

        $this->assertEquals($operations, $this->subject->_get('allowedOperations'));
    }

    /**
     * @test
     */
    public function initializeContextNewRecordsPidSetsStorageToContext()
    {
        $context = $this->createPartialMock(ImportContext::class, ['setNewRecordsPid']);

        $newPid = 10;
        $storage = [10, 12];

        $configuration['importNewRecords']['pid'] = $newPid;
        $this->inject($context, 'storagePids', $storage);

        $context
            ->expects($this->once())
            ->method('setNewRecordsPid')
            ->with($newPid);

        $this->subject->_set('context', $context);
        $this->subject->_call('initializeContextNewRecordsPid', $configuration);
    }

    /**
     * @test
     */
    public function initializeContextNewRecordsPidThrownExceptionIfNewPidIsNotPartOfStorage()
    {
        $context = $this->createPartialMock(ImportContext::class, ['setNewRecordsPid']);

        $newPid = 10;
        $storage = [12];

        $configuration['importNewRecords']['pid'] = $newPid;
        $this->inject($context, 'storagePids', $storage);

        $context
            ->expects($this->never())
            ->method('setNewRecordsPid')
            ->with($newPid);

        $this->expectException(\UnexpectedValueException::class);
        $this->subject->_set('context', $context);
        $this->subject->_call('initializeContextNewRecordsPid', $configuration);
    }

    /**
     * @test
     */
    public function setConfigurationSetsConfiguration()
    {
        $conf = ['test' => 'conf'];

        $this->subject->_call('setConfiguration', $conf);

        $this->assertEquals($conf, $this->subject->_get('configuration'));
    }

    /**
     * @test
     */
    public function setSourceSetSource()
    {
        $source = $this->createMock(CsvSource::class);

        $this->subject->_call('setSource', $source);

        $this->assertSame($source, $this->subject->_get('source'));
    }
}
