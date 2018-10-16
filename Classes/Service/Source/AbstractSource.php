<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Service\Source;

/**
 * Source simple abstract class to work with simple array
 *
 * @package Pixelant\PxaPmImporter\Service\Source
 */
abstract class AbstractSource implements SourceInterface
{
    /**
     * Source data
     * @var array
     */
    protected $data = [];

    /**
     * Rewind data
     */
    public function rewind(): void
    {
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
     * Set source data
     */
    abstract protected function setData(): void;
}
