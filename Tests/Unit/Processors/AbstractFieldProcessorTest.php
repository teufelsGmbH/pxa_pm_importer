<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Processors;

use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pixelant\PxaPmImporter\Processors\AbstractFieldProcessor;
use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
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
            ['process']
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
    public function isValidIfRequiredReturnFalseOnEmptyValue()
    {
        $value = '';
        $configuration = [
            'validation' => 'required'
        ];
        $this->subject->_set('configuration', $configuration);

        $this->assertFalse($this->subject->isValid($value));
    }

    /**
     * @test
     */
    public function isValidIfNotRequiredReturnTrueOnEmptyValue()
    {
        $value = '';
        $configuration = [
            'validation' => ''
        ];
        $this->subject->_set('configuration', $configuration);

        $this->assertTrue($this->subject->isValid($value));
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
    public function isRuleInValidationListCheckIfRuleIsInListOfValidation()
    {
        $configuration = [
            'validation' => 'testvalidation,required'
        ];
        $this->subject->_set('configuration', $configuration);

        $this->assertTrue($this->subject->_call('isRuleInValidationList', 'required'));
        $this->assertFalse($this->subject->_call('isRuleInValidationList', 'test'));
    }

    /**
     * @test
     */
    public function addErrorWillAddError()
    {
        $expect = ['Error test'];
        $error = 'Error test';

        $this->subject->_set('validationErrors', []);
        $this->subject->_call('addError', $error);

        $this->assertEquals($expect, $this->subject->getValidationErrors());
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
