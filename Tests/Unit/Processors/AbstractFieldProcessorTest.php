<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Processors;

use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pixelant\PxaPmImporter\Domain\Validation\ValidationStatus;
use Pixelant\PxaPmImporter\Domain\Validation\ValidationStatusInterface;
use Pixelant\PxaPmImporter\Domain\Validation\Validator\ProcessorFieldValueValidatorInterface;
use Pixelant\PxaPmImporter\Exception\ProcessorValidation\CriticalErrorValidationException;
use Pixelant\PxaPmImporter\Exception\ProcessorValidation\ErrorValidationException;
use Pixelant\PxaPmImporter\Processors\AbstractFieldProcessor;
use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class AbstractFieldProcessorTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Processors
 */
class AbstractFieldProcessorTest extends UnitTestCase
{
    /**
     * @var AbstractFieldProcessor|MockObject|AccessibleMockObjectInterface
     */
    protected $subject = null;

    protected function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            AbstractFieldProcessor::class,
            ['process', 'resolveValidator'],
            [],
            '',
            false
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
    public function preProcessWillTrimString()
    {
        $value = '  test  ';
        $expect = 'test';

        $this->subject->preProcess($value);
        $this->assertEquals($expect, $value);
    }

    /**
     * @test
     */
    public function preProcessWillNotTrimIfNoAString()
    {
        $value = 12;
        $expect = 12;

        $this->subject->preProcess($value);
        $this->assertEquals($expect, $value);
    }

    /**
     * @test
     */
    public function ifValidationNotSetIsValidReturnTrue()
    {
        $value = '';
        $configuration = [];
        $this->subject->_set('configuration', $configuration);

        $this->assertTrue($this->subject->isValid($value));
    }

    /**
     * @test
     */
    public function resolveValidatorThrowsExceptionIfClassDoesNotExist()
    {
        $className= 'FakeClassName';
        $subject = $this->getAccessibleMock(
            AbstractFieldProcessor::class,
            ['process'],
            [],
            '',
            false
        );
        $this->expectException(\RuntimeException::class);
        $subject->_call('resolveValidator', $className);
    }

    /**
     * @test
     */
    public function resolveValidatorThrowsExceptionIfClassIsNotValidatorInterface()
    {
        $className= GeneralUtility::class; // Existing class
        $subject = $this->getAccessibleMock(
            AbstractFieldProcessor::class,
            ['process'],
            [],
            '',
            false
        );

        $this->expectException(\UnexpectedValueException::class);
        $subject->_call('resolveValidator', $className);
    }

    /**
     * @test
     */
    public function isValidAddErrorAndReturnFalseOnWarningValidationStatus()
    {
        $value = '';
        $configuration = [
            'validation' => [
                'required'
            ]
        ];
        $subject = $this->getAccessibleMock(
            AbstractFieldProcessor::class,
            ['process', 'resolveValidator', 'addError'],
            [],
            '',
            false
        );
        $subject->_set('configuration', $configuration);

        $mockedValidator = $this->createPartialMock(ProcessorFieldValueValidatorInterface::class, ['validate', 'getValidationStatus']);
        $mockedValidationStatus = $this->createPartialMock(ValidationStatus::class, ['getSeverity']);

        $subject
            ->expects($this->once())
            ->method('resolveValidator')
            ->with('required')
            ->willReturn($mockedValidator);

        $subject
            ->expects($this->once())
            ->method('addError');

        $mockedValidator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(false);

        $mockedValidator
            ->expects($this->atLeastOnce())
            ->method('getValidationStatus')
            ->willReturn($mockedValidationStatus);

        $mockedValidationStatus
            ->expects($this->once())
            ->method('getSeverity')
            ->willReturn(ValidationStatusInterface::WARNING);

        $this->assertFalse($subject->isValid($value));
    }

