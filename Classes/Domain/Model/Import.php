<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Domain\Model;

use Pixelant\PxaPmImporter\Exception\InvalidFrequencyException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Scheduler\CronCommand\CronCommand;

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
     * @var string
     */
    protected $frequency = '';

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
     * @var \DateTime|null
     */
    protected $nextExecution = null;

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
    public function getFrequency(): string
    {
        return $this->frequency;
    }

    /**
     * @param string $frequency
     */
    public function setFrequency(string $frequency): void
    {
        $this->frequency = $frequency;
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
     * @return \DateTime|null
     */
    public function getNextExecution(): ?\DateTime
    {
        $this->nextExecution;
    }

    /**
     * @param \DateTime|null $nextExecution
     */
    public function setNextExecution(\DateTime $nextExecution): void
    {
        $this->nextExecution = $nextExecution;
    }

    /**
     * Check if should execute only one time
     *
     * @return bool
     */
    public function isSingleTimeExecution(): bool
    {
        return $this->getFrequency() === '';
    }

    /**
     * Calculate next execution
     *
     * @return \DateTime
     * @throws InvalidFrequencyException
     */
    public function calculateNextExecutionTime(): \DateTime
    {
        try {
            /** @var $cronCmd CronCommand */
            $cronCmd = GeneralUtility::makeInstance(CronCommand::class, $this->getFrequency());
            $cronCmd->calculateNextValue();

            return new \DateTime('@' . $cronCmd->getTimestamp());
        } catch (\Exception $e) {
            $cronErrorMessage = $e->getMessage();

            if (is_numeric($this->getFrequency())) {
                $interval = (int)$this->getFrequency();

                if ($this->getLastExecution() === null) {
                    return new \DateTime('now-1minute');
                } else {
                    $dateTime = clone  $this->getLastExecution();
                    $dateTime->modify('+' . $interval . ' seconds');

                    return $dateTime;
                }
            }

            // @codingStandardsIgnoreStart
            throw new InvalidFrequencyException('Invalid frequency value "' . $this->getFrequency() . '"for "' . $this->getName() . '": ' . $cronErrorMessage, 1535707741899);
            // @codingStandardsIgnoreEnd
        }
    }
}
