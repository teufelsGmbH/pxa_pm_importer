<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Processors;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Exception\InvalidProcessorConfigurationException;
use Pixelant\PxaPmImporter\Processors\DateTimeProcessor;
use Pixelant\PxaProductManager\Domain\Model\Product;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class DateTimeProcessorTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Processors
 */
class DateTimeProcessorTest extends UnitTestCase
{
    /**
     * @var DateTimeProcessor
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getAccessibleMock(
            DateTimeProcessor::class,
            ['getOptions'],
            [],
            '',
            false
        );
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->subject);
    }

    /**
     * @test
     */
    public function preProcessWithoutInputFormatThrowsException()
    {
        $value = '2018-09-27';

        $configuration = [
            'outputFormat' => 'Y-m-d'
        ];

        $this->subject->_set('configuration', $configuration);
        $this->expectException(InvalidProcessorConfigurationException::class);
        $this->subject->preProcess($value);
    }

    /**
     * @test
     */
    public function preProcessWithoutOutputFormatThrowsException()
    {
        $value = '2018-09-27';

        $configuration = [
            'inputFormat' => 'Y-m-d',
        ];

        $this->subject->_set('configuration', $configuration);
        $this->expectException(InvalidProcessorConfigurationException::class);
        $this->subject->preProcess($value);
    }

    /**
     * @test
     */
    public function preProcessWillReturnExpectedValueFromIntegerProperty()
    {
        $value = '09/26/2018 02:00:32';
        $expect = '1537927232';

        $configuration = [
            'inputFormat' => 'm/d/Y h:i:s',
            'outputFormat' => 'U'
        ];

        $entity = new Product();
        $entity->_setProperty('crdate', 0);

        $this->subject->_set('configuration', $configuration);
        $this->subject->_set('entity', $entity);
        $this->subject->_set('property', 'crdate');

        $this->subject->preProcess($value);
        $this->assertTrue($this->subject->isValid($value));
        $this->subject->process($value);
        $this->assertEquals($expect, $entity->getCrdate());
    }

    /**
     * @test
     */
    public function preProcessWillReturnExpectedValueFromStringProperty()
    {
        $value = '1538117523';
        $expect = '2018-09-28T06:52:03+00:00';

        $configuration = [
            'inputFormat' => 'U',
            'outputFormat' => 'c'
        ];

        $entity = new Product();
        $entity->_setProperty('name', '');

        $this->subject->_set('configuration', $configuration);
        $this->subject->_set('entity', $entity);
        $this->subject->_set('property', 'name');

        $this->subject->preProcess($value);
        $this->assertTrue($this->subject->isValid($value));
        $this->subject->process($value);
        $this->assertEquals($expect, $entity->getName());
    }

    /**
     * @test
     */
    public function preProcessWillReturnExpectedValueFromIntegerPropertyWithNoTimeSet()
    {
        $value = '2000-06-10';
        $expect = '960595200';

        $configuration = [
            'inputFormat' => 'Y-m-d',
            'outputFormat' => 'U'
        ];

        $entity = new Product();
        $entity->_setProperty('crdate', 0);

        $this->subject->_set('configuration', $configuration);
        $this->subject->_set('entity', $entity);
        $this->subject->_set('property', 'crdate');

        $this->subject->preProcess($value);
        $this->assertTrue($this->subject->isValid($value));
        $this->subject->process($value);
        $this->assertEquals($expect, $entity->getCrdate());
    }

    /**
     * @test
     */
    public function preProcessWillReturnExpectedValueFromStringPropertyWithNoTimeSet()
    {
        $value = '2000-06-10';
        $expect = '2000-06-10T00:00:00+00:00';

        $configuration = [
            'inputFormat' => 'Y-m-d',
            'outputFormat' => 'c'
        ];

        $entity = new Product();
        $entity->_setProperty('name', '');

        $this->subject->_set('configuration', $configuration);
        $this->subject->_set('entity', $entity);
        $this->subject->_set('property', 'name');

        $this->subject->preProcess($value);
        $this->assertTrue($this->subject->isValid($value));
        $this->subject->process($value);
        $this->assertEquals($expect, $entity->getName());
    }
}
