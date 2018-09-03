<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Domain\Model;

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
     * @var \DateTime|null
     */
    protected $lastExecution = null;

    /**
     * @var \DateTime
     */
    protected $crdate = null;

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
     * @return \DateTime
     */
    public function getCrdate(): \DateTime
    {
        return $this->crdate;
    }
}
