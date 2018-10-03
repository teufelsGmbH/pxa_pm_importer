<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Processors;

use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pixelant\PxaPmImporter\Exception\InvalidProcessorConfigurationException;
use Pixelant\PxaPmImporter\Processors\ProductAttributeProcessor;
use Pixelant\PxaPmImporter\Service\Importer\ImporterInterface;
use Pixelant\PxaProductManager\Domain\Model\Attribute;
use Pixelant\PxaProductManager\Domain\Model\AttributeValue;
use Pixelant\PxaProductManager\Domain\Model\Product;
use Pixelant\PxaProductManager\Domain\Repository\AttributeRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ProductAttributeProcessorTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Processors
 */
class ProductAttributeProcessorTest extends UnitTestCase
{
    /**
     * @var ProductAttributeProcessor|MockObject|AccessibleMockObjectInterface
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getAccessibleMock(
            ProductAttributeProcessor::class,
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
    public function preProcessWithoutAttributeUidThrowsException()
    {
        $value = '';

        $this->expectException(InvalidProcessorConfigurationException::class);
        $this->subject->preProcess($value);
    }

    /**
     * @test
     */
    public function preProcessThrowsExceptionIfAttributeNotFound()
    {
        $repository = $this->createMock(AttributeRepository::class);
        $conf = [
            'attributeUid' => 12
        ];
        $this->subject->_set('attributeRepository', $repository);
        $this->subject->_set('configuration', $conf);

        $this->expectException(\RuntimeException::class);

        $value = '';
        $this->subject->preProcess($value);
    }

    /**
     * @test
     */
    public function isValidReturnFalseForNotValidDateFormat()
    {
        $attribute = new Attribute();
        $attribute->setType(Attribute::ATTRIBUTE_TYPE_DATETIME);

        $this->subject->_set('attribute', $attribute);

        $value = 'TEST';
        $this->assertFalse($this->subject->isValid($value));
    }

    /**
     * Parse date time from configuration format
     * @test
     */
    public function parseDateTimeFromFormat()
    {
        $configuration = ['dateFormat' => 'Y-m-d'];
        $value = '2018-09-10';

        $this->subject->_set('configuration', $configuration);

        $this->assertEquals($value, $this->subject->_call('parseDateTime', $value)->format('Y-m-d'));
    }

    /**
     * @test
     */
    public function getAttributeValueWillReturnAttributeValueUsingEntityAndAttributeIfExist()
    {
        $entity = new Product();
        $attribute = new Attribute();
        $attribute->_setProperty('uid', 222);

        $attributeValue = new AttributeValue();
        $attributeValue->setAttribute($attribute);

        $entity->addAttributeValue($attributeValue);

        $this->inject($this->subject, 'entity', $entity);
        $this->inject($this->subject, 'attribute', $attribute);

        $this->assertSame($attributeValue, $this->subject->_call('getAttributeValue'));
    }

    /**
     * @test
     */
    public function getAttributeValueWillReturnNullUsingEntityAndAttributeIfDoesNotExist()
    {
        $entity = new Product();
        $attribute = new Attribute();
        $attribute->_setProperty('uid', 22);
        $attributeNonInProcessor = new Attribute();
        $attributeNonInProcessor->_setProperty('uid', 33);

        $attributeValue = new AttributeValue();
        $attributeValue->setAttribute($attribute);

        $entity->addAttributeValue($attributeValue);

        $this->inject($this->subject, 'entity', $entity);
        $this->inject($this->subject, 'attribute', $attributeNonInProcessor);

        $this->assertNull($this->subject->_call('getAttributeValue'));
    }

    /**
     * @test
     */
    public function updateAttributeValueWillUpdateValueOfAttribute()
    {
        $attributeValue = new AttributeValue();
        $attributeValue->setValue('old value');

        $subject = $this->getAccessibleMock(
            ProductAttributeProcessor::class,
            ['getAttributeValue'],
            [],
            '',
            false
        );

        $subject
            ->expects($this->once())
            ->method('getAttributeValue')
            ->willReturn($attributeValue);

        $newValue = 'Super new value';

        $subject->_call('updateAttributeValue', $newValue);

        $this->assertEquals($newValue, $attributeValue->getValue());
    }

    /**
     * @test
     */
    public function updateAttributeValueWillAttachNewAttributeWithUpdatedValueIfNotExist()
    {
        $subject = $this->getAccessibleMock(
            ProductAttributeProcessor::class,
            ['getAttributeValue'],
            [],
            '',
            false
        );
        $attributeValue = new AttributeValue();
        $attribute = new Attribute();
        $dbRow = ['sys_language_uid' => 0];

        $objectManager = $this->createPartialMock(ObjectManager::class, ['get']);
        $objectManager
            ->expects($this->once())
            ->method('get')
            ->willReturn($attributeValue);


        $entity = new Product();

        $subject
            ->expects($this->once())
            ->method('getAttributeValue');

        $this->inject($subject, 'entity', $entity);
        $this->inject($subject, 'objectManager', $objectManager);
        $this->inject($subject, 'attribute', $attribute);
        $this->inject($subject, 'dbRow', $dbRow);
        $this->inject($subject, 'importer', $this->createMock(ImporterInterface::class));

        $newValue = 'Super new value';

        $subject->_call('updateAttributeValue', $newValue);

        $attributeValues = $subject->_get('entity')->getAttributeValues();
        $this->assertCount(1, $attributeValues);
        $attributeValues->rewind();
        $this->assertSame($attributeValue, $attributeValues->current());
    }
}
