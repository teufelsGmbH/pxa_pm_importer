<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation;

use Pixelant\PxaProductManager\Domain\Model\Option;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        $fields = array_merge($this->defaultNewFields($identifier), [
            'value' => $identifier,
            'sys_language_uid' => 0,
            'attribute' => $this->entity->getUid(),
        ]);

        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_pxaproductmanager_domain_model_option')
            ->insert(
                'tx_pxaproductmanager_domain_model_option',
                $fields
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
