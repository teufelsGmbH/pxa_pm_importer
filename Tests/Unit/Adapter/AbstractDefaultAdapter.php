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
            ['adaptSourceData']
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
    public function getFieldDataReturnRowColumnData()
    {
        $row = [0 => 'test', 1 => 'data'];
        $expect = 'data';

        $this->assertEquals($expect, $this->subject->_call('getFieldData', 1, $row));
    }
}
