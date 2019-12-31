<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session;

use Laminas\Session\Container;
use Laminas\Session\Storage\SessionArrayStorage;

/**
 * @category   Laminas
 * @package    Laminas_Session
 * @subpackage UnitTests
 * @group      Laminas_Session
 */
class SessionArrayStorageTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SESSION = array();
        $this->storage = new SessionArrayStorage;
    }

    public function tearDown()
    {
        $_SESSION = array();
    }

    public function testStorageWritesToSessionSuperglobal()
    {
        $this->storage['foo'] = 'bar';
        $this->assertSame($_SESSION['foo'], $this->storage->foo);
        unset($this->storage['foo']);
        $this->assertFalse(array_key_exists('foo', $_SESSION));
    }

    public function testPassingArrayToConstructorOverwritesSessionSuperglobal()
    {
        $_SESSION['foo'] = 'bar';
        $array   = array('foo' => 'FOO');
        $storage = new SessionArrayStorage($array);
        $expected = array(
            'foo' => 'FOO',
            '__Laminas' => array(
                '_REQUEST_ACCESS_TIME' => $storage->getRequestAccessTime(),
            ),
        );
        $this->assertSame($expected, $_SESSION);
    }

    public function testModifyingSessionSuperglobalDirectlyUpdatesStorage()
    {
        $_SESSION['foo'] = 'bar';
        $this->assertTrue(isset($this->storage['foo']));
    }

    public function testDestructorSetsSessionToArray()
    {
        $this->storage->foo = 'bar';
        $expected = array(
            '__Laminas' => array(
                '_REQUEST_ACCESS_TIME' => $this->storage->getRequestAccessTime(),
            ),
            'foo' => 'bar',
        );
        $this->storage->__destruct();
        $this->assertSame($expected, $_SESSION);
    }

    public function testModifyingOneSessionObjectModifiesTheOther()
    {
        $this->storage->foo = 'bar';
        $storage = new SessionArrayStorage();
        $storage->bar = 'foo';
        $this->assertEquals('foo', $this->storage->bar);
    }

    public function testMarkingOneSessionObjectImmutableShouldMarkOtherInstancesImmutable()
    {
        $this->storage->foo = 'bar';
        $storage = new SessionArrayStorage();
        $this->assertEquals('bar', $storage['foo']);
        $this->storage->markImmutable();
        $this->assertTrue($storage->isImmutable(), var_export($_SESSION, 1));
    }

    public function testAssignment()
    {
        $_SESSION['foo'] = 'bar';
        $this->assertEquals('bar', $this->storage['foo']);
    }

    public function testMultiDimensionalAssignment()
    {
        $_SESSION['foo']['bar'] = 'baz';
        $this->assertEquals('baz', $this->storage['foo']['bar']);
    }

    public function testUnset()
    {
        $_SESSION['foo'] = 'bar';
        unset($_SESSION['foo']);
        $this->assertFalse(isset($this->storage['foo']));
    }

    public function testMultiDimensionalUnset()
    {
        $_SESSION['foo']['bar'] = 'baz';
        unset($_SESSION['foo']['bar']);
        $this->assertFalse(isset($this->storage['foo']['bar']));
    }

    public function testSessionWorksWithContainer()
    {
        $container = new Container('test');
        $container->foo = 'bar';

        $this->assertSame($container->foo, $_SESSION['test']['foo']);

    }

}
