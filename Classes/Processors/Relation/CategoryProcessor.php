<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation;

use Pixelant\PxaProductManager\Domain\Model\Category;

/**
 * Class CategoryProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class CategoryProcessor extends AbstractRelationFieldProcessor implements AbleCreateMissingEntities
{
    /**
     * @inheritDoc
     */
    public function createMissingEntity(string $importId)
    {
        $fields = ['title' => $importId, $this->tcaHiddenField() => 1];
        $sysLanguageUid = 0;

        $this->repository->createEmpty(
            $importId,
            'sys_category',
            $sysLanguageUid,
            $this->newRecordFieldsWithPlaceHolder($fields)
        );
    }

    /**
     * @inheritDoc
     */
    protected function domainModel(): string
    {
        return Category::class;
    }
}
