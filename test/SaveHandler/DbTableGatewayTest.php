<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session\SaveHandler;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Session\SaveHandler\DbTableGateway;
use Laminas\Session\SaveHandler\DbTableGatewayOptions;

/**
 * Unit testing for DbTableGateway include all tests for
 * regular session handling
 *
 * @group      Laminas_Session
 * @group      Laminas_Db_Table
 */
class DbTableGatewayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Adapter
     */
    protected $adapter;

    /**
     * @var TableGateway
     */
    protected $tableGateway;

    /**
     * @var DbTableGatewayOptions
     */
    protected $options;

    /**
     * Array to collect used DbTableGateway objects, so they are not
     * destroyed before all tests are done and session is not closed
     *
     * @var array
     */
    protected $usedSaveHandlers = array();

    /**
     * Setup performed prior to each test method
     *
     * @return void
     */
    public function setUp()
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Laminas\Session\SaveHandler\DbTableGateway tests are not enabled due to missing PDO_Sqlite extension');
        }

        $this->options = new DbTableGatewayOptions(array(
            'nameColumn' => 'name',
            'idColumn'   => 'id',
            'dataColumn' => 'data',
            'modifiedColumn' => 'modified',
            'lifetimeColumn' => 'lifetime',
        ));

        $this->setupDb($this->options);
        $this->testArray = array('foo' => 'bar', 'bar' => array('foo' => 'bar'));
    }

    /**
     * Tear-down operations performed after each test method
     *
     * @return void
     */
    public function tearDown()
    {
        if ($this->adapter) {
            $this->dropTable();
        }
    }

    public function testReadWrite()
    {
        $this->usedSaveHandlers[] = $saveHandler = new DbTableGateway($this->tableGateway, $this->options);
        $saveHandler->open('savepath', 'sessionname');

        $id = '242';

        $this->assertTrue($saveHandler->write($id, serialize($this->testArray)));

        $data = unserialize($saveHandler->read($id));
        $this->assertEquals($this->testArray, $data, 'Expected ' . var_export($this->testArray, 1) . "\nbut got: " . var_export($data, 1));
    }

    public function testReadWriteComplex()
    {
        $this->usedSaveHandlers[] = $saveHandler = new DbTableGateway($this->tableGateway, $this->options);
        $saveHandler->open('savepath', 'sessionname');

        $id = '242';

        $this->assertTrue($saveHandler->write($id, serialize($this->testArray)));

        $this->assertEquals($this->testArray, unserialize($saveHandler->read($id)));
    }

    public function testReadWriteTwice()
    {
        $this->usedSaveHandlers[] = $saveHandler = new DbTableGateway($this->tableGateway, $this->options);
        $saveHandler->open('savepath', 'sessionname');

        $id = '242';

        $this->assertTrue($saveHandler->write($id, serialize($this->testArray)));

        $this->assertEquals($this->testArray, unserialize($saveHandler->read($id)));

        $this->assertTrue($saveHandler->write($id, serialize($this->testArray)));

        $this->assertEquals($this->testArray, unserialize($saveHandler->read($id)));
    }

    /**
     * Sets up the database connection and creates the table for session data
     *
     * @param  Laminas\Session\SaveHandler\DbTableGatewayOptions $options
     * @return void
     */
    protected function setupDb(DbTableGatewayOptions $options)
    {
        $this->adapter = new Adapter(array(
            'driver' => 'pdo_sqlite',
            'database' => ':memory:',
        ));


        $query = <<<EOD
CREATE TABLE `sessions` (
    `{$options->getIdColumn()}` text NOT NULL,
    `{$options->getNameColumn()}` text NOT NULL,
    `{$options->getModifiedColumn()}` int(11) default NULL,
    `{$options->getLifetimeColumn()}` int(11) default NULL,
    `{$options->getDataColumn()}` text,
    PRIMARY KEY (`{$options->getIdColumn()}`, `{$options->getNameColumn()}`)
);
EOD;
        $this->adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
        $this->tableGateway = new TableGateway('sessions', $this->adapter);
    }

    /**
     * Drops the database table for session data
     *
     * @return void
     */
    protected function dropTable()
    {
        if (!$this->adapter) {
            return;
        }
        $this->adapter->query('DROP TABLE sessions', Adapter::QUERY_MODE_EXECUTE);
    }
}
