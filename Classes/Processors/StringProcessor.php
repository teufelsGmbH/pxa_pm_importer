<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use Pixelant\PxaPmImporter\Command\ImportCommand;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class StringProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class StringProcessor extends AbstractFieldProcessor
{
    /**
     * Set as string always
     *
     * @param $value
     */
    public function process($value): void
    {
        $this->simplePropertySet((string)$value);
    }
}
