<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Session\Validator;

/**
 * Session validator interface
 *
 * @category   Laminas
 * @package    Laminas_Session
 */
interface ValidatorInterface
{
    /**
     * This method will be called at the beginning of
     * every session to determine if the current environment matches
     * that which was store in the setup() procedure.
     *
     * @return boolean
     */
    public function isValid();

    /**
     * Get data from validator to be used for validation comparisons
     *
     * @return mixed
     */
    public function getData();

    /**
     * Get validator name for use with storing validators between requests
     *
     * @return string
     */
    public function getName();
}
