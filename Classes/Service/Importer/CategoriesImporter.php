<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Importer;

use Pixelant\PxaProductManager\Domain\Model\Category;
use Pixelant\PxaProductManager\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Database\Connection;

/**
 * Class CategoriesImporter
 * @package Pixelant\PxaPmImporter\Service\Importer
 */
class CategoriesImporter extends Importer
{
    /**
     * @var CategoryRepository
     */
    protected $repository = null;

    /**
     * Default fields for new record
     *
     * @var array
     */
    protected $defaultNewRecordFields = [
        'values' => [
            'title' => ''
        ],
        'types' => [
            Connection::PARAM_STR
        ]
    ];

    /**
     * Set table name
     */
    protected function initDbTableName(): void
    {
        $this->dbTable = 'sys_category';
    }

    /**
     * Init repository
     */
    protected function initRepository(): void
    {
        $this->repository = $this->objectManager->get(CategoryRepository::class);
    }

    /**
     * Category mode name
     */
    protected function initModelName(): void
    {
        $this->modelName = Category::class;
    }
}
