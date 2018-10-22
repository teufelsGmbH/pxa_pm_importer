<?php
declare(strict_types=1);

namespace Pixelant\PxaPmImporter\Tests\Unit\Processors\Helpers;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pixelant\PxaPmImporter\Processors\Helpers\BulkInsertHelper;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class BulkInsertHelperTest
 * @package Pixelant\PxaPmImporter\Tests\Unit\Processors\Helpers
 */
class BulkInsertHelperTest extends UnitTestCase
{
    /**
     * @test
     */
    public function bulkInsertInstanceOfSingleton()
    {
        $subject = new BulkInsertHelper();

        $this->assertTrue($subject instanceof SingletonInterface);
    }

    /**
     * @test
     */
    public function addRowWillAddRowAndFields()
    {
        $subject = new BulkInsertHelper();
        $tableName = 'sys_test';
        $row = ['test' => 123, 'value' => 'test'];

        $expectRows = [
            $tableName => [$row]
        ];
        $expectFields = [
            $tableName => ['test', 'value']
        ];
        $subject->addRow($tableName, $row);

        $this->assertEquals($expectRows, $this->readAttribute($subject, 'insertRows'));
        $this->assertEquals($expectFields, $this->readAttribute($subject, 'insertFields'));
    }

    /**
     * @test
     */
    public function addRowsWillAddMultipleRows()
    {
        $subject = new BulkInsertHelper();
        $tableName = 'sys_test2';
        $row = ['field1' => 123, 'field2' => 'test'];
        $row2 = ['field1' => 111, 'field2' => 'test 2'];

        $expectRows = [
            $tableName => [$row, $row2]
        ];
        $expectFields = [
            $tableName => ['field1', 'field2']
        ];
        $subject->addRows($tableName, [$row, $row2]);

        $this->assertEquals($expectRows, $this->readAttribute($subject, 'insertRows'));
        $this->assertEquals($expectFields, $this->readAttribute($subject, 'insertFields'));
    }

    /**
     * @test
     */
    public function setTypesWillSetTypes()
    {
        $subject = new BulkInsertHelper();
        $tableName = 'sys_table';
        $fields = [
            \PDO::PARAM_INT,
            \PDO::PARAM_STR
        ];

        $subject->setTypes($tableName, $fields);
        $expect = [
            $tableName => $fields
        ];

        $this->assertEquals($expect, $this->readAttribute($subject, 'types'));
    }

    /**
     * @test
     */
    public function persistBulkInsertThrowsExceptionIfInsertRowsNotSet()
    {
        $subject = new BulkInsertHelper();
        $tableName = 'sys_table';

        $this->expectException(\UnexpectedValueException::class);

        $subject->persistBulkInsert($tableName);
    }

    /**
     * @test
     */
    public function flushTableWillFlushTableData()
    {
        $subject = new BulkInsertHelper();
        $tableName = 'sys_test2';
        $row = ['field1' => 123, 'field2' => 'test'];
        $row2 = ['field1' => 111, 'field2' => 'test 2'];

        $fields = [
            \PDO::PARAM_INT,
            \PDO::PARAM_STR
        ];

        $subject->setTypes($tableName, $fields);
        $subject->addRows($tableName, [$row, $row2]);

        $subject->flushTable($tableName);

        $this->assertEmpty($this->readAttribute($subject, 'types'));
        $this->assertEmpty($this->readAttribute($subject, 'insertRows'));
        $this->assertEmpty($this->readAttribute($subject, 'insertFields'));
    }

    /**
     * @test
     */
    public function flushAllWillFlushEverything()
    {
        $subject = new BulkInsertHelper();
        $tableName = 'sys_test2';
        $tableName2 = 'sys_table';

        $row = ['field1' => 123, 'field2' => 'test'];
        $row2 = ['field1' => 111, 'field2' => 'test 2'];

        $fields = [
            \PDO::PARAM_INT,
            \PDO::PARAM_STR
        ];

        $subject->setTypes($tableName, $fields);
        $subject->addRows($tableName, [$row, $row2]);

        $subject->setTypes($tableName2, $fields);
        $subject->addRows($tableName2, [$row, $row2]);

        $subject->flushAll();

        $this->assertEmpty($this->readAttribute($subject, 'types'));
        $this->assertEmpty($this->readAttribute($subject, 'insertRows'));
        $this->assertEmpty($this->readAttribute($subject, 'insertFields'));
    }

    /**
     * @test
     */
    public function hasTableDataReturnFalseIfNoData()
    {
        $subject = new BulkInsertHelper();
        $tableName = 'sys_test';
        
        $this->assertFalse($subject->hasTableData($tableName));
    }

    /**
     * @test
     */
    public function hasTableDataReturnTrueIfDataFound()
    {
        $subject = new BulkInsertHelper();
        $tableName = 'sys_test';

        $row = ['field1' => 123, 'field2' => 'test'];

        $subject->addRow($tableName, $row);

        $this->assertTrue($subject->hasTableData($tableName));
    }
}
