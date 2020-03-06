<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Processors;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Exception\InvalidProcessorConfigurationException;
use Pixelant\PxaPmImporter\Processors\DateTimeProcessor;

/**
 * Class DateTimeProcessorTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Processors
 */
class DateTimeProcessorTest extends UnitTestCase
{
    /**
     * @var DateTimeProcessor
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getAccessibleMock(
            DateTimeProcessor::class,
            ['simplePropertySet'],
            [],
            '',
            false
        );
    }

    /**
     * @test
     */
    public function checkConfigurationWithoutInputFormatThrowsException()
    {
        $configuration = [
            'outputFormat' => 'Y-m-d'
        ];

        $this->subject->_set('configuration', $configuration);
        $this->expectException(InvalidProcessorConfigurationException::class);
        $this->subject->_call('checkConfiguration');
    }

    /**
     * @test
     */
    public function checkConfigurationWithoutOutputFormatThrowsException()
    {
        $configuration = [
            'inputFormat' => 'Y-m-d',
        ];

        $this->subject->_set('configuration', $configuration);
        $this->expectException(InvalidProcessorConfigurationException::class);
        $this->subject->_call('checkConfiguration');
    }

    /**
     * @test
     */
    public function processWillTryToUpdateWithExpectedValueFromIntegerProperty()
    {
        $value = '09/26/2018 02:00:32';
        $expect = '1537927232';

        $configuration = [
            'inputFormat' => 'm/d/Y h:i:s',
            'outputFormat' => 'U'
        ];


        $this->subject->_set('configuration', $configuration);

        $this->subject
            ->expects($this->once())
            ->method('simplePropertySet')
            ->with($expect);

        $this->subject->process($value);
    }

    /**
     * @test
     */
    public function processWillTryToUpdateWithExpectedValueFromStringProperty()
    {
        $value = '1538117523';
        $expect = '2018-09-28T06:52:03+00:00';

        $configuration = [
            'inputFormat' => 'U',
            'outputFormat' => 'c'
        ];


        $this->subject->_set('configuration', $configuration);

        $this->subject
            ->expects($this->once())
            ->method('simplePropertySet')
            ->with($expect);

        $this->subject->process($value);
    }

    /**
     * @test
     */
    public function processWillTryToUpdateWithExpectedValueFromIntegerPropertyWithNoTimeSet()
    {
        $value = '2000-06-10';
        $expect = '960595200';

        $configuration = [
            'inputFormat' => 'Y-m-d',
            'outputFormat' => 'U'
        ];

        $this->subject->_set('configuration', $configuration);

        $this->subject
            ->expects($this->once())
            ->method('simplePropertySet')
            ->with($expect);

        $this->subject->process($value);
    }

    /**
     * @test
     */
    public function processWillTryToUpdateWithExpectedValueFromStringPropertyWithNoTimeSet()
    {
        $value = '2000-06-10';
        $expect = '2000-06-10T00:00:00+00:00';

        $configuration = [
            'inputFormat' => 'Y-m-d',
            'outputFormat' => 'c'
        ];


        $this->subject->_set('configuration', $configuration);

        $this->subject
            ->expects($this->once())
            ->method('simplePropertySet')
            ->with($expect);

        $this->subject->process($value);
    }
}
