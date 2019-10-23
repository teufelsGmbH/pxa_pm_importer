<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Domain\Validation\Validator;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Domain\Validation\Validator\AbstractProcessorFieldValueValidator;
use Pixelant\PxaPmImporter\Domain\Validation\Validator\ProcessorFieldValueValidatorInterface;

/**
 * Class AbstractProcessorFieldValueValidatorTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Domain\Validation\Validator
 */
class AbstractProcessorFieldValueValidatorTest extends UnitTestCase
{
    /**
     * @var AbstractProcessorFieldValueValidator
     */
    protected $subject = null;

    protected function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            AbstractProcessorFieldValueValidator::class,
            ['validate']
        );
    }

    protected function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function getSeverityReturnSeverity()
    {
        $this->subject->_set('severity', 100);
        $this->assertEquals(100, $this->subject->getSeverity());
    }

    /**
     * @test
     */
    public function getValidationErrorReturnMessage()
    {
        $value = 'errors';
        $this->subject->_set('message', $value);

        $this->assertEquals($value, $this->subject->getValidationError());
    }

    /**
     * @test
     */
    public function errorWillSetMessageAndSeverityToError()
    {
        $value = 'error';

        $this->subject->_call('error', $value);

        $this->assertEquals($value, $this->subject->getValidationError());
        $this->assertEquals(ProcessorFieldValueValidatorInterface::ERROR, $this->subject->getSeverity());
    }

    /**
     * @test
     */
    public function warningWillSetMessageAndSeverityToError()
    {
        $value = 'warning';

        $this->subject->_call('warning', $value);

        $this->assertEquals($value, $this->subject->getValidationError());
        $this->assertEquals(ProcessorFieldValueValidatorInterface::WARNING, $this->subject->getSeverity());
    }

    /**
     * @test
     */
    public function criticalWillSetMessageAndSeverityToError()
    {
        $value = 'critical';

        $this->subject->_call('critical', $value);

        $this->assertEquals($value, $this->subject->getValidationError());
        $this->assertEquals(ProcessorFieldValueValidatorInterface::CRITICAL, $this->subject->getSeverity());
    }
}
