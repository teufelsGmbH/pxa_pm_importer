<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Processors;

use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pixelant\PxaPmImporter\Domain\Validation\Validator\ProcessorFieldValueValidatorInterface;
use Pixelant\PxaPmImporter\Exception\ProcessorValidation\CriticalErrorValidationException;
use Pixelant\PxaPmImporter\Exception\ProcessorValidation\ErrorValidationException;
use Pixelant\PxaPmImporter\Processors\AbstractFieldProcessor;
use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

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

        $mockedLogger = $this->createPartialMock(LogManager::class, ['error']);
        $subject->_set('logger', $mockedLogger);
        $subject->_set('dbRow', [ImporterInterface::DB_IMPORT_ID_FIELD => 'id', 'uid' => 1]);

        $mockedValidator = $this->createPartialMock(ProcessorFieldValueValidatorInterface::class, ['validate', 'getSeverity', 'getValidationError']);

        $subject
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
            ->method('getSeverity')
            ->willReturn(ProcessorFieldValueValidatorInterface::WARNING);

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

        $mockedValidator = $this->createPartialMock(ProcessorFieldValueValidatorInterface::class, ['validate', 'getSeverity', 'getValidationError']);

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
            ->expects($this->once())
            ->method('getSeverity')
            ->willReturn(ProcessorFieldValueValidatorInterface::ERROR);

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

        $mockedValidator = $this->createPartialMock(ProcessorFieldValueValidatorInterface::class, ['validate', 'getSeverity', 'getValidationError']);

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
            ->expects($this->once())
            ->method('getSeverity')
            ->willReturn(ProcessorFieldValueValidatorInterface::CRITICAL);

        $this->expectException(CriticalErrorValidationException::class);
        $this->subject->isValid($value);
    }

    /**
     * @test
     */
    public function initWillInitVariables()
    {
        $entity = $this->createMock(AbstractEntity::class);
        $dbRow = ['id' => 123];
        $property = 'property';
        $configuration = ['test' => 'test'];

        $this->subject->init(
            $entity,
            $dbRow,
            $property,
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
