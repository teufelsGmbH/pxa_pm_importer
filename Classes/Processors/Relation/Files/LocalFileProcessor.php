<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Processors\Relation\Files;

use Pixelant\PxaPmImporter\Processors\Relation\AbstractRelationFieldProcessor;
use Pixelant\PxaPmImporter\Processors\Traits\FilesResources;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class LocalFileProcessor
 * @package Pixelant\PxaPmImporter\Processors\File
 */
class LocalFileProcessor extends AbstractRelationFieldProcessor
{
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

        /**
         * Find all existing file references attached to product
         * in order to reuse it if it was already imported
         */
        $existingFiles = [];
        /** @var FileReference $file */
        foreach (ObjectAccess::getProperty($this->entity, $this->property) as $file) {
            $existingFiles[$this->getEntityUidForCompare($file)] = $file;
        }

        foreach ($this->collectFilesFromList($folder, $value, $this->logger) as $file) {
            // Create new file reference
            if (!array_key_exists($file->getUid(), $existingFiles)) {
                $entities[] = $this->createFileReference(
                    $file,
                    $this->entity->getUid(),
                    $this->importer->getPid()
                );
            } else {
                // Use existing file reference
                $entities[] = $existingFiles[$file->getUid()];
            }
        }

        return $entities;
    }
}
