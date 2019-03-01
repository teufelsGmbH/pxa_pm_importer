<?php

namespace Pixelant\PxaPmImporter\Tests\Unit\Domain\Validation\Validator;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Domain\Validation\Validator\ValidatorFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ValidatorFactoryTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Domain\Validation\Validator
 */
class ValidatorFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function resolveValidatorThrowsExceptionIfClassDoesNotExist()
    {
        $className= 'FakeClassName';

        $this->expectException(\InvalidArgumentException::class);
        ValidatorFactory::factory($className);
    }

    /**
     * @test
     */
    public function factoryThrowsExceptionIfClassIsNotValidatorInterface()
    {
        $className= GeneralUtility::class; // Existing class

        $this->expectException(\UnexpectedValueException::class);
        ValidatorFactory::factory($className);
    }
}
