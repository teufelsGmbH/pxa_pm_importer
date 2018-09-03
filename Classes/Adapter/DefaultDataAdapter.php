<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Adapter;


class DefaultDataAdapter implements AdapterInterface
{
    /**
     * @var array
     */
    protected $data = [];

    public function adapt(array $data, array $configuration): void
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Nothing here
     *
     * @param int $languageUid
     * @return array
     */
    public function getLocalizationData(int $languageUid): array
    {
        return [];
    }
}
