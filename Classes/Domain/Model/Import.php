<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Domain\Model;

use Pixelant\PxaPmImporter\Service\Configuration\YamlConfiguration;
use Pixelant\PxaPmImporter\Service\Configuration\ConfigurationInterface;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Import
 */
class Import extends AbstractEntity
{
    /**
     * name
     *
     * @var string
     */
    protected $name = '';

    /**
     * configurationPath
     *
     * @var string
     */
    protected $configurationPath = '';

    /**
     * @var string
     */
    protected $localFilePath = '';

    /**
     * @var bool
     */
    protected $localConfiguration = false;

    /**
     * @var \DateTime|null
     */
    protected $lastExecution = null;

    /**
     * @var \DateTime
     */
    protected $crdate = null;

    /**
     * @var ConfigurationInterface
     */
    protected $configurationService = null;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getConfigurationPath(): string
    {
        return $this->configurationPath;
    }

    /**
     * @param string $configurationPath
     */
    public function setConfigurationPath(string $configurationPath): void
    {
        $this->configurationPath = $configurationPath;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastExecution(): ?\DateTime
    {
        return $this->lastExecution;
    }

    /**
     * @param \DateTime|null $lastExecution
     */
    public function setLastExecution(\DateTime $lastExecution): void
    {
        $this->lastExecution = $lastExecution;
    }

    /**
     * @return string
     */
    public function getLocalFilePath(): string
    {
        return $this->localFilePath;
    }

    /**
     * @param string $localFilePath
     */
    public function setLocalFilePath(string $localFilePath): void
    {
        $this->localFilePath = $localFilePath;
    }

    /**
     * @return bool
     */
    public function isLocalConfiguration(): bool
    {
        return $this->localConfiguration;
    }

    /**
     * @param bool $localConfiguration
     */
    public function setLocalConfiguration(bool $localConfiguration): void
    {
        $this->localConfiguration = $localConfiguration;
    }

    /**
     * @return \DateTime
     */
    public function getCrdate(): \DateTime
    {
        return $this->crdate;
    }

    /**
     * @return ConfigurationInterface
     */
    public function getConfigurationService(): ConfigurationInterface
    {
        // Initialize it here, when configurationPath is already mapped
        if ($this->configurationService === null) {
            $this->initializeConfiguration();
        }

        return $this->configurationService;
    }

    /**
     * Init configuration
     */
    protected function initializeConfiguration()
    {
        $this->configurationService = $this->getConfigurationInstance();
    }

    /**
     * Get configuration by type
     * @TODO currently only Yaml is supported
     *
     * @return string
     */
    protected function getConfigurationInstance(): ConfigurationInterface
    {
        return GeneralUtility::makeInstance(YamlConfiguration::class, $this->resolveConfigurationFilePath());
    }

    /**
     * Resolve absolute path to configuration file
     *
     * @return string
     * @throws \TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException
     * @throws \TYPO3\CMS\Core\LinkHandling\Exception\UnknownUrnException
     */
    protected function resolveConfigurationFilePath(): string
    {
        if ($this->isLocalConfiguration()) {
            $linkService = GeneralUtility::makeInstance(LinkService::class);
            $data = $linkService->resolveByStringRepresentation($this->getLocalFilePath());
            $file = $data['file'] ?: null;
            if (is_object($file) && $file instanceof File) {
                return $file->getForLocalProcessing(false);
            }
        } else {
            return GeneralUtility::getFileAbsFileName($this->configurationPath);
        }

        return '';
    }
}
