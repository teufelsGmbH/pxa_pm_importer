<?php

namespace Pixelant\PxaPmImporter\Traits;

/**
 * Use if you need to translate in BE
 * @package Pixelant\PxaProductManager\Traits
 */
trait TranslateBeTrait
{
    /**
     * Path to the local language file
     *
     * @var string
     */
    protected $llPath = 'LLL:EXT:pxa_pm_importer/Resources/Private/Language/locallang_be.xlf:';

    /**
     * Translate by key
     *
     * @param string $key
     * @param array $arguments
     * @return string
     */
    protected function translate(string $key, array $arguments = []): string
    {
        if (TYPO3_MODE === 'BE') {
            $label = $this->getLanguageService()->sL($this->llPath . $key) ?? '';

            if (!empty($arguments)) {
                $label = vsprintf($label, $arguments);
            }
        }

        return $label ?? '';
    }

    /**
     * Return language service instance
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
