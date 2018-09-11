<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Processors;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Processors\IntegerProcessor;

/**
 * Class FloatProcessorTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Processors
 */
class IntegerProcessorTest extends UnitTestCase
{
    /**
     * @var IntegerProcessor
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new IntegerProcessor();
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->subject);
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
