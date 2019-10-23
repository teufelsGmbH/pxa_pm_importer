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
        $this->subject = $this
            ->getMockBuilder(FloatProcessor::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
    }

    protected function tearDown()
    {
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
}
