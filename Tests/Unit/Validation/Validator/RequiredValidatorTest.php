<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Validation\Validator;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Validation\ValidationResult;
use Pixelant\PxaPmImporter\Validation\Validator\RequiredValidator;

/**
 * @package Pixelant\PxaPmImporter\Tests\Unit\Domain\Validation\Validator
 */
class RequiredValidatorTest extends UnitTestCase
{
    /**
     * @var RequiredValidator
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();

        $this->subject = $this->getMockBuilder(RequiredValidator::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @test
     */
    public function validateWillFailOnEmptyValue()
    {
        $row = ['field' => ''];

        $result = $this->createMock(ValidationResult::class);
        $result
            ->expects($this->once())
            ->method('setPassed')
            ->with(false);
        $this->inject($this->subject, 'result', $result);

        $this->subject->validate($row, 'field');
    }

    /**
     * @test
     */
    public function validateWillPassOnNonEmptyValue()
    {
        $row = ['title' => '123'];

        $result = $this->createMock(ValidationResult::class);
        $result
            ->expects($this->never())
            ->method('setPassed');
        $this->inject($this->subject, 'result', $result);

        $this->subject->validate($row, 'title');
    }
}
