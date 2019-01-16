<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Processors\Relation\Traits;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaProductManager\Domain\Model\Category;
use Pixelant\PxaProductManager\Domain\Model\Product;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class UpdateRelationPropertyTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Processors\Relation\Traits
 */
class UpdateRelationPropertyTest extends UnitTestCase
{
    /**
     * @test
     */
    public function updateObjectStorageWontDoAnythingIfStorageIsSame()
    {
        $mock = $this->getMockForTrait(
            'Pixelant\PxaPmImporter\Processors\Traits\UpdateRelationProperty',
            [],
            '',
            true,
            true,
            true,
            ['doesStorageDiff']
        );
        $mock
            ->expects($this->once())
            ->method('doesStorageDiff')
            ->willReturn(false);

        $product = new Product();
        $relatedProduct1 = new Product();
        $relatedProduct1->_setProperty('uid', 12);

        $relatedProduct2 = new Product();
        $relatedProduct2->_setProperty('uid', 21);

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($relatedProduct1);
        $objectStorage->attach($relatedProduct2);

        $product->setRelatedProducts($objectStorage);

        $this->callInaccessibleMethod($mock, 'updateObjectStorage', $product, 'relatedProducts', $objectStorage, []);

        $this->assertEquals($objectStorage, $product->getRelatedProducts());
    }

    /**
     * @test
     */
    public function updateObjectStorageWillSetObjectsFromImportIfStorageIsDifferent()
    {
        $mock = $this->getMockForTrait(
            'Pixelant\PxaPmImporter\Processors\Traits\UpdateRelationProperty',
            [],
            '',
            true,
            true,
            true,
            ['doesStorageDiff']
        );
        $mock
            ->expects($this->once())
            ->method('doesStorageDiff')
            ->willReturn(true); // Different

        $product = new Product();
        $relatedProduct1 = new Product();
        $relatedProduct1->_setProperty('uid', 12);

        $relatedProduct2 = new Product();
        $relatedProduct2->_setProperty('uid', 21);

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($relatedProduct1);
        $objectStorage->attach($relatedProduct2);

        $product->setRelatedProducts($objectStorage);

        $relatedProduct3 = new Product();
        $relatedProduct3->_setProperty('uid', 121);

        $relatedProduct4 = new Product();
        $relatedProduct4->_setProperty('uid', 211);

        $import = [
            $relatedProduct3,
            $relatedProduct4
        ];

        $this->callInaccessibleMethod($mock, 'updateObjectStorage', $product, 'relatedProducts', $objectStorage, $import);

        $this->assertEquals($import, $product->getRelatedProducts()->toArray());
    }

    /**
     * @test
     */
    public function getEntityUidForCompareReturnUidOfEntityIfNotAFileReference()
    {
        $uid = 111122;
        $entity = new Product();
        $entity->_setProperty('uid', $uid);

        $mock = $this->getMockForTrait(
            'Pixelant\PxaPmImporter\Processors\Traits\UpdateRelationProperty'
        );

        $this->assertEquals($uid, $this->callInaccessibleMethod($mock, 'getEntityUidForCompare', $entity));
    }

    /**
     * @test
     */
    public function getEntityUidForCompareReturnUidOfFileUidIfAFileReference()
    {
        $uid = 3344;
        $fileData = [
            'name' => 'testfile',
            'identifier' => 'testIdentifier',
            'uid' => $uid
        ];
        $file = new File($fileData, $this->createMock(ResourceStorage::class));

        $originalResourceMock = $this
            ->getMockBuilder(FileReference::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();
        $this->inject($originalResourceMock, 'originalFile', $file);

        $fileReference = $this->getAccessibleMock(
            \TYPO3\CMS\Extbase\Domain\Model\FileReference::class,
            null,
            [],
            '',
            false
        );
        $fileReference->_set('originalResource', $originalResourceMock);

        $mock = $this->getMockForTrait(
            'Pixelant\PxaPmImporter\Processors\Traits\UpdateRelationProperty'
        );

        $this->assertEquals($uid, $this->callInaccessibleMethod($mock, 'getEntityUidForCompare', $fileReference));
    }

    /**
     * @test
     */
    public function updateRelationPropertyForObjectStorageCallUpdateObjectStorage()
    {
        $objectStorage = new ObjectStorage();

        $entity = $this->createPartialMock(Product::class, ['getProperty']);
        $entity
            ->expects($this->atLeastOnce())
            ->method('getProperty')
            ->willReturn($objectStorage);

        $mock = $this->getMockForTrait(
            'Pixelant\PxaPmImporter\Processors\Traits\UpdateRelationProperty',
            [],
            '',
            false,
            true,
            true,
            ['updateObjectStorage']
        );
        $mock
            ->expects($this->once())
            ->method('updateObjectStorage');

        $this->callInaccessibleMethod($mock, 'updateRelationProperty', $entity, 'property', []);
    }

    /**
     * @test
     */
    public function updateRelationPropertyForSingleEntityWillSetProperty()
    {
        $newParentEntity = new Category();
        $newParentEntity->_setProperty('uid', 222);

        $parent = new Category();
        $parent->_setProperty('uid', 333);
        $entity = new Category();
        $entity->_setProperty('uid', 111);
        $entity->_setProperty('parent', $parent);

        $mock = $this->getMockForTrait(
            'Pixelant\PxaPmImporter\Processors\Traits\UpdateRelationProperty',
            [],
            '',
            false,
            true,
            true,
            ['updateObjectStorage']
        );
        $mock
            ->expects($this->never())
            ->method('updateObjectStorage');

        $this->callInaccessibleMethod($mock, 'updateRelationProperty', $entity, 'parent', [$newParentEntity]);

        $this->assertSame($entity->getParent(), $newParentEntity);
    }

    /**
     * @test
     */
    public function updateRelationPropertyForSingleEntityWillSetPropertyWhenOriginalValueIsNull()
    {
        $newParentEntity = new Category();
        $newParentEntity->_setProperty('uid', 5433);

        $entity = new Category();
        $entity->_setProperty('uid', 1);
        $entity->_setProperty('parent', null);

        $mock = $this->getMockForTrait(
            'Pixelant\PxaPmImporter\Processors\Traits\UpdateRelationProperty',
            [],
            '',
            false,
            true,
            true,
            ['updateObjectStorage']
        );
        $mock
            ->expects($this->never())
            ->method('updateObjectStorage');

        $this->callInaccessibleMethod($mock, 'updateRelationProperty', $entity, 'parent', [$newParentEntity]);

        $this->assertSame($entity->getParent(), $newParentEntity);
    }
}
