<?php

namespace unit\Helper;

use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Illuminate\Database\Eloquent\Model;
use ModelGenerator\Helper\MgHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MgHelperTest extends TestCase
{
    /**
     * @dataProvider fqcnProvider
     */
    public function testGetShortClassName(string $fqcn, string $expected): void
    {
        $this->assertEquals($expected, MgHelper::getShortClassName($fqcn));
    }

    public function fqcnProvider(): array
    {
        return [
            ['fqcn' => Model::class, 'expected' => 'Model'],
            ['fqcn' => 'Custom\Name', 'expected' => 'Name'],
            ['fqcn' => 'ShortName', 'expected' => 'ShortName'],
        ];
    }

    /**
     * @dataProvider classNameProvider
     */
    public function testGetTableNameByClassName(string $className, string $expected): void
    {
        $this->assertEquals($expected, MgHelper::getTableNameByClassName($className));
    }

    public function classNameProvider(): array
    {
        return [
            ['className' => 'User', 'expected' => 'users'],
            ['className' => 'ServiceAccount', 'expected' => 'service_accounts'],
            ['className' => 'Mouse', 'expected' => 'mice'],
            ['className' => 'D', 'expected' => 'ds'],
        ];
    }

    /**
     * @dataProvider tableNameToClassNameProvider
     */
    public function testGetClassNameByTableName(string $tableName, string $expected): void
    {
        $this->assertEquals($expected, MgHelper::getClassNameByTableName($tableName));
    }

    public function tableNameToClassNameProvider(): array
    {
        return [
            ['className' => 'users', 'expected' => 'User'],
            ['className' => 'service_accounts', 'expected' => 'ServiceAccount'],
            ['className' => 'mice', 'expected' => 'Mouse'],
            ['className' => 'ds', 'expected' => 'D'],
        ];
    }

    /**
     * @dataProvider tableNameToForeignColumnNameProvider
     */
    public function testGetDefaultForeignColumnName(string $tableName, string $expected): void
    {
        $this->assertEquals($expected, MgHelper::getDefaultForeignColumnName($tableName));
    }

    public function tableNameToForeignColumnNameProvider(): array
    {
        return [
            ['tableName' => 'organizations', 'expected' => 'organization_id'],
            ['tableName' => 'service_accounts', 'expected' => 'service_account_id'],
            ['tableName' => 'mice', 'expected' => 'mouse_id'],
        ];
    }

    /**
     * @dataProvider tableNamesProvider
     */
    public function testGetDefaultJoinTableName(string $tableNameOne, string $tableNameTwo, string $expected): void
    {
        $this->assertEquals($expected, MgHelper::getDefaultJoinTableName($tableNameOne, $tableNameTwo));
    }

    public function tableNamesProvider(): array
    {
        return [
            ['tableNameOne' => 'users', 'tableNameTwo' => 'roles', 'expected' => 'role_user'],
            ['tableNameOne' => 'roles', 'tableNameTwo' => 'users', 'expected' => 'role_user'],
            ['tableNameOne' => 'accounts', 'tableNameTwo' => 'profiles', 'expected' => 'account_profile'],
        ];
    }

    public function testIsColumnUnique(): void
    {
        $indexMock = $this->createMock(Index::class);
        $indexMock->expects($this->once())
            ->method('getColumns')
            ->willReturn(['column_0']);

        $indexMock->expects($this->once())
            ->method('isUnique')
            ->willReturn(true);

        $indexMocks = [$indexMock];
        $tableMock = $this->createMockTable($indexMocks);


        $this->assertTrue(MgHelper::isColumnUnique($tableMock, 'column_0'));
    }

    public function testIsColumnUniqueTwoIndexColumns(): void
    {
        $indexMock = $this->createMock(Index::class);
        $indexMock->expects($this->once())
            ->method('getColumns')
            ->willReturn(['column_0', 'column_1']);

        $indexMock->expects($this->never())
            ->method('isUnique');

        $indexMocks = [$indexMock];

        $tableMock = $this->createMockTable($indexMocks);


        $this->assertFalse(MgHelper::isColumnUnique($tableMock, 'column_0'));
    }
    private function createMockTable($indexMocks): MockObject|Table
    {
        $tableMock = $this->createMock(Table::class);
        $tableMock->expects($this->once())
            ->method('getIndexes')
            ->willReturn($indexMocks);
        return $tableMock;
    }
    public function testIsColumnUniqueIndexNotUnique(): void
    {
        $indexMock = $this->createMock(Index::class);
        $indexMock->expects($this->once())
            ->method('getColumns')
            ->willReturn(['column_0']);

        $indexMock->expects($this->once())
            ->method('isUnique')
            ->willReturn(false);

        $indexMocks = [$indexMock];
        $tableMock = $this->createMockTable($indexMocks);


        $this->assertFalse(MgHelper::isColumnUnique($tableMock, 'column_0'));
    }
}
