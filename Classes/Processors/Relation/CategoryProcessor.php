<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation;

use Pixelant\PxaProductManager\Domain\Model\Category;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        $fields = array_merge($this->defaultNewFields($importId), [
            'title' => $importId,
            $this->tcaHiddenField() => 1,
        ]);

        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_category')
            ->insert(
                'sys_category',
                $fields
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
