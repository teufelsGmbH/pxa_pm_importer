<?php

namespace Pixelant\PxaPmImporter\Tests\Unit\Validation;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Validation\ValidationResult;

/**
 * @package Pixelant\PxaPmImporter\Tests\Unit\Validation
 */
class ValidationResultTest extends UnitTestCase
{
    /**
     * @var ValidationResult
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new ValidationResult();
    }

    /**
     * @test
     */
    public function initValueForPassedIsTrue()
    {
        $this->assertTrue($this->subject->passed());
    }

    /**
     * @test
     */
    public function canSetPassed()
    {
        $this->subject->setPassed(false);
        $this->assertFalse($this->subject->passed());
    }

    /**
     * @test
     */
    public function defaultValueForErrorEmtpyString()
    {
        $this->assertEmpty($this->subject->getError());
    }

    /**
     * @test
     */
    public function errorCanBeSet()
    {
        $error = 'error';
        $this->subject->setError($error);

        $this->assertEquals($error, $this->subject->getError());
    }
}
