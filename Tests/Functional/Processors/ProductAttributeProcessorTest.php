<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Functional\Processors;

use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pixelant\PxaPmImporter\Processors\ProductAttributeProcessor;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class ProductAttributeProcessorTest
 * @package Pixelant\PxaPmImporter\Tests\Functional\Processors
 */
class ProductAttributeProcessorTest extends FunctionalTestCase
{
    /**
     * @var ProductAttributeProcessor|MockObject|AccessibleMockObjectInterface
     */
    protected $subject = null;

    protected $testExtensionsToLoad = ['typo3conf/ext/pxa_product_manager', 'typo3conf/ext/pxa_pm_importer'];

    protected function setUp()
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_pxaproductmanager_domain_model_attributevalue.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_pxaproductmanager_domain_model_option.xml');

        $this->subject = $this->getAccessibleMock(
            ProductAttributeProcessor::class,
            ['dummy'],
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
    public function updateAttributeValueWillUpdateValueOfAttribute()
    {
        $newValue = 'Super new value';
        $productUid = 11;
        $attributeUid = 22;

        $productEntity = $this->createPartialMock(AbstractEntity::class, ['dummy']);
        $productEntity->_setProperty('uid', $productUid);
        $attributeEntity = $this->createPartialMock(AbstractEntity::class, ['dummy']);
        $attributeEntity->_setProperty('uid', $attributeUid);

        $this->subject->_set('entity', $productEntity);
        $this->subject->_set('attribute', $attributeEntity);
        $this->subject->_set('dbRow', ['sys_language_uid' => 0]);

        $this->subject->_call('updateAttributeValue', $newValue);

        $updated = $this->getAttributeRecordForProductAndAttribute($productUid, $attributeUid);
        $this->assertEquals($updated['value'], $newValue);
    }

    /**
     * @test
     */
    public function updateAttributeValueWillCreateNewAttributeValueRecordIfDoesNotExist()
    {
        $newValue = 'New value of new record';
        $productUid = 999;
        $attributeUid = 22;
        $pid = 1122;

        $productEntity = $this->createPartialMock(AbstractEntity::class, ['dummy']);
        $productEntity->_setProperty('uid', $productUid);
        $attributeEntity = $this->createPartialMock(AbstractEntity::class, ['dummy']);
        $attributeEntity->_setProperty('uid', $attributeUid);

        $importer = new class
        {
            public function getPid()
            {
                return 1122;
            }
        };

        $this->subject->_set('entity', $productEntity);
        $this->subject->_set('attribute', $attributeEntity);
        $this->subject->_set('importer', $importer);
        $this->subject->_set('dbRow', ['sys_language_uid' => 0]);

        // Before it should not exist
        $this->assertFalse($this->getAttributeRecordForProductAndAttribute($productUid, $attributeUid));

        $this->subject->_call('updateAttributeValue', $newValue);

        $newRecord = $this->getAttributeRecordForProductAndAttribute($productUid, $attributeUid);

        $this->assertEquals($newRecord['value'], $newValue);
        $this->assertEquals($newRecord['product'], $productUid);
        $this->assertEquals($newRecord['attribute'], $attributeUid);
        $this->assertEquals($newRecord['pid'], $pid);
    }

    /**
     * @test
     */
    public function getOptionsCanFindOptionsByIdentifierUidOrValue()
    {
        $values = 'BWM,Tesla';

        $expect = [131, 141];

        $importer = new class
        {
            public function getPid()
            {
                return 1;
            }
        };
        $mockedAttribute = $this->createPartialMock(AbstractEntity::class, ['dummy']);
        $mockedAttribute->_setProperty('uid', 3344);

        $this->subject->_set('importer', $importer);
        $this->subject->_set('attribute', $mockedAttribute);

        $this->assertEquals($expect, $this->subject->_call('getOptions', $values));
    }

    protected function getAttributeRecordForProductAndAttribute($product, $attiribute)
    {
        $row = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_pxaproductmanager_domain_model_attributevalue')
            ->select(
                ['*'],
                'tx_pxaproductmanager_domain_model_attributevalue',
                [
                    'product' => $product,
                    'attribute' => $attiribute
                ],
                [],
                [],
                1
            )
            ->fetch();

        return $row;
    }
}
