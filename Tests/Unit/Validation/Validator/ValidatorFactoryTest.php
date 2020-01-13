<?php

namespace Pixelant\PxaPmImporter\Tests\Unit\Validation\Validator;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Validation\Validator\ValidatorFactory;

/**
 * Class ValidatorFactoryTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Domain\Validation\Validator
 */
class ValidatorFactoryTest extends UnitTestCase
{
    /**
     * @var ValidatorFactory
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();

        $this->subject = $this->getMockBuilder(ValidatorFactory::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @test
     */
    public function resolveValidatorThrowsExceptionIfClassDoesNotExist()
    {
        $className = 'FakeClassName';

        $this->expectException(\InvalidArgumentException::class);
        $this->subject->create($className);
    }
}
