<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation;

use Pixelant\PxaPmImporter\Exception\FailedInitEntityException;
use Pixelant\PxaPmImporter\Exception\PostponeProcessorException;
use Pixelant\PxaPmImporter\Processors\Traits\InitRelationEntities;
use Pixelant\PxaProductManager\Domain\Model\Product;

/**
 * Class RelatedProductsProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class RelatedProductsProcessor extends AbstractRelationFieldProcessor
{
    use InitRelationEntities;

    /**
     * Set categories
     *
     * @param mixed $value
     * @return array
     */
    protected function initEntities($value): array
    {
        try {
            $entities = $this->initEntitiesForTable(
                $value,
                Product::class
            );
        } catch (FailedInitEntityException $exception) {
            throw new PostponeProcessorException(
                'Related product not found [ID- "' . $exception->getIdentifier() . '"]',
                1536148407513
            );
        }

        return $entities;
    }
}
