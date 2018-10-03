<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Adapter;

use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pixelant\PxaPmImporter\Adapter\AbstractDefaultAdapter;
use Pixelant\PxaPmImporter\Exception\InvalidAdapterFieldMapping;

/**
 * Class AbstractDefaultAdapterTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Adapter
 */
class AbstractDefaultAdapterTest extends UnitTestCase
{
    /**
     * @var AbstractDefaultAdapter|MockObject|AccessibleMockObjectInterface
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getAccessibleMock(
            AbstractDefaultAdapter::class,
            ['transformSourceData']
        );
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->subject);
    }

    /**
     * @test
     */
    public function getDataReturnData()
    {
        $data = ['test' => 'testdata'];

        $this->subject->_set('data', $data);

        $this->assertEquals($data, $this->subject->getData());
    }

    /**
     * Get languages return language UIDS array from data
     *
     * @test
     */
    public function getLanguagesReturnLanguagesUids()
    {
        $data = [
            0 => [
                'test' => 'test'
            ],
            1 => [
                'test' => 'test'
            ],
            2 => [
                'test' => 'test'
            ]
        ];
        $this->subject->_set('languagesMapping', $data);
        $expect = array_keys($data);

        $this->assertEquals($expect, $this->subject->getLanguages());
    }

    /**
     * @test
     */
    public function getLanguageDataReturnDataForLanguage()
    {
        $data = [
            0 => [
                'test' => 'test'
            ],
            1 => [
                'test' => 'test'
            ],
            2 => [
                'test' => 'test',
                'blabla' => 'testdata'
            ]
        ];
        $expect = ['test' => 'test', 'blabla' => 'testdata'];

        $this->subject->_set('data', $data);

        $this->assertEquals($expect, $this->subject->getLanguageData(2));
    }

