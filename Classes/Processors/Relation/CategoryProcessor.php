<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation;

use Pixelant\PxaPmImporter\Exception\FailedInitEntityException;
use Pixelant\PxaPmImporter\Exception\PostponeProcessorException;
use Pixelant\PxaPmImporter\Processors\Traits\InitRelationEntities;
use Pixelant\PxaProductManager\Domain\Model\Category;

/**
 * Class CategoryProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class CategoryProcessor extends AbstractRelationFieldProcessor
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
                Category::class
            );
        } catch (FailedInitEntityException $exception) {
            throw new PostponeProcessorException(
                'Related category not found [ID- "' . $exception->getIdentifier() . '"]',
                1547190959260
            );
        }

        return $entities;
    }
}
