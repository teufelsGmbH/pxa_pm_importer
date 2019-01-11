<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation\Files;

use Pixelant\PxaPmImporter\Processors\Relation\AbstractRelationFieldProcessor;
use Pixelant\PxaPmImporter\Processors\Traits\FilesResources;
use Pixelant\PxaPmImporter\Traits\EmitSignalTrait;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;

/**
 * Class LocalFileProcessor
 * @package Pixelant\PxaPmImporter\Processors\File
 */
class LocalFileProcessor extends AbstractRelationFieldProcessor
{
    use EmitSignalTrait;
    use FilesResources;

    /**
     * Get files from value list and convert to extbase domain file reference
     *
     * @param mixed $value
     * @return array
     */
    protected function initEntities($value): array
    {
        $entities = [];

        try {
            $folder = $this->getFolder();
        } catch (FolderDoesNotExistException $exception) {
            $this->addError($exception->getMessage());

            return [];
        }

        foreach ($this->collectFilesFromList($folder, $value, $this->logger) as $file) {
            $entities[] = $this->createFileReference(
                $file,
                $this->entity->getUid(),
                $this->importer->getPid()
            );
        }

        return $entities;
    }
}
