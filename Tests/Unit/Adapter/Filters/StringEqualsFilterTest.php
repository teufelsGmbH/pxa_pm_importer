<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Processors;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Adapter\Filters\StringEqualsFilter;
use Pixelant\PxaPmImporter\Exception\InvalidAdapterFilterColumn;
use Pixelant\PxaPmImporter\Exception\InvalidAdapterFilterValue;

/**
 * Class FloatProcessorTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Processors
 */
class StringEqualsFilterTest extends UnitTestCase
{
    /**
     * @var StringEqualsFilter
     */
    protected $subject = null;

    protected $rowData = [
        'id' => '19101910',
        'name' => 'Some product to import',
        'alias' => 'twostars',
        'category' => 'Use online',
        'some_value' => '0'
    ];

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new StringEqualsFilter();
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->subject);
    }

    /**
     * @test
     */
    public function stringEqualsFilterWillReturnTrueOnMatch()
    {
        $column = 'category';
        $configuration = [
            'value' => 'Use online'
        ];

        $this->assertTrue($this->subject->includeRow(
            $column,
            'dummy_key',
            $this->rowData,
            $configuration
        ));
    }

    /**
     * @test
     */
    public function stringEqualsFilterNonTrimmedWillBeTrimmedAndReturnTrueOnMatch()
    {
        $column = 'category';
        $configuration = [
            'value' => 'use online '
        ];

        $this->assertTrue($this->subject->includeRow(
            $column,
            'dummy_key',
            $this->rowData,
            $configuration
        ));
    }

    /**
     * @test
     */
    public function stringEqualsFilterIsCaseInsenitiveAndReturnTrueOnMatch()
    {
        $column = 'category';
        $configuration = [
            'value' => 'Use online'
        ];

        $this->assertTrue($this->subject->includeRow(
            $column,
            'dummy_key',
            $this->rowData,
            $configuration
        ));
    }

    /**
     * @test
     */
    public function stringEqualsFilterIntegerStringReturnTrueOnMatch()
    {
        $column = 'some_value';
        $configuration = [
            'value' => '0'
        ];

        $this->assertTrue($this->subject->includeRow(
            $column,
            'dummy_key',
            $this->rowData,
            $configuration
        ));
    }

    /**
     * @test
     */
    public function stringEqualsFilterWillReturnFalseOnNoMatch()
    {
        $column = 'category';
        $configuration = [
            'value' => 'NonExistText'
        ];

        $this->assertFalse($this->subject->includeRow(
            $column,
            'dummy_key',
            $this->rowData,
            $configuration
        ));
    }

    /**
     * @test
     */
    public function stringEqualsFilterWillThrowExceptionWhenColumnDoesntExistInRowData()
    {
        $column = 'NONEXISTINGCOLUMN';
        $configuration = [
            'value' => 'NonExistText'
        ];

        $this->expectException(InvalidAdapterFilterColumn::class);
        $this->subject->includeRow($column, 'dummy_key', $this->rowData, $configuration);
    }

    /**
     * @test
     */
    public function stringEqualsFilterWillThrowExceptionWhenValueIsMissing()
    {
        $column = 'category';
        $configuration = [];

        $this->expectException(InvalidAdapterFilterValue::class);
        $this->subject->includeRow($column, 'dummy_key', $this->rowData, $configuration);
    }
}
