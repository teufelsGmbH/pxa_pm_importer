<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Source;

/**
 * Source simple abstract class to work with simple array
 *
 * @package Pixelant\PxaPmImporter\Source
 */
abstract class AbstractSource implements SourceInterface
{
    /**
     * Source data
     *
     * @var array
     */
    protected $data = null;

    /**
     * Rewind data
     */
    public function rewind(): void
    {
        if ($this->data === null) {
            $this->setData();
        }
        reset($this->data);
    }

    /**
     * Current key
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->data);
    }

    /**
     * Is valid
     *
     * @return bool
     */
    public function valid(): bool
    {
        return current($this->data) !== false;
    }

    /**
     * Next item
     */
    public function next(): void
    {
        next($this->data);
    }

    /**
     * Current item
     *
     * @return mixed
     */
    public function current()
    {
        $current = current($this->data);

        return $current;
    }

    /**
     * Count items
     *
     * @return int
     */
    public function count()
    {
        if ($this->data === null) {
            $this->setData();
        }
        return count($this->data);
    }

    /**
     * Set source data
     */
    abstract protected function setData(): void;
}
