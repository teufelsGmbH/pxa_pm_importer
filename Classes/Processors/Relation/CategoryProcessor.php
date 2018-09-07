<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation;

use Pixelant\PxaProductManager\Domain\Model\Category;
use Pixelant\PxaProductManager\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class CategoryProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class CategoryProcessor extends AbstractRelationFieldProcessor
{
    /**
     * Table
     *
     * @return string
     */
    protected function getDbTable(): string
    {
        return 'sys_category';
    }

    /**
     * @return string
     */
    protected function getModelClassName(): string
    {
        return Category::class;
    }

    /**
     * @return Repository
     */
    protected function getRepository(): Repository
    {
        if ($this->repository === null) {
            $this->repository = GeneralUtility::makeInstance(ObjectManager::class)->get(CategoryRepository::class);
        }

        return $this->repository;
    }
}
