<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use Pixelant\PxaPmImporter\Exception\InvalidProcessorConfigurationException;
use Pixelant\PxaProductManager\Domain\Model\Attribute;
use Pixelant\PxaProductManager\Domain\Model\Product;
use Pixelant\PxaProductManager\Domain\Repository\AttributeRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ProductAttributeProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class ProductAttributeProcessor extends AbstractFieldProcessor
{
    /**
     * @var Attribute
     */
    protected $attribute = null;

    /**
     * @var AttributeRepository
     */
    protected $attributeRepository = null;

    /**
     * @var Product
     */
    protected $entity = null;

    /**
     * Initialize
     */
    public function __construct()
    {
        $this->attributeRepository = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(AttributeRepository::class);
    }

    /**
     * Prepare for process
     *
     * @param mixed $value
     */
    public function preProcess(&$value): void
    {
        if (empty($this->configuration['attributeUid'])) {
            // @codingStandardsIgnoreStart
            throw new InvalidProcessorConfigurationException('Missing "attributeUid" of processor configuration. Name - "' . $this->property . '"', 1536325707731);
            // @codingStandardsIgnoreEnd
        }

        $this->attribute = $this->attributeRepository->findByUid((int)$this->configuration['attributeUid']);

        if ($this->attribute === null) {
            // @codingStandardsIgnoreStart
            throw new \RuntimeException('Could not found attribute with UID "' . $this->configuration['attributeUid'] . '"', 1536325896431);
            // @codingStandardsIgnoreEnd
        }

        parent::preProcess($value);
    }

    /**
     * Process attribute value
     *
     * @param $value
     */
    public function process($value): void
    {
        $attributeValues = $this->entity->getSerializedAttributesValues();

        switch ($this->attribute->getType()) {
            case Attribute::ATTRIBUTE_TYPE_DROPDOWN:
            case Attribute::ATTRIBUTE_TYPE_MULTISELECT:
                //$optionsValues =
                break;
        }

        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($this->attribute,'Debug',16);die;
    }
}
