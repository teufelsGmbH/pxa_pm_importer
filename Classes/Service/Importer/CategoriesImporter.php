<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Importer;

use Pixelant\PxaPmImporter\Domain\Model\Import;
use Pixelant\PxaPmImporter\Service\Source\SourceInterface;
use Pixelant\PxaProductManager\Domain\Model\Category;
use Pixelant\PxaProductManager\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Database\Connection;

/**
 * Class CategoriesImporter
 * @package Pixelant\PxaPmImporter\Service\Importer
 */
class CategoriesImporter extends AbstractImporter
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
     * @param SourceInterface $source
     * @param Import $import
     * @param array $configuration
     */
    public function preImport(SourceInterface $source, Import $import, array $configuration = []): void
    {
    }

    /**
     * @param Import $import
     */
    public function postImport(Import $import): void
    {
    }

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
