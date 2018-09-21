<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Adapter;

use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pixelant\PxaPmImporter\Adapter\AbstractDefaultAdapter;

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
    public function getFieldDataReturnRowColumnData()
    {
        $row = [0 => 'test', 1 => 'data'];
        $expect = 'data';

        $this->assertEquals($expect, $this->subject->_call('getFieldData', 1, $row));
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
}
