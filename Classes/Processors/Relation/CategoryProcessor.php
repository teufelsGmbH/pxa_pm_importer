<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation;

use Pixelant\PxaPmImporter\Exception\PostponeProcessorException;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use Pixelant\PxaProductManager\Domain\Model\Category;
use Pixelant\PxaProductManager\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class CategoryProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class CategoryProcessor extends AbstractRelationFieldProcessor
{
    /**
     * Set categories
     *
     * @param mixed $value
     * @return array
     */
    protected function initEntities($value): array
    {
        $entities = [];
        $value = GeneralUtility::trimExplode(',', $value, true);

        foreach ($value as $identifier) {
            if (true === (bool)($this->configuration['treatIdentifierAsUid'] ?? false)) {
                $model = GeneralUtility::makeInstance(ObjectManager::class)->get(CategoryRepository::class)
                    ->findByUid((int)$identifier);
            } else {
                $record = $this->getRecordByImportIdentifier($identifier, 'sys_category'); // Default language record
                if ($record !== null) {
                    $model = MainUtility::convertRecordArrayToModel($record, Category::class);
                }
            }

            if (isset($model) && is_object($model)) {
                $entities[] = $model;
            } else {
                // @codingStandardsIgnoreStart
                throw new PostponeProcessorException('Category with id "' . $identifier . '" not found.', 1536148407513);
                // @codingStandardsIgnoreEnd
            }
        }

        return $entities;
    }
}
