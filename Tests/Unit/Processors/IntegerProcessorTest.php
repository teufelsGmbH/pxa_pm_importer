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
        $this->subject = $this
            ->getMockBuilder(IntegerProcessor::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function preProcessWillForceInt()
    {
        $value = '14';
        $expect = (int)$value;

        $this->subject->preProcess($value);

        $this->assertEquals($expect, $value);
    }
}
