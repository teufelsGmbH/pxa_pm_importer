<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors;

use Pixelant\PxaPmImporter\Command\ImportCommandController;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class StringProcessor
 * @package Pixelant\PxaPmImporter\Processors
 */
class StringProcessor extends AbstractFieldProcessor
{
    /**
     * @param $value
     * @return bool
     */
    public function isValid($value): bool
    {
        if ($this->isRequired() && empty($value)) {
            $this->validationError = 'Property "' . $this->property . '" value is required';

            return false;
        }

        return true;
    }

    /**
     * Return as string always
     *
     * @param $value
     * @return string
     */
    public function postProcess($value): string
    {
        return (string)$value;
    }
}
