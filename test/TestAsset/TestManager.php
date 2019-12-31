<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session\TestAsset;

use Laminas\EventManager\EventManagerInterface;
use Laminas\Session\AbstractManager;
use Laminas\Session\Configuration\ConfigurationInterface as SessionConfig;
use Laminas\Session\SaveHandler\SaveHandlerInterface as SessionSaveHandler;
use Laminas\Session\Storage\StorageInterface as SessionStorage;

class TestManager extends AbstractManager
{
    public $started = false;

    protected $configDefaultClass = 'Laminas\\Session\\Configuration\\StandardConfig';
    protected $storageDefaultClass = 'Laminas\\Session\\Storage\\ArrayStorage';

    public function start()
    {
        $this->started = true;
    }

    public function destroy()
    {
        $this->started = false;
    }

    public function stop()
    {}

    public function writeClose()
    {
        $this->started = false;
    }

    public function getName()
    {}

    public function setName($name)
    {}

    public function getId()
    {}

    public function setId($id)
    {}

    public function regenerateId()
    {}

    public function rememberMe($ttl = null)
    {}

    public function forgetMe()
    {}


    public function setValidatorChain(EventManagerInterface $chain)
    {}

    public function getValidatorChain()
    {}

    public function isValid()
    {}


    public function sessionExists()
    {}

    public function expireSessionCookie()
    {}
}
