<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Domain\Validation;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Domain\Validation\ValidationStatus;
use Pixelant\PxaPmImporter\Domain\Validation\ValidationStatusInterface;

/**
 * Class ValidationStatusTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Domain\Validation
 */
class ValidationStatusTest extends UnitTestCase
{
    /**
     * @var ValidationStatus
     */
    protected $subject = null;

    protected function setUp()
    {
        $this->subject = new ValidationStatus();
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
    public function initValuesMatchExpected()
    {
        $this->assertEquals('', $this->subject->getMessage());
        $this->assertEquals(ValidationStatusInterface::WARNING, $this->subject->getSeverity());
    }

    /**
     * @test
     */
    public function canSetMessage()
    {
        $message = 'test message';

        $this->subject->setMessage($message);

        $this->assertEquals($message, $this->subject->getMessage());
    }

    /**
     * @test
     */
    public function canSetSeverity()
    {
        $severity = ValidationStatusInterface::CRITICAL;

        $this->subject->setSeverity($severity);

        $this->assertEquals($severity, $this->subject->getSeverity());
    }

    /**
     * @test
     */
    public function canSetPropertiesWithConstructor()
    {
        $message = 'new message';
        $severity = ValidationStatusInterface::ERROR;

        $subject = new ValidationStatus($message, $severity);

        $this->assertEquals($message, $subject->getMessage());
        $this->assertEquals($severity, $subject->getSeverity());
    }
}
