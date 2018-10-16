<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Service\Importer;

use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pixelant\PxaPmImporter\Exception\MissingPropertyMappingException;
use Pixelant\PxaPmImporter\Processors\FieldProcessorInterface;
use Pixelant\PxaPmImporter\Service\Importer\AbstractImporter;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Log\Logger;

/**
 * Class AbstractImporterTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Service\Importer
 */
class AbstractImporterTest extends UnitTestCase
{
    /**
     * @var AbstractImporter|MockObject|AccessibleMockObjectInterface
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getAccessibleMock(
            AbstractImporter::class,
            ['emitSignal', 'initDbTableName', 'initModelName', 'initRepository', 'preImport', 'postImport', 'getRecordByImportIdHash', 'getDataHandler'],
            [],
            '',
            false
        );

        $logger = $this->createMock(Logger::class);
        $this->subject->_set('logger', $logger);
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->subject);
    }

    /**
     * @test
     */
    public function getPidReturnPid()
    {
        $this->subject->_set('pid', 12);

        $this->assertEquals(12, $this->subject->getPid());
    }

    /**
     * @test
     */
    public function determinateIdentifierFieldThrowsExceptionIfIdentifierConfigurationMissing()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->subject->_call('determinateIdentifierField', []);
    }

    /**
     * @test
     */
    public function identifierFieldIsSetFromConfiguration()
    {
        $conf = ['identifierField' => 'id'];

        $this->subject->_call('determinateIdentifierField', $conf);

        $this->assertEquals('id', $this->subject->_get('identifier'));
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

        $this->subject->_call('setMapping', []);
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

        $this->subject->_call('setMapping', $configuration);
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
    public function getFieldMappingForMissingFieldThrowsException()
    {
        $mapping = [
            'name' => [

            ]
        ];
        $this->subject->_set('mapping', $mapping);

        $this->expectException(MissingPropertyMappingException::class);
        $this->subject->_call('getFieldMapping', 'none');
    }

    /**
     * @test
     */
    public function getFieldMappingReturnMappingField()
    {
        $mapping = [
            'name' => [
                'property' => 'name'
            ]
        ];
        $expect = ['property' => 'name'];
        $this->subject->_set('mapping', $mapping);

        $this->assertEquals($expect, $this->subject->_call('getFieldMapping', 'name'));
    }

    /**
     * @test
     */
    public function createNewEmptyRecordWithWrongDefaultFieldsThrowsException()
    {
        $defaultFields = [
            'values' => [
                123,
                'test'
            ],
            'types' => [
                \PDO::PARAM_INT
            ]
        ];

        $this->subject->_set('defaultNewRecordFields', $defaultFields);

        $this->expectException(\UnexpectedValueException::class);
        $this->subject->_call('createNewEmptyRecord', '', '', 0);
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
            ->method('getRecordByImportIdHash')
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
            ->method('getRecordByImportIdHash')
            ->with($hash, 0)
            ->willReturn(['uid' => 12]);

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
            ->method('getRecordByImportIdHash')
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
    public function postponeProcessorWillAddProcessorInQueue()
    {
        $this->subject->_set('postponedProcessors', []);
        $processorInstance = $this->createMock(FieldProcessorInterface::class);

        $expect = [
            [
                'value' => 'test',
                'processorInstance' => $processorInstance
            ]
        ];

        $this->subject->_call('postponeProcessor', $processorInstance, 'test');
        $this->assertEquals($expect, $this->subject->_get('postponedProcessors'));
    }

    /**
     * @test
     */
    public function checkForDuplicatedIdentifiersThrowsExceptionIfDuplicationsFound()
    {
        $this->subject->_set('identifier', 'id');
        $data = [
            [
                'id' => 'test',
                'name' => 'bla'
            ],
            [
                'id' => 'duplicated',
                'name' => '321123'
            ],
            [
                'id' => 'duplicated',
                'name' => 'test123'
            ]
        ];

        $this->expectException(\RuntimeException::class);
        $this->subject->_call('checkForDuplicatedIdentifiers', $data);
    }

    /**
     * @test
     */
    public function setSettingWillSetSettingsFromConfigurationArray()
    {
        $settings = [
            'testing' => [
                'key' => 'value'
            ]
        ];
        $configuration['settings'] = $settings;

        $this->subject->_call('setSettings', $configuration);

        $this->assertEquals($settings, $this->subject->_get('settings'));
    }
}
