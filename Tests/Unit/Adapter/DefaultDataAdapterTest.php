<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Adapter;

use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pixelant\PxaPmImporter\Adapter\AbstractDefaultAdapter;
use Pixelant\PxaPmImporter\Adapter\DefaultDataAdapter;
use Pixelant\PxaPmImporter\Exception\InvalidAdapterFieldMapping;
use Pixelant\PxaPmImporter\Service\Source\SourceInterface;

/**
 * Class AbstractDefaultAdapterTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Adapter
 */
class DefaultDataAdapterTest extends UnitTestCase
{
    /**
     * @var AbstractDefaultAdapter|MockObject|AccessibleMockObjectInterface
     */
    protected $subject = null;

    protected function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            DefaultDataAdapter::class,
            ['dummy']
        );
    }

    protected function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function countAmountOfItemsReturnLanguagesMultipleBySourceItems()
    {
        $data = [
            0 => [
                'test' => 'test'
            ],
            1 => [
                'test' => 'test'
            ]
        ];
        $this->subject->_set('languagesMapping', $data);

        $source = $this->createPartialMock(SourceInterface::class, ['count', 'initialize', 'current', 'next', 'key', 'rewind', 'valid']);
        $source
            ->expects($this->once())
            ->method('count')
            ->willReturn(5);

        $this->assertEquals(2 * 5, $this->subject->countAmountOfItems($source));
    }

    /**
     * Get languages return language UIDS array from data
     *
     * @test
     */
    public function getImportLanguagesReturnLanguagesUids()
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

        $this->assertEquals($expect, $this->subject->getImportLanguages());
    }

    /**
     * @test
     */
    public function adaptRowForNonExistingLanguageThrowsException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->subject->adaptRow(0, [], 111);
    }

    /**
     * @test
     */
    public function initializeWithoutMappingConfigurationThrowsException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1536050678725);
        $this->subject->initialize([]);
    }

    /**
     * @test
     */
    public function initializeWithMissingMappingIdConfigurationThrowsException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1536050717594);
        $this->subject->initialize(['mapping' => ['noId' => false]]);
    }

    /**
     * @test
     */
    public function initializeWithEmptyLanguageConfigurationThrowsException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1536050795179);

        $configuration = [
            'mapping' => [
                'id' => '1'
            ]
        ];
        $this->subject->initialize($configuration);
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
        $this->subject->initialize($configuration);

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
        $this->subject->initialize($configuration);

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
        $this->subject->initialize($configuration);

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
        $this->subject->initialize($configuration);
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
        $this->subject->initialize($configuration);
    }

    /**
     * @test
     */
    public function initializeWithArrayIdConfigurationAndExcelColumnsConvertArrayToNumbers()
    {
        $identifiers = [
            0 => 'A',
            1 => 'G'
        ];
        $expect = [
            0,
            6
        ];
        $configuration = [
            'mapping' => [
                'excelColumns' => true,
                'id' => $identifiers,
                'languages' => [0 => []]
            ]
        ];

        $this->subject->initialize($configuration);

        $this->assertEquals($expect, $this->subject->_get('identifier'));
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
        $this->subject->initialize($configuration);
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
        $this->subject->initialize($configuration);

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
        $this->subject->initialize($configuration);

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
    public function getFieldDataForArrayColumnMappingCallgetMultipleFieldData()
    {
        $row = [0 => 'test', 1 => 'data'];
        $expect = 'data';

        $subject = $this->getMockBuilder(DefaultDataAdapter::class)
            ->setMethods(['getMultipleFieldData'])
            ->disableOriginalConstructor()
            ->getMock();

        $subject
            ->expects($this->once())
            ->method('getMultipleFieldData');

        $this->callInaccessibleMethod($subject, 'getFieldData', [0, 1], $row);
    }

    /**
     * @test
     */
    public function getMultipleFieldDataReturnRowColumnData()
    {
        $row = [0 => 'test', 1 => 'data', 2 => 'multiple'];
        $expect = 'testdatamultiple';

        $this->assertEquals($expect, $this->subject->_call('getMultipleFieldData', [0, 1, 2], $row));
    }

    /**
     * @test
     */
    public function getMultipleFieldWithNonExistingColumnDataWillThrowException()
    {
        $row = [0 => 'test', 1 => 'data', 2 => 'multiple'];

        $this->expectException(InvalidAdapterFieldMapping::class);
        $this->expectExceptionCode(1536051927592);
        $this->subject->_call('getMultipleFieldData', [0, 1, 4], $row);
    }

    /**
     * @test
     * @dataProvider adaptRowDataProvider
     */
    public function adaptRowWillAdaptDataAccordingToMapping($row, $expect, $language)
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

        $this->subject->initialize($configuration);
        $this->assertEquals($expect, $this->subject->adaptRow(0, $row, $language));
    }

    /**
     * @test
     * @dataProvider includeRowDataProvider
     */
    public function includeRowIncludeRowThatMuchFilter($row, $expect)
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

        $this->subject->initialize($configuration);
        $this->assertEquals($expect, $this->subject->includeRow('dummy_key', $row));
    }

    /**
     * @test
     * @dataProvider includeRowMultipleFilterDataProvider
     */
    public function includeRowIncludeRowThatMuchMultipleFilters($row, $expect)
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

        $this->subject->initialize($configuration);
        $this->assertEquals($expect, $this->subject->includeRow('dummy_key', $row));
    }

    /**
     * @test
     */
    public function includeRowWillThrowExceptionOnInvalidClass()
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

        $row = [
            'ITEMID' => 'ID1',
            'UA_TITLE' => 'UA Title 1',
            'SE_TITLE' => 'SE Title 1',
            'UA_DESC' => 'UA Description 1',
            'SE_DESC' => 'SE Description 1',
            'AREA' => 'A',
            'REGION' => 'A'
        ];

        $this->subject->initialize($configuration);
        $this->expectException(\Error::class);
        $this->subject->includeRow('dummy_key', $row);
    }

    /**
     * @test
     */
    public function includeRowWillThrowExceptionOnNonFilterInterface()
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
                    'filter' => 'stdClass',
                    'value' => 'A'
                ]
            ]
        ];

        $row = [
            'ITEMID' => 'ID1',
            'UA_TITLE' => 'UA Title 1',
            'SE_TITLE' => 'SE Title 1',
            'UA_DESC' => 'UA Description 1',
            'SE_DESC' => 'SE Description 1',
            'AREA' => 'A',
            'REGION' => 'A'
        ];

        $this->subject->initialize($configuration);
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1538142318);
        $this->subject->includeRow('dummy_key', $row);
    }

    /**
     * @test
     * @dataProvider adaptRowMultipleColumnIdDataProvider
     */
    public function adaptRowWithMultipleColumnIdWillReturnCorrectData($row, $expect, $language)
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


        $this->subject->initialize($configuration);
        $this->assertEquals($expect, $this->subject->adaptRow(0, $row, $language));
    }

    /**
     * @return array
     */
    public function adaptRowMultipleColumnIdDataProvider()
    {
        $row1 = [
            'ITEMID' => 'ID1',
            'UA_TITLE' => 'UA Title 1',
            'SE_TITLE' => 'SE Title 1',
            'UA_DESC' => 'UA Description 1',
            'SE_DESC' => 'SE Description 1',
            'AREA' => 'A',
            'REGION' => 'A'
        ];
        $row2 = [
            'ITEMID' => 'ID2',
            'UA_TITLE' => 'UA Title 2',
            'SE_TITLE' => 'SE Title 2',
            'UA_DESC' => 'UA Description 2',
            'SE_DESC' => 'SE Description 2',
            'AREA' => 'A',
            'REGION' => 'B'
        ];
        return [
            [
                'row' => $row1,
                'expect' => [
                    'id' => 'ID1AA',
                    'title' => 'SE Title 1',
                    'description' => 'SE Description 1',
                    'area' => 'A',
                    'region' => 'A',
                ],
                'language' => 0
            ],
            [
                'row' => $row1,
                'expect' => [
                    'id' => 'ID1AA',
                    'title' => 'UA Title 1',
                    'description' => 'UA Description 1',
                    'area' => 'A',
                    'region' => 'A',
                ],
                'language' => 1
            ],
            [
                'row' => $row2,
                'expect' => [
                    'id' => 'ID2AB',
                    'title' => 'SE Title 2',
                    'description' => 'SE Description 2',
                    'area' => 'A',
                    'region' => 'B',
                ],
                'language' => 0
            ],
            [
                'row' => $row2,
                'expect' => [
                    'id' => 'ID2AB',
                    'title' => 'UA Title 2',
                    'description' => 'UA Description 2',
                    'area' => 'A',
                    'region' => 'B',
                ],
                'language' => 1
            ]
        ];
    }

    /**
     * @return array
     */
    public function includeRowMultipleFilterDataProvider()
    {
        return [
            [
                [
                    'ITEMID' => 'ID1',
                    'UA_TITLE' => 'UA Title 1',
                    'SE_TITLE' => 'SE Title 1',
                    'UA_DESC' => 'UA Description 1',
                    'SE_DESC' => 'SE Description 1',
                    'AREA' => 'A',
                    'REGION_AREA' => 'A'
                ],
                true
            ],
            [
                [
                    'ITEMID' => 'ID2',
                    'UA_TITLE' => 'UA Title 2',
                    'SE_TITLE' => 'SE Title 2',
                    'UA_DESC' => 'UA Description 2',
                    'SE_DESC' => 'SE Description 2',
                    'AREA' => 'A',
                    'REGION_AREA' => 'B'
                ],
                false
            ],
            [
                [
                    'ITEMID' => 'ID3',
                    'UA_TITLE' => 'UA Title 3',
                    'SE_TITLE' => 'SE Title 3',
                    'UA_DESC' => 'UA Description 3',
                    'SE_DESC' => 'SE Description 3',
                    'AREA' => 'B',
                    'REGION_AREA' => 'B'
                ],
                false
            ],
            [
                ['ITEMID' => 'ID4',
                    'UA_TITLE' => 'UA Title 4',
                    'SE_TITLE' => 'SE Title 4',
                    'UA_DESC' => 'UA Description 4',
                    'SE_DESC' => 'SE Description 4',
                    'AREA' => 'B',
                    'REGION_AREA' => 'A'
                ],
                false
            ],
            [
                [
                    'ITEMID' => 'ID5',
                    'UA_TITLE' => 'UA Title 5',
                    'SE_TITLE' => 'SE Title 5',
                    'UA_DESC' => 'UA Description 5',
                    'SE_DESC' => 'SE Description 5',
                    'AREA' => 'A',
                    'REGION_AREA' => 'A'
                ],
                true
            ]
        ];
    }

    /**
     * @return array
     */
    public function includeRowDataProvider()
    {
        return [
            [
                'row' => [
                    0 => 'English title 1',
                    1 => 'English desc 1',
                    2 => 'Ukrainian title 1',
                    3 => 'Ukrainian desc 1',
                    4 => 'id1',
                    5 => 'se'
                ],
                'expect' => true
            ],
            [
                'row' => [
                    0 => 'English title 4',
                    1 => 'English desc 4',
                    2 => 'Ukrainian title 4',
                    3 => 'Ukrainian desc 4',
                    4 => 'id4',
                    5 => 'se'
                ],
                'expect' => true
            ],
            [
                'row' => [
                    0 => 'English title 3',
                    1 => 'English desc 3',
                    2 => 'Ukrainian title 3',
                    3 => 'Ukrainian desc 3',
                    4 => 'id3',
                    5 => 'ua'
                ],
                'expect' => false
            ]
        ];
    }

    /**
     * @return array
     */
    public function adaptRowDataProvider()
    {
        return [
            [
                'row' => [
                    0 => 'English title 1',
                    1 => 'English desc 1',
                    2 => 'Ukrainian title 1',
                    3 => 'Ukrainian desc 1',
                    4 => 'id1'
                ],
                'expect' => [
                    'title' => 'English title 1',
                    'description' => 'English desc 1',
                    'id' => 'id1'
                ],
                'language' => 0
            ],
            [
                'row' => [
                    0 => 'English title 2',
                    1 => 'English desc 2',
                    2 => 'Ukrainian title 2',
                    3 => 'Ukrainian desc 2',
                    4 => 'id2'
                ],
                'expect' => [
                    'title' => 'Ukrainian title 2',
                    'description' => 'Ukrainian desc 2',
                    'id' => 'id2'
                ],
                'language' => 1
            ],
        ];
    }
}
