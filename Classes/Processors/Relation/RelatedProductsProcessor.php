<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation;

use Pixelant\PxaPmImporter\Exception\PostponeProcessorException;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use Pixelant\PxaProductManager\Domain\Model\Product;
use Pixelant\PxaProductManager\Domain\Repository\ProductRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class RelatedProductsProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class RelatedProductsProcessor extends AbstractRelationFieldProcessor
{
    /**
     * Set categories
     *
     * @param mixed $value
     */
    protected function initEntities($value): void
    {
        $value = strtolower($value);

        $this->entities = []; // Reset, important for PostponeProcessorException
        $value = GeneralUtility::trimExplode(',', $value, true);

        foreach ($value as $identifier) {
            if (true === (bool)$this->configuration['treatIdentifierAsUid']) {
                $model = GeneralUtility::makeInstance(ObjectManager::class)
                    ->get(ProductRepository::class)
                    ->findByUid((int)$identifier);
            } else {
                $record = $this->getRecordByImportIdentifier($identifier, 'tx_pxaproductmanager_domain_model_product'); // Default language record
                if ($record !== null) {
                    $model = MainUtility::convertRecordArrayToModel($record, Product::class);
                }
            }

            if (isset($model) && is_object($model)) {
                $this->entities[] = $model;
            } else {
                // @codingStandardsIgnoreStart
                throw new PostponeProcessorException('Product with id "' . $identifier . '" not found.', 1536148407513);
                // @codingStandardsIgnoreEnd
            }
        }
    }
}
