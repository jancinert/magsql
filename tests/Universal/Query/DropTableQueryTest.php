<?php
use Magsql\Universal\Query\CreateTableQuery;
use Magsql\Universal\Query\DropTableQuery;
use Magsql\Testing\PDOQueryTestCase;
use Magsql\Driver\MySQLDriver;
use Magsql\Driver\PgSQLDriver;
use Magsql\Raw;

class DropTableQueryTest extends PDOQueryTestCase
{
    public $driverType = 'MySQL';

    // public $schema = array( 'tests/schema/member_mysql.sql' );

    public function createDriver() {
        return new MySQLDriver;
    }

    public function setUp()
    {
        parent::setUp();

        // Clean up
        foreach(array('groups','users','points') as $table) {
            $dropQuery = new DropTableQuery($table);
            $dropQuery->IfExists();
            $this->assertQuery($dropQuery);
        }
    }

    public function tearDown()
    {
        foreach(array('groups','users', 'points') as $table) {
            $dropQuery = new DropTableQuery($table);
            $dropQuery->IfExists();
            $this->assertQuery($dropQuery);
        }
    }

    public function testDropTemporaryTable()
    {
        $q = new CreateTableQuery('points');
        $q->temporary();
        $q->column('x')->float(10,2);
        $q->column('y')->float(10,2);
        $this->assertQuery($q);

        $q = new DropTableQuery('points');
        $q->temporary();
        $this->assertSql('DROP TEMPORARY TABLE `points`', $q);
        $this->assertQuery($q);
    }

    public function testDropTable() 
    {
        $q = new CreateTableQuery('points');
        $q->column('x')->float(10,2);
        $q->column('y')->float(10,2);
        $this->assertQuery($q);

        $q = new DropTableQuery('points');
        $q->drop('users');
        $q->drop('books');
        $q->ifExists();
        $this->assertQuery($q);
    }

    public function testDropMultipleTable() 
    {
        $q = new CreateTableQuery('points');
        $q->column('id')->int();
        $this->assertQuery($q);

        $q = new CreateTableQuery('users');
        $q->column('id')->int();
        $this->assertQuery($q);

        $q = new DropTableQuery(['points','users']);
        $this->assertQuery($q);
    }
}