    /**
     * @test
     */
    public function isValidThrowExceptionOnErrorValidationStatus()
    {
        $value = '';
        $configuration = [
            'validation' => [
                'required'
            ]
        ];
        $this->subject->_set('configuration', $configuration);

        $mockedValidator = $this->createPartialMock(ProcessorFieldValueValidatorInterface::class, ['validate', 'getValidationStatus']);
        $mockedValidationStatus = $this->createPartialMock(ValidationStatus::class, ['getSeverity']);

        $this->subject
            ->expects($this->once())
            ->method('resolveValidator')
            ->with('required')
            ->willReturn($mockedValidator);

        $mockedValidator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(false);

        $mockedValidator
            ->expects($this->atLeastOnce())
            ->method('getValidationStatus')
            ->willReturn($mockedValidationStatus);

        $mockedValidationStatus
            ->expects($this->once())
            ->method('getSeverity')
            ->willReturn(ValidationStatusInterface::ERROR);

        $this->expectException(ErrorValidationException::class);
        $this->subject->isValid($value);
    }

    /**
     * @test
     */
    public function isValidThrowExceptionOnCriticalValidationStatus()
    {
        $value = '';
        $configuration = [
            'validation' => [
                'required'
            ]
        ];
        $this->subject->_set('configuration', $configuration);

        $mockedValidator = $this->createPartialMock(ProcessorFieldValueValidatorInterface::class, ['validate', 'getValidationStatus']);
        $mockedValidationStatus = $this->createPartialMock(ValidationStatus::class, ['getSeverity']);

        $this->subject
            ->expects($this->once())
            ->method('resolveValidator')
            ->with('required')
            ->willReturn($mockedValidator);

        $mockedValidator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(false);

        $mockedValidator
            ->expects($this->atLeastOnce())
            ->method('getValidationStatus')
            ->willReturn($mockedValidationStatus);

        $mockedValidationStatus
            ->expects($this->once())
            ->method('getSeverity')
            ->willReturn(ValidationStatusInterface::CRITICAL);

        $this->expectException(CriticalErrorValidationException::class);
        $this->subject->isValid($value);
    }

    /**
     * @test
     */
    public function getValidationErrorsAsStringReturnCommaSeparatedErrors()
    {
        $errors = ['Error1', 'Error2'];
        $expect = '"Error1", "Error2"';

        $this->subject->_set('validationErrors', $errors);

        $this->assertEquals($expect, $this->subject->getValidationErrorsString());
    }

    /**
     * @test
     */
    public function getValidationErrorsReturnErrors()
    {
        $errors = ['Error1', 'Error2'];

        $this->subject->_set('validationErrors', $errors);

        $this->assertEquals($errors, $this->subject->getValidationErrors());
    }

    /**
     * @test
     */
    public function initWillInitVariables()
    {
        $entity = $this->createMock(AbstractEntity::class);
        $dbRow = ['id' => 123];
        $property = 'property';
        $importer = $this->createMock(ImporterInterface::class);
        $configuration = ['test' => 'test'];

        $this->subject->init(
            $entity,
            $dbRow,
            $property,
            $importer,
            $configuration
        );

        $this->assertSame($entity, $this->subject->getProcessingEntity());
        $this->assertEquals($dbRow, $this->subject->getProcessingDbRow());
        $this->assertEquals($property, $this->subject->getProcessingProperty());
        $this->assertEquals($configuration, $this->subject->getConfiguration());
    }

    /**
     * @test
     */
    public function addErrorWillAddError()
    {
        $error = 'Error test';

        $this->subject->_set('validationErrors', []);
        $this->subject->_call('addError', $error);


        $this->assertTrue(
            strpos($this->subject->getValidationErrors()[0], $error) !== false
        );
    }

    /**
     * @test
     */
    public function simplePropertySetSetValueForEntity()
    {
        $entity = $this->createPartialMock(AbstractEntity::class, ['dummy']);
        $this->subject->_set('entity', $entity);
        $this->subject->_set('property', 'pid');

        $uid = 12;
        $this->subject->_call('simplePropertySet', $uid);

        $this->assertEquals($uid, $entity->getPid());
    }
}
