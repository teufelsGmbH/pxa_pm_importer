<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Processors\Relation;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pixelant\PxaPmImporter\Processors\Relation\AbstractRelationFieldProcessor;
use Pixelant\PxaProductManager\Domain\Model\Category;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class AbstractRelationFieldProcessorTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Processors\Relation
 */
class AbstractRelationFieldProcessorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function preProcessWillCallInitEntities()
    {
        /** @var AbstractRelationFieldProcessor|MockObject $subject */
        $subject = $this
            ->getMockBuilder(AbstractRelationFieldProcessor::class)
            ->setMethods(['initEntities'])
            ->disableOriginalConstructor()
            ->getMock();

        $subject
            ->expects($this->once())
            ->method('initEntities');

        $value = '';
        $subject->preProcess($value);
    }

    /**
     * Update object storage will populate it with new items and remove ones that are not listed
     * @test
     */
    public function updateObjectStorage()
    {
        $newEntity = $this->createPartialMock(AbstractEntity::class, ['dummy']);
        $newEntity->_setProperty('uid', 12);

        $newEntityInStorage = $this->createPartialMock(AbstractEntity::class, ['dummy']);
        $newEntityInStorage->_setProperty('uid', 21);

        $entityInStorage = clone  $newEntityInStorage;
        $removeFromStorageEntity = $this->createPartialMock(AbstractEntity::class, ['dummy']);
        $removeFromStorageEntity->_setProperty('uid', 111);

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($entityInStorage);
        $objectStorage->attach($removeFromStorageEntity);

        $objectStorageExpect = new ObjectStorage();
        $objectStorageExpect->attach($newEntityInStorage);
        $objectStorageExpect->attach($newEntity);

        $subject = $this->getAccessibleMock(AbstractRelationFieldProcessor::class, ['initEntities']);

        $subject->_call('updateObjectStorage', $objectStorage, [$newEntity, $newEntityInStorage]);
        $this->assertEquals($objectStorageExpect->toArray(), $objectStorage->toArray());
    }

    /**
     * @test
     */
    public function updateRelationPropertyForObjectStorageCallUpdateObjectStorage()
    {
        $objectStorage = new ObjectStorage();

        $entity = $this->createPartialMock(AbstractEntity::class, ['getProperty']);
        $entity
            ->expects($this->atLeastOnce())
            ->method('getProperty')
            ->willReturn($objectStorage);

        $subject = $this->getAccessibleMock(AbstractRelationFieldProcessor::class, ['initEntities', 'updateObjectStorage']);
        $subject->_set('property', 'property');
        $subject->_set('entity', $entity);
        $subject
            ->expects($this->once())
            ->method('updateObjectStorage');

        $subject->_call('updateRelationProperty', []);
    }

    /**
     * @test
     */
    public function updateRelationPropertyForSingleEntityWillSetProperty()
    {
        $newEntity = new Category();
        $newEntity->_setProperty('uid', 222);

        $parent = new Category();
        $parent->_setProperty('uid', 333);
        $entity = new Category();
        $entity->_setProperty('uid', 111);
        $entity->_setProperty('parent', $parent);

        $subject = $this->getAccessibleMock(AbstractRelationFieldProcessor::class, ['initEntities', 'updateObjectStorage']);
        $subject->_set('property', 'parent');
        $subject->_set('entity', $entity);
        $subject
            ->expects($this->never())
            ->method('updateObjectStorage');

        $subject->_call('updateRelationProperty', [$newEntity]);

        $this->assertSame($entity->getParent(), $newEntity);
    }
}