    /**
     * @test
     */
    public function getLanguageDataForNonExistingLanguageThrowsException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->subject->getLanguageData(111);
    }

    /**
     * @test
     */
    public function initializeWithoutMappingConfigurationThrowsException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1536050678725);
        $this->subject->_call('initialize', []);
    }

    /**
     * @test
     */
    public function initializeWithMissingMappingIdConfigurationThrowsException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1536050717594);
        $this->subject->_call('initialize', ['mapping' => ['noId' => false]]);
    }

    /**
     * @test
     */
    public function initalizeWithEmptyLanguageConfigurationThrowsException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1536050795179);

        $configuration = [
            'mapping' => [
                'id' => '1'
            ]
        ];
        $this->subject->_call('initialize', $configuration);
    }

    /**
     * @test
     */
    public function initializeWithNumericIdConfigurationConvertToInt()
    {
        $configuration = [
            'mapping' => [
                'id' => '12',
                'languages' => [0 => []]
            ]
        ];
        $this->subject->_call('initialize', $configuration);

        $this->assertEquals(12, $this->subject->_get('identifier'));
        $this->assertTrue(is_int($this->subject->_get('identifier')));
    }

    /**
     * @test
     */
    public function initializeWithExcelColumnsIdConfigurationConvertToInt()
    {
        $configuration = [
            'mapping' => [
                'id' => 'BZ',
                'excelColumns' => true,
                'languages' => [0 => []]
            ]
        ];
        $this->subject->_call('initialize', $configuration);

        $this->assertEquals(77, $this->subject->_get('identifier'));
        $this->assertTrue(is_int($this->subject->_get('identifier')));
    }

    /**
     * @test
     */
    public function initializeWithStringIdConfigurationStaysString()
    {
        $configuration = [
            'mapping' => [
                'id' => 'ITEMID',
                'languages' => [0 => []]
            ]
        ];
        $this->subject->_call('initialize', $configuration);

        $this->assertEquals('ITEMID', $this->subject->_get('identifier'));
        $this->assertTrue(is_string($this->subject->_get('identifier')));
    }

    /**
     * @test
     */
    public function initializeWithArrayIdConfigurationStaysArray()
    {
        $identifiers = [
            0 => 'ITEMID',
            1 => 'AREAID'
        ];
        $configuration = [
            'mapping' => [
                'id' => $identifiers,
                'languages' => [0 => []]
            ]
        ];
        $this->subject->_call('initialize', $configuration);
        $this->assertEquals($identifiers, $this->subject->_get('identifier'));
        $this->assertTrue(is_array($this->subject->_get('identifier')));
    }

    /**
     * @test
     */
    public function initializeWithEmptyArrayIdConfigurationThrowsException()
    {
        $identifiers = [];
        $configuration = [
            'mapping' => [
                'id' => $identifiers,
                'languages' => [0 => []]
            ]
        ];
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1538560400221);
        $this->subject->_call('initialize', $configuration);
    }

    /**
     * @test
     */
    public function initializeWithTypeNonSupportedIdConfigurationThrowsException()
    {
        $identifier = 9.5;
        $configuration = [
            'mapping' => [
                'id' => $identifier,
                'languages' => [0 => []]
            ]
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1538560523613);
        $this->subject->_call('initialize', $configuration);
    }
    
    /**
     * @test
     */
    public function initializeWithSettingsWillSetSettings()
    {
        $settings = [
            'test' => 'setting'
        ];
        $configuration = [
            'mapping' => [
                'id' => '12',
                'languages' => [0 => []]
            ],
            'settings' => $settings
        ];
        $this->subject->_call('initialize', $configuration);

        $this->assertEquals($settings, $this->subject->_get('settings'));
    }

    /**
     * @test
     */
    public function initializeWithFiltersWillSetFilters()
    {
        $filters = [
            'columnName' => [
                'filter' => 'someFilterClass',
                'value' => 'someValue'
            ]
        ];
        $configuration = [
            'mapping' => [
                'id' => '12',
                'languages' => [0 => []]
            ],
            'filters' => $filters
        ];
        $this->subject->_call('initialize', $configuration);

        $this->assertEquals($filters, $this->subject->_get('filters'));
    }

    /**
     * @test
     */
    public function getFieldDataReturnRowColumnData()
    {
        $row = [0 => 'test', 1 => 'data'];
        $expect = 'data';

        $this->assertEquals($expect, $this->subject->_call('getFieldData', 1, $row));
    }

    /**
     * @test
     */
    public function getMultipleFieldDataReturnRowColumnData()
    {
        $row = [0 => 'test', 1 => 'data', 2 => 'multiple'];
        $expect = 'testdatamultiple';

        $this->assertEquals($expect, $this->subject->_call('getMultipleFieldData', [0,1,2], $row));
    }

    /**
     * @test
     */
    public function getMultipleFieldWithNonExistingColumnDataWillThrowException()
    {
        $row = [0 => 'test', 1 => 'data', 2 => 'multiple'];

        $this->expectException(InvalidAdapterFieldMapping::class);
        $this->expectExceptionCode(1536051927592);
        $this->subject->_call('getMultipleFieldData', [0,1,4], $row);
    }

    /**
     * @test
     */
    public function adaptSourceDataWillSetDataAccordingToMapping()
    {
        $configuration = [
            'mapping' => [
                'id' => 4,
                'languages' => [
                    0 => [
                        'title' => 0,
                        'description' => 1
                    ],
                    1 => [
                        'title' => 2,
                        'description' => 3
                    ],
                ]
            ]
        ];

        $data = [
            [
                0 => 'English title 1',
                1 => 'English desc 1',
                2 => 'Ukrainian title 1',
                3 => 'Ukrainian desc 1',
                4 => 'id1'
            ],
            [
                0 => 'English title 2',
                1 => 'English desc 2',
                2 => 'Ukrainian title 2',
                3 => 'Ukrainian desc 2',
                4 => 'id2'
            ],
        ];

        $expect = [
            0 => [
                [
                    'title' => 'English title 1',
                    'description' => 'English desc 1',
                    'id' => 'id1'
                ],
                [
                    'title' => 'English title 2',
                    'description' => 'English desc 2',
                    'id' => 'id2'
                ]
            ],
            1 => [
                [
                    'title' => 'Ukrainian title 1',
                    'description' => 'Ukrainian desc 1',
                    'id' => 'id1'
                ],
                [
                    'title' => 'Ukrainian title 2',
                    'description' => 'Ukrainian desc 2',
                    'id' => 'id2'
                ]
            ],
        ];

        $this->subject->_call('initialize', $configuration);
        $this->assertEquals($expect, $this->subject->_call('adaptData', $data));
    }

    /**
     * @test
     */
    public function adaptSourceDataWillSetDataAccordingToMappingAndFilter()
    {
        $configuration = [
            'mapping' => [
                'id' => 4,
                'languages' => [
                    0 => [
                        'title' => 0,
                        'description' => 1
                    ],
                    1 => [
                        'title' => 2,
                        'description' => 3
                    ],
                ]
            ],
            'filters' => [
                '5' => [
                    'filter' => 'Pixelant\PxaPmImporter\Adapter\Filters\StringEqualsFilter',
                    'value' => 'se'
                ]
            ]
        ];

        $data = [
            [
                0 => 'English title 1',
                1 => 'English desc 1',
                2 => 'Ukrainian title 1',
                3 => 'Ukrainian desc 1',
                4 => 'id1',
                5 => 'se'
            ],
            [
                0 => 'English title 2',
                1 => 'English desc 2',
                2 => 'Ukrainian title 2',
                3 => 'Ukrainian desc 2',
                4 => 'id2',
                5 => 'se'
            ],
            [
                0 => 'English title 3',
                1 => 'English desc 3',
                2 => 'Ukrainian title 3',
                3 => 'Ukrainian desc 3',
                4 => 'id3',
                5 => 'ua'
            ],
            [
                0 => 'English title 4',
                1 => 'English desc 4',
                2 => 'Ukrainian title 4',
                3 => 'Ukrainian desc 4',
                4 => 'id4',
                5 => 'se'
            ]
        ];

        $expect = [
            0 => [
                [
                    'title' => 'English title 1',
                    'description' => 'English desc 1',
                    'id' => 'id1'
                ],
                [
                    'title' => 'English title 2',
                    'description' => 'English desc 2',
                    'id' => 'id2'
                ],
                [
                    'title' => 'English title 4',
                    'description' => 'English desc 4',
                    'id' => 'id4'
                ]
            ],
            1 => [
                [
                    'title' => 'Ukrainian title 1',
                    'description' => 'Ukrainian desc 1',
                    'id' => 'id1'
                ],
                [
                    'title' => 'Ukrainian title 2',
                    'description' => 'Ukrainian desc 2',
                    'id' => 'id2'
                ],
                [
                    'title' => 'Ukrainian title 4',
                    'description' => 'Ukrainian desc 4',
                    'id' => 'id4'
                ]
            ],
        ];

        $this->subject->_call('initialize', $configuration);
        $this->assertEquals($expect, $this->subject->_call('adaptData', $data));
    }

    /**
     * @test
     */
    public function adaptSourceDataWillSetDataAccordingToMappingAndMultipleFilters()
    {
        $configuration = [
            'mapping' => [
                'id' => 'ITEMID',
                'languages' => [
                    0 => [
                        'title' => 'SE_TITLE',
                        'description' => 'SE_DESC',
                        'area' => 'AREA',
                        'region' => 'REGION_AREA'
                    ],
                    1 => [
                        'title' => 'UA_TITLE',
                        'description' => 'UA_DESC',
                        'area' => 'AREA',
                        'region' => 'REGION_AREA'
                    ],
                ]
            ],
            'filters' => [
                'AREA' => [
                    'filter' => 'Pixelant\PxaPmImporter\Adapter\Filters\StringEqualsFilter',
                    'value' => 'A'
                ],
                'REGION_AREA' => [
                    'filter' => 'Pixelant\PxaPmImporter\Adapter\Filters\StringEqualsFilter',
                    'value' => 'A'
                ]
            ]
        ];

        $data = [
            [
                'ITEMID' => 'ID1',
                'UA_TITLE' => 'UA Title 1',
                'SE_TITLE' => 'SE Title 1',
                'UA_DESC' => 'UA Description 1',
                'SE_DESC' => 'SE Description 1',
                'AREA' => 'A',
                'REGION_AREA' => 'A'
            ],
            [
                'ITEMID' => 'ID2',
                'UA_TITLE' => 'UA Title 2',
                'SE_TITLE' => 'SE Title 2',
                'UA_DESC' => 'UA Description 2',
                'SE_DESC' => 'SE Description 2',
                'AREA' => 'A',
                'REGION_AREA' => 'B'
            ],
            [
                'ITEMID' => 'ID3',
                'UA_TITLE' => 'UA Title 3',
                'SE_TITLE' => 'SE Title 3',
                'UA_DESC' => 'UA Description 3',
                'SE_DESC' => 'SE Description 3',
                'AREA' => 'B',
                'REGION_AREA' => 'B'
            ],
            [
                'ITEMID' => 'ID4',
                'UA_TITLE' => 'UA Title 4',
                'SE_TITLE' => 'SE Title 4',
                'UA_DESC' => 'UA Description 4',
                'SE_DESC' => 'SE Description 4',
                'AREA' => 'B',
                'REGION_AREA' => 'A'
            ],
            [
                'ITEMID' => 'ID5',
                'UA_TITLE' => 'UA Title 5',
                'SE_TITLE' => 'SE Title 5',
                'UA_DESC' => 'UA Description 5',
                'SE_DESC' => 'SE Description 5',
                'AREA' => 'A',
                'REGION_AREA' => 'A'
            ],
        ];

        $expect = [
            0 => [
                [
                    'id' => 'ID1',
                    'title' => 'SE Title 1',
                    'description' => 'SE Description 1',
                    'area' => 'A',
                    'region' => 'A',
                ],
                [
                    'id' => 'ID5',
                    'title' => 'SE Title 5',
                    'description' => 'SE Description 5',
                    'area' => 'A',
                    'region' => 'A',
                ]
            ],
            1 => [
                [
                    'id' => 'ID1',
                    'title' => 'UA Title 1',
                    'description' => 'UA Description 1',
                    'area' => 'A',
                    'region' => 'A',
                ],
                [
                    'id' => 'ID5',
                    'title' => 'UA Title 5',
                    'description' => 'UA Description 5',
                    'area' => 'A',
                    'region' => 'A',
                ]
            ],
        ];

        $this->subject->_call('initialize', $configuration);
        $this->assertEquals($expect, $this->subject->_call('adaptData', $data));
    }

    /**
     * @test
     */
    public function adaptSourceDataWillThrowExceptionOnInvalidClass()
    {
        $configuration = [
            'mapping' => [
                'id' => 'ITEMID',
                'languages' => [
                    0 => [
                        'title' => 'SE_TITLE',
                        'description' => 'SE_DESC',
                        'area' => 'AREA',
                        'region' => 'REGION'
                    ],
                    1 => [
                        'title' => 'UA_TITLE',
                        'description' => 'UA_DESC',
                        'area' => 'AREA',
                        'region' => 'REGION'
                    ],
                ]
            ],
            'filters' => [
                'AREA' => [
                    'filter' => 'Pixelant\PxaPmImporter\Adapter\Filters\InvalidFilter',
                    'value' => 'A'
                ]
            ]
        ];

        $data = [
            [
                'ITEMID' => 'ID1',
                'UA_TITLE' => 'UA Title 1',
                'SE_TITLE' => 'SE Title 1',
                'UA_DESC' => 'UA Description 1',
                'SE_DESC' => 'SE Description 1',
                'AREA' => 'A',
                'REGION' => 'A'
            ],
            [
                'ITEMID' => 'ID2',
                'UA_TITLE' => 'UA Title 2',
                'SE_TITLE' => 'SE Title 2',
                'UA_DESC' => 'UA Description 2',
                'SE_DESC' => 'SE Description 2',
                'AREA' => 'A',
                'REGION' => 'B'
            ]
        ];

        $this->subject->_call('initialize', $configuration);
        $this->expectException(\Error::class);
        $this->subject->_call('adaptData', $data);
    }

    /**
     * @test
     */
    public function adaptSourceDataWillThrowExceptionOnNonFilterInterface()
    {
        $configuration = [
            'mapping' => [
                'id' => 'ITEMID',
                'languages' => [
                    0 => [
                        'title' => 'SE_TITLE',
                        'description' => 'SE_DESC',
                        'area' => 'AREA',
                        'region' => 'REGION'
                    ],
                    1 => [
                        'title' => 'UA_TITLE',
                        'description' => 'UA_DESC',
                        'area' => 'AREA',
                        'region' => 'REGION'
                    ],
                ]
            ],
            'filters' => [
                'AREA' => [
                    'filter' => 'Pixelant\PxaPmImporter\Processors\IntegerProcessor',
                    'value' => 'A'
                ]
            ]
        ];

        $data = [
            [
                'ITEMID' => 'ID1',
                'UA_TITLE' => 'UA Title 1',
                'SE_TITLE' => 'SE Title 1',
                'UA_DESC' => 'UA Description 1',
                'SE_DESC' => 'SE Description 1',
                'AREA' => 'A',
                'REGION' => 'A'
            ],
            [
                'ITEMID' => 'ID2',
                'UA_TITLE' => 'UA Title 2',
                'SE_TITLE' => 'SE Title 2',
                'UA_DESC' => 'UA Description 2',
                'SE_DESC' => 'SE Description 2',
                'AREA' => 'A',
                'REGION' => 'B'
            ]
        ];

        $this->subject->_call('initialize', $configuration);
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1538142318);
        $this->subject->_call('adaptData', $data);
    }

    /**
     * @test
     */
    public function adaptSourceDataWithMultipleColumnIdWillReturnCorrectData()
    {
        $configuration = [
            'mapping' => [
                'id' => [
                    0 => 'ITEMID',
                    1 => 'AREA',
                    2 => 'REGION'
                ],
                'languages' => [
                    0 => [
                        'title' => 'SE_TITLE',
                        'description' => 'SE_DESC',
                        'area' => 'AREA',
                        'region' => 'REGION'
                    ],
                    1 => [
                        'title' => 'UA_TITLE',
                        'description' => 'UA_DESC',
                        'area' => 'AREA',
                        'region' => 'REGION'
                    ],
                ]
            ]
        ];

        $data = [
            [
                'ITEMID' => 'ID1',
                'UA_TITLE' => 'UA Title 1',
                'SE_TITLE' => 'SE Title 1',
                'UA_DESC' => 'UA Description 1',
                'SE_DESC' => 'SE Description 1',
                'AREA' => 'A',
                'REGION' => 'A'
            ],
            [
                'ITEMID' => 'ID2',
                'UA_TITLE' => 'UA Title 2',
                'SE_TITLE' => 'SE Title 2',
                'UA_DESC' => 'UA Description 2',
                'SE_DESC' => 'SE Description 2',
                'AREA' => 'A',
                'REGION' => 'B'
            ]
        ];

        $expect = [
            0 => [
                [
                    'id' => 'ID1AA',
                    'title' => 'SE Title 1',
                    'description' => 'SE Description 1',
                    'area' => 'A',
                    'region' => 'A',
                ],
                [
                    'id' => 'ID2AB',
                    'title' => 'SE Title 2',
                    'description' => 'SE Description 2',
                    'area' => 'A',
                    'region' => 'B',
                ]
            ],
            1 => [
                [
                    'id' => 'ID1AA',
                    'title' => 'UA Title 1',
                    'description' => 'UA Description 1',
                    'area' => 'A',
                    'region' => 'A',
                ],
                [
                    'id' => 'ID2AB',
                    'title' => 'UA Title 2',
                    'description' => 'UA Description 2',
                    'area' => 'A',
                    'region' => 'B',
                ]
            ],
        ];

        $this->subject->_call('initialize', $configuration);
        $this->assertEquals($expect, $this->subject->_call('adaptData', $data));
    }


}
