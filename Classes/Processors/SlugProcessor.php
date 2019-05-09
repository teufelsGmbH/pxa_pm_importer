<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use Pixelant\PxaPmImporter\Exception\Processors\SlugFieldNotFoundException;
use Pixelant\PxaPmImporter\Utility\MainUtility;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SlugProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class SlugProcessor extends AbstractFieldProcessor
{

    /**
     * Process slug import field
     *
     * @param $value
     */
    public function process($value): void
    {
        $slugField = $this->configuration['fieldName'] ?: $this->property;

        if (!array_key_exists($slugField, $this->dbRow)) {
            throw new SlugFieldNotFoundException("Could not find slug field with name '{$slugField}'", 1557407283555);
        }

        $table = MainUtility::getTableNameByModelName(get_class($this->entity));
        if (!array_key_exists($slugField, $GLOBALS['TCA'][$table]['columns'])) {
            throw new \Exception(
                "TCA configuration invalid for slug field '$slugField' and table '{$table}'",
                1557408220115
            );
        }
        $tcaFieldConf = $GLOBALS['TCA'][$table]['columns'][$slugField]['config'];

        $currentSlug = (string)$this->dbRow[$slugField];

        $helper = GeneralUtility::makeInstance(SlugHelper::class, $table, $slugField, $tcaFieldConf);
        // Generate a value if there is none, otherwise ensure that all characters are cleaned up
        if ($value === '') {
            $value = $helper->generate($this->dbRow, $this->importer->getPid());
        } else {
            $value = $helper->sanitize($value);
        }

        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($value,'Debug',16);
        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($currentSlug, 'Debug', 16);
        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($table, 'Debug', 16);
        die;
    }
}
