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
