<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Processors;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Processors\FloatProcessor;

/**
 * Class FloatProcessorTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Processors
 */
class FloatProcessorTest extends UnitTestCase
{
    /**
     * @var FloatProcessor
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new FloatProcessor();
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->subject);
    }

    /**
     * @test
     */
    public function preProcessWillFormatStringForFloating()
    {
        $value = ' 12,36 ';
        $expect = '12.36';

        $this->subject->preProcess($value);
        $this->assertEquals($expect, $value);
    }

    /**
     * @test
     */
    public function notNumericValuesWillNotPassValidation()
    {
        $value = '12test';

        $this->assertFalse($this->subject->isValid($value));
    }

    /**
     * @test
     */
    public function numericValueWillPassValidation()
    {
        $value = '12';

        $this->assertTrue($this->subject->isValid($value));
    }
}
