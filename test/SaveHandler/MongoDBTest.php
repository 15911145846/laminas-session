<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session\SaveHandler;

use Laminas\Session\SaveHandler\MongoDB;
use Laminas\Session\SaveHandler\MongoDBOptions;
use Mongo;

/**
 * @group      Laminas_Session
 * @covers Laminas\Session\SaveHandler\MongoDb
 */
class MongoDBTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Mongo|MongoClient
     */
    protected $mongo;

    /**
     * MongoCollection instance
     *
     * @var MongoCollection
     */
    protected $mongoCollection;

    /**
     * @var MongoDBOptions
     */
    protected $options;

    /**
     * Setup performed prior to each test method
     *
     * @return void
     */
    public function setUp()
    {
        if (!extension_loaded('mongo')) {
            $this->markTestSkipped('Laminas\Session\SaveHandler\MongoDB tests are not enabled due to missing Mongo extension');
        }

        $this->options = new MongoDBOptions([
            'database' => 'laminas_tests',
            'collection' => 'sessions',
        ]);

        $mongoClass = (version_compare(phpversion('mongo'), '1.3.0', '<')) ? '\Mongo' : '\MongoClient';

        $this->mongo = new $mongoClass();
        $this->mongoCollection = $this->mongo->selectCollection($this->options->getDatabase(), $this->options->getCollection());
    }

    /**
     * Tear-down operations performed after each test method
     *
     * @return void
     */
    public function tearDown()
    {
        if ($this->mongoCollection) {
            $this->mongoCollection->drop();
        }
    }

    public function testConstructorThrowsException()
    {
        $notMongo = new \stdClass();

        $this->setExpectedException(
            'InvalidArgumentException',
            'Parameter of type stdClass is invalid; must be MongoClient or Mongo'
        );

        $saveHandler = new MongoDB($notMongo, $this->options);
    }

    public function testReadWrite()
    {
        $saveHandler = new MongoDB($this->mongo, $this->options);
        $this->assertTrue($saveHandler->open('savepath', 'sessionname'));

        $id = '242';
        $data = ['foo' => 'bar', 'bar' => ['foo' => 'bar']];

        $this->assertTrue($saveHandler->write($id, serialize($data)));
        $this->assertEquals($data, unserialize($saveHandler->read($id)));

        $data = ['foo' => [1, 2, 3]];

        $this->assertTrue($saveHandler->write($id, serialize($data)));
        $this->assertEquals($data, unserialize($saveHandler->read($id)));
    }

    public function testReadDestroysExpiredSession()
    {
        /* Note: due to the session save handler's open() method reading the
         * "session.gc_maxlifetime" INI value directly, it's necessary to set
         * that to simulate natural session expiration.
         */
        $oldMaxlifetime = ini_get('session.gc_maxlifetime');
        ini_set('session.gc_maxlifetime', 0);

        $saveHandler = new MongoDB($this->mongo, $this->options);
        $this->assertTrue($saveHandler->open('savepath', 'sessionname'));

        $id = '242';
        $data = ['foo' => 'bar'];

        $this->assertNull($this->mongoCollection->findOne(['_id' => $id]));

        $this->assertTrue($saveHandler->write($id, serialize($data)));
        $this->assertNotNull($this->mongoCollection->findOne(['_id' => $id]));
        $this->assertEquals('', $saveHandler->read($id));
        $this->assertNull($this->mongoCollection->findOne(['_id' => $id]));

        ini_set('session.gc_maxlifetime', $oldMaxlifetime);
    }

    public function testGarbageCollection()
    {
        $saveHandler = new MongoDB($this->mongo, $this->options);
        $this->assertTrue($saveHandler->open('savepath', 'sessionname'));

        $data = ['foo' => 'bar'];

        $this->assertTrue($saveHandler->write(123, serialize($data)));
        $this->assertTrue($saveHandler->write(456, serialize($data)));
        $this->assertEquals(2, $this->mongoCollection->count());
        $saveHandler->gc(5);
        $this->assertEquals(2, $this->mongoCollection->count());

        /* Note: MongoDate uses micro-second precision, so even a maximum
         * lifetime of zero would not match records that were just inserted.
         * Use a negative number instead.
         */
        $saveHandler->gc(-1);
        $this->assertEquals(0, $this->mongoCollection->count());
    }

    /**
     * @expectedException MongoCursorException
     */
    public function testWriteExceptionEdgeCaseForChangedSessionName()
    {
        $saveHandler = new MongoDB($this->mongo, $this->options);
        $this->assertTrue($saveHandler->open('savepath', 'sessionname'));

        $id = '242';
        $data = ['foo' => 'bar'];

        /* Note: a MongoCursorException will be thrown if a record with this ID
         * already exists with a different session name, since the upsert query
         * cannot insert a new document with the same ID and new session name.
         * This should only happen if ID's are not unique or if the session name
         * is altered mid-process.
         */
        $saveHandler->write($id, serialize($data));
        $saveHandler->open('savepath', 'sessionname_changed');
        $saveHandler->write($id, serialize($data));
    }
}
