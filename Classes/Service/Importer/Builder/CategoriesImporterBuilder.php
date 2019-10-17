<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Importer\Builder;

use Pixelant\PxaProductManager\Domain\Model\Category;
use Pixelant\PxaProductManager\Domain\Repository\CategoryRepository;

/**
 * Class CategoriesImporterBuilder
 * @package Pixelant\PxaPmImporter\Service\Importer\Builder
 */
class CategoriesImporterBuilder extends AbstractBuilder
{
    /**
     * Add repository of import subject
     */
    public function addRepository(): void
    {
        $repository = $this->objectManager->get(CategoryRepository::class);
        $this->importer->setRepository($repository);
    }

    /**
     * Add model name of import subject
     */
    public function addModelName(): void
    {
        $this->importer->setModelName(Category::class);
    }

    /**
     * Add table name of import subject
     */
    public function addDatabaseTableName(): void
    {
        $this->importer->setDatabaseTableName('sys_category');
    }

    /**
     * Add default fields of new created record
     */
    public function addDefaultNewRecordFields(): void
    {
        $this->importer->setDefaultNewRecordFields(
            [
                'values' => [
                    'title' => ''
                ],
                'types' => [
                    \PDO::PARAM_STR
                ]
            ]
        );
    }
}
