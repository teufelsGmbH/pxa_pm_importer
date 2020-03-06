<?php

namespace Pixelant\PxaPmImporter\Tests\Unit\Validation;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Validation\ValidationManager;
use Pixelant\PxaPmImporter\Validation\ValidationResult;
use Pixelant\PxaPmImporter\Validation\Validator\ValidatorFactory;

/**
 */
class ValidationManagerTest extends UnitTestCase
{
    /**
     * @var ValidationManager
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder(ValidationManager::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->inject($this->subject, 'factory', $this->createMock(ValidatorFactory::class));
    }

    /**
     * @test
     */
    public function getLastValidationResultReturnLastValidationResult()
    {
        $validationResult = $this->createMock(ValidationResult::class);

        $this->inject($this->subject, 'validationResult', $validationResult);

        $this->assertSame($validationResult, $this->subject->getLastValidationResult());
    }

    /**
     * @test
     */
    public function isValidResetValidationResult()
    {
        $validationResult = $this->createMock(ValidationResult::class);
        $this->inject($this->subject, 'validationResult', $validationResult);

        $this->subject->isValid([]);

        $this->assertNull($this->subject->getLastValidationResult());
    }
}
