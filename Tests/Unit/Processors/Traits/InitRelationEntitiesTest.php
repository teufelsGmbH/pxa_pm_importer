<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Processors\Relation\Traits;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Exception\FailedInitEntityException;
use Pixelant\PxaPmImporter\Processors\Traits\InitRelationEntities;

/**
 * Class InitRelationEntitiesTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Processors\Relation\Traits
 */
class InitRelationEntitiesTest extends UnitTestCase
{
    use InitRelationEntities;

    /**
     * @test
     */
    public function treatIdentifierAsUidReturnFalseIfNotSetInConfiguration()
    {
        $this->assertFalse($this->treatIdentifierAsUid());
    }

    /**
     * @test
     */
    public function treatIdentifierAsUidReturnTrueIfSetInConfiguration()
    {
        $this->configuration = ['treatIdentifierAsUid' => true];
        $this->assertTrue($this->treatIdentifierAsUid());
    }

    /**
     * @test
     */
    public function initEntitiesForTableThrownExceptionIfFailToFindEntity()
    {
        $this->configuration = ['treatIdentifierAsUid' => false];

        $this->expectException(FailedInitEntityException::class);

        $this->initEntitiesForTable('entity', 'DomainDummyClass');
    }

    /**
     * @test
     */
    public function initEntitiesForTableWillTryToCreateEntityIfMethodExist()
    {
        $mock = $this->getMockForTrait(
            'Pixelant\PxaPmImporter\Processors\Traits\InitRelationEntities',
            [],
            '',
            false,
            false,
            true,
            ['getRecordByImportIdentifier', 'getTableName']
        );

        $mock
            ->expects($this->atLeastOnce())
            ->method('getRecordByImportIdentifier')
            ->willReturn(null);

        $closureNewEntity = function ($identifier) {
            $this->assertEquals($identifier, 'entity');
        };

        $this->expectException(FailedInitEntityException::class);
        $this->callInaccessibleMethod($mock, 'initEntitiesForTable', 'entity', 'DomainDummyClass', $closureNewEntity);
    }

    /**
     * Fake method for trait
     *
     * @return null
     */
    protected function getRecordByImportIdentifier()
    {
        return null;
    }

    /**
     * Fake method for trait
     *
     * @return null
     */
    protected function getTableName(string $domainName)
    {
        return 'null';
    }
}
