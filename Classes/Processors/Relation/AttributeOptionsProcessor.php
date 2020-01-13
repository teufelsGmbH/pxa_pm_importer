<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation;

use Pixelant\PxaProductManager\Domain\Model\Option;

/**
 * Class AttributeOptionsProcessor
 * @package Pixelant\PxaPmImporter\Processors\Relation
 */
class AttributeOptionsProcessor extends AbstractRelationFieldProcessor implements AbleCreateMissingEntities
{
    /**
     * If not found create one
     *
     * @param string $identifier
     */
    public function createMissingEntity(string $identifier): void
    {
        $fields = [
            'value' => $identifier,
            'attribute' => $this->entity->getUid()
        ];

        $this->repository->createEmpty(
            $identifier,
            'tx_pxaproductmanager_domain_model_option',
            0,
            $this->newRecordFieldsWithPlaceHolder($fields)
        );
    }

    /**
     * @inheritDoc
     */
    protected function domainModel(): string
    {
        return Option::class;
    }
}
