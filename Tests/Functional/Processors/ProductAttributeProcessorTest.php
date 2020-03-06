<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Functional\Processors;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Pixelant\PxaPmImporter\Context\ImportContext;
use Pixelant\PxaPmImporter\Processors\ProductAttributeProcessor;
use Pixelant\PxaProductManager\Domain\Model\Attribute;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ProductAttributeProcessorTest
 * @package Pixelant\PxaPmImporter\Tests\Functional\Processors
 */
class ProductAttributeProcessorTest extends FunctionalTestCase
{
    /**
     * @var ProductAttributeProcessor
     */
    protected $subject = null;

    protected $testExtensionsToLoad = ['typo3conf/ext/pxa_product_manager', 'typo3conf/ext/pxa_pm_importer'];

    protected function setUp()
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_pxaproductmanager_domain_model_option.xml');

        $context = GeneralUtility::makeInstance(ImportContext::class);
        $context->setNewRecordsPid(1);
        $context->setStoragePids([1]);

        $this->subject = GeneralUtility::makeInstance(ObjectManager::class)->get(ProductAttributeProcessor::class);
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

        $expect = '131,141';

        $attribute = new Attribute();
        $attribute->_setProperty('uid', 3344);

        $this->inject($this->subject, 'attribute', $attribute);

        $this->assertEquals($expect, $this->callInaccessibleMethod($this->subject, 'getOptions', $values));
    }
}
