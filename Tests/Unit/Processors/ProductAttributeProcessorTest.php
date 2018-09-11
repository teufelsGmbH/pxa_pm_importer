<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Processors;

use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pixelant\PxaPmImporter\Exception\InvalidProcessorConfigurationException;
use Pixelant\PxaPmImporter\Processors\ProductAttributeProcessor;
use Pixelant\PxaProductManager\Domain\Model\Attribute;
use Pixelant\PxaProductManager\Domain\Repository\AttributeRepository;

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
            ['updateAttributeValue', 'getOptions'],
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
}
