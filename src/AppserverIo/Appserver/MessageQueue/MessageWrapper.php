<?php

/**
 * AppserverIo\Appserver\MessageQueue\MessageWrapper
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category   Server
 * @package    Appserver
 * @subpackage Application
 * @author     Tim Wagner <tw@appserver.io>
 * @author     Markus Stockbauer <ms@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/appserver
 * @link       http://www.appserver.io
 */

namespace AppserverIo\Appserver\MessageQueue;

use AppserverIo\Storage\GenericStackable;
use AppserverIo\Psr\MessageQueueProtocol\Queue;
use AppserverIo\Psr\MessageQueueProtocol\Message;
use AppserverIo\Psr\MessageQueueProtocol\Monitor;
use AppserverIo\Psr\MessageQueueProtocol\Utils\PriorityKeys;
use AppserverIo\Psr\MessageQueueProtocol\Utils\PriorityKey;
use AppserverIo\Psr\MessageQueueProtocol\Utils\PriorityLow;
use AppserverIo\Psr\MessageQueueProtocol\Utils\MQStateKeys;
use AppserverIo\Psr\MessageQueueProtocol\Utils\MQStateKey;
use AppserverIo\Psr\MessageQueueProtocol\Utils\MQStateActive;

/**
 * This is a simple stackable wrapper for a message.
 *
 * @category   Server
 * @package    Appserver
 * @subpackage Application
 * @author     Tim Wagner <tw@appserver.io>
 * @author     Markus Stockbauer <ms@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/appserver
 * @link       http://www.appserver.io
 */
class MessageWrapper extends GenericStackable implements Message
{

    /**
     * Initializes the instance.
     */
    public function __construct()
    {

        // initialize the members
        $this->messageId = null;
        $this->message = null;
        $this->sessionId = '';
        $this->destination = null;
        $this->parentMessage = null;
        $this->messageMonitor = null;
        $this->priority = PriorityLow::KEY;
        $this->state = MQStateActive::KEY;
    }

    /**
     * Creates a new and empty wrapper instance.
     *
     * @return \AppserverIo\Psr\MessageQueueProtocol\Message The empty message wrapper instance
     */
    public static function emptyInstance()
    {
        return new MessageWrapper();
    }

    /**
     * Initializes the wrapper with the real message
     *
     * @param \AppserverIo\Psr\MessageQueueProtocol\Message $message The message we want to wrap
     *
     * @return void
     */
    public function init(Message $message)
    {
        $this->messageId = $message->getMessageId();
        $this->message = $message->getMessage();
        $this->sessionId = $message->getSessionId();
        $this->messageMonitor = $message->getMessageMonitor();
        $this->priority = $message->getPriority()->getPriority();
        $this->state = $message->getState()->getState();
        $this->destination = $message->getDestination();
        $this->sessionId = $message->getSessionId();
    }

    /**
     * Returns the message id.
     *
     * @return string The message id as hash value
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * The message itself.
     *
     * @return array The message itself
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Returns the message as string.
     *
     * @return string The message as string
     */
    public function __toString()
    {
        return serialize($this->message);
    }

    /**
     * Sets the unique session id.
     *
     * @param string $sessionId The uniquid id
     *
     * @return void
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * Returns the unique session id.
     *
     * @return string The uniquid id
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Sets the destination Queue.
     *
     * @param \AppserverIo\Psr\MessageQueueProtocol\Queue $destination The destination
     *
     * @return void
     */
    public function setDestination(Queue $destination)
    {
        $this->destination = $destination;
    }

    /**
     * Returns the destination Queue.
     *
     * @return \AppserverIo\Psr\MessageQueueProtocol\Queue The destination Queue
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * Sets the priority of the message.
     *
     * @param \AppserverIo\Psr\MessageQueueProtocol\Utils\PriorityKey $priority The priority to set the message to
     *
     * @return void
     */
    public function setPriority(PriorityKey $priority)
    {
        $this->priority = $priority->getPriority();
    }

    /**
     * Returns the priority of the message.
     *
     * @return \AppserverIo\Psr\MessageQueueProtocol\Utils\PriorityKey The priority of the message
     */
    public function getPriority()
    {
        return PriorityKeys::get($this->priority);
    }

    /**
     * Sets the state of the message.
     *
     * @param \AppserverIo\Psr\MessageQueueProtocol\Utils\MQStateKey $state The new state
     *
     * @return void
     */
    public function setState(MQStateKey $state)
    {
        $this->state = $state->getState();
    }

    /**
     * Returns the state of the message.
     *
     * @return \AppserverIo\Psr\MessageQueueProtocol\Utils\MQStateKey The message state
     */
    public function getState()
    {
        return MQStateKeys::get($this->state);
    }

    /**
     * Sets the parent message.
     *
     * @param \AppserverIo\Psr\MessageQueueProtocol\Message $parentMessage The parent message
     *
     * @return void
     */
    public function setParentMessage(Message $parentMessage)
    {
        $this->parentMessage = $parentMessage;
    }

    /**
     * Returns the parent message.
     *
     * @return \AppserverIo\Psr\MessageQueueProtocol\Message The parent message
     *
     * @see \AppserverIo\Psr\MessageQueueProtocol\Message::getParentMessage()
     */
    public function getParentMessage()
    {
        return $this->parentMessage;
    }

    /**
     * Sets the monitor for monitoring the message itself.
     *
     * @param \AppserverIo\Psr\MessageQueueProtocol\Monitor $messageMonitor The monitor
     *
     * @return void
     */
    public function setMessageMonitor(Monitor $messageMonitor)
    {
        $this->messageMonitor = $messageMonitor;
    }

    /**
     * Returns the message monitor.
     *
     * @return \AppserverIo\Psr\MessageQueueProtocol\Monitor The monitor
     *
     * @see \AppserverIo\Appserver\MessageQueueProtocol::getMessageMonitor()
     */
    public function getMessageMonitor()
    {
        return $this->messageMonitor;
    }
}
