<?php

/**
 * TechDivision\PersistenceContainerClient\Context\ContextConnection
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category  Appserver
 * @package   TechDivision_PersistenceContainerClient
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */

namespace TechDivision\PersistenceContainerClient\Context;

use TechDivision\Socket\Client;
use TechDivision\PersistenceContainerClient\Context\ContextSession;
use TechDivision\PersistenceContainerClient\Interfaces\Connection;
use TechDivision\PersistenceContainerClient\Interfaces\RemoteMethod;

/**
 * Connection implementation to invoke a remote method call over a socket.
 *
 * @category   Appserver
 * @package    TechDivision_PersistenceContainerClient
 * @subpackage Context
 * @author     Tim Wagner <tw@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.appserver.io
 */
class ContextConnection implements Connection
{

    /**
     * The client socket's IP address.
     *
     * @var string
     */
    protected $address = '127.0.0.1';

    /**
     * The client socket's port.
     *
     * @var integer
     */
    protected $port = 8585;

    /**
     * The name of the webapp using this client connection.
     *
     * @var string
     */
    protected $appName;

    /**
     * The ArrayObject for the sessions.
     *
     * @var \ArrayObject
     */
    protected $sessions = null;

    /**
     * The client socket instance.
     *
     * @var \TechDivision\Socket\Client
     */
    protected $client = null;

    /**
     * Initializes the connection.
     *
     * @param string $appName Name of the webapp using this client connection
     */
    public function __construct($appName = '')
    {
        $this->appName = $appName;
        $this->sessions = new \ArrayObject();
    }

    /**
     * Close the sockets that will not be needed anymore.
     *
     * @return void
     */
    public function __destruct()
    {
    }

    /**
     * Creates the connection to the container.
     *
     * @return void
     * @see \TechDivision\PersistenceContainerClient\Interfaces\Connection::connect()
     */
    public function connect()
    {
        $client = new Client($this->getAddress(), $this->getPort());
        $this->setSocket($client->start()->setBlock());
    }

    /**
     * Shutdown the connection to the container.
     *
     * @return void
     * @see \TechDivision\PersistenceContainerClient\Interfaces\Connection::disconnect()
     */
    public function disconnect()
    {
    }

    /**
     * Sets the socket to use for the client connection, a Socket instance by default.
     *
     * @param \TechDivision\Socket\Client $socket The client socket
     *
     * @return \TechDivision\PersistenceContainerClient\Context\ContextConnection The instance itself
     */
    public function setSocket(Client $socket)
    {
        $this->socket = $socket;
        return $this;
    }

    /**
     * Returns the socket the connection is based on.
     *
     * @return \TechDivision\Socket\Client The socket instance
     * @see \TechDivision\PersistenceContainerClient\Interfaces\Connection::getSocket()
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * Set's the servers IP address for the client to connect to.
     *
     * @param string $address The servers IP address to connect to
     *
     * @return \TechDivision\PersistenceContainerClient\Context\ContextConnection The instance itself
     */
    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }

    /**
     * Returns the client sockets IP address.
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     *  Set's  the servers port for the client to connect to.
     *
     * @param integer $port The servers port to connect to
     *
     * @return void
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * Returns the client port.
     *
     * @return integer The client port
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Sets the client's webapp name
     *
     * @param string $appName Name of the webapp using this client connection
     *
     * @return void
     */
    public function setAppName($appName)
    {
        $this->appName = $appName;
    }

    /**
     * Returns the name of the webapp this connection is for
     *
     * @return string The webapp name
     */
    public function getAppName()
    {
        return $this->appName;
    }

    /**
     * Sends the remote method call to the container instance.
     *
     * @param \TechDivision\PersistenceContainerClient\Interfaces\RemoteMethod $remoteMethod The remote method instance
     *
     * @return mixed The response from the container
     * @see TechDivision\PersistenceContainerClient\Interfaces\Connection::send()
     */
    public function send(RemoteMethod $remoteMethod)
    {

        // load the socket instance
        $socket = $this->getSocket();

        // set address + port + appName
        $remoteMethod->setAddress($this->getAddress());
        $remoteMethod->setPort($this->getPort());
        $remoteMethod->setAppName($this->getAppName());

        // serialize the remote method and write it to the socket
        $socket->sendLine(serialize($remoteMethod));

        // read the response
        $serialized = $socket->readLine();

        // unserialize the response
        $response = unserialize($serialized);

        // if an exception returns, throw it again
        if ($response instanceof \Exception) {
            throw $response;
        }

        // return the data
        return $response;
    }

    /**
     * Initializes a new session instance.
     *
     * @return \TechDivision\PersistenceContainerClient\Interfaces\Session The session instance
     * @see \TechDivision\PersistenceContainerClient\Interfaces\Connection::createContextSession()
     */
    public function createContextSession()
    {
        return $this->sessions[] = $session = new ContextSession($this);
    }
}
