<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Domain\Validation\Validator;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Domain\Validation\ValidationStatus;
use Pixelant\PxaPmImporter\Domain\Validation\ValidationStatusInterface;
use Pixelant\PxaPmImporter\Domain\Validation\Validator\AbstractProcessorFieldValueValidator;

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

        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->subject);
    }

    /**
     * @test
     */
    public function getValidationStatusReturnStatusWithOkMessageIfNotSet()
    {
        $this->assertEquals(ValidationStatusInterface::OK, $this->subject->getSeverity()->getSeverity());
    }

    /**
     * @test
     */
    public function createValidationStatusWillCreateStatus()
    {
        $message = 'test';
        $severity = ValidationStatusInterface::WARNING;

        $status= $this->subject->_call('createValidationStatus', $message, $severity);

        $this->assertInstanceOf(ValidationStatusInterface::class, $status);

        $this->assertEquals($message, $status->getMessage());
        $this->assertEquals($severity, $status->getSeverity());
    }

    /**
     * @test
     */
    public function getValidationStatusReturnStatus()
    {
        $validationStatus = new ValidationStatus();

        $this->subject->_set('validationStatus', $validationStatus);

        $this->assertSame($validationStatus, $this->subject->getSeverity());
    }

    /**
     * @test
     */
    public function errorWillCreateStatusWithError()
    {
        $subject = $this->getAccessibleMock(
            AbstractProcessorFieldValueValidator::class,
            ['validate', 'createValidationStatus']
        );
        $message = 'error';

        $subject
            ->expects($this->once())
            ->method('createValidationStatus')
            ->with($message, ValidationStatusInterface::ERROR);

        $subject->_call('error', $message);
    }

    /**
     * @test
     */
    public function warningWillCreateStatusWithWarning()
    {
        $subject = $this->getAccessibleMock(
            AbstractProcessorFieldValueValidator::class,
            ['validate', 'createValidationStatus']
        );
        $message = 'error';

        $subject
            ->expects($this->once())
            ->method('createValidationStatus')
            ->with($message, ValidationStatusInterface::WARNING);

        $subject->_call('warning', $message);
    }

    /**
     * @test
     */
    public function criticalWillCreateStatusWithCritical()
    {
        $subject = $this->getAccessibleMock(
            AbstractProcessorFieldValueValidator::class,
            ['validate', 'createValidationStatus']
        );
        $message = 'error';

        $subject
            ->expects($this->once())
            ->method('createValidationStatus')
            ->with($message, ValidationStatusInterface::CRITICAL);

        $subject->_call('critical', $message);
    }
}
