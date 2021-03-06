<?php

/**
 * AppserverIo\Appserver\MessageQueue\QueueManager
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
use AppserverIo\Psr\Naming\NamingException;
use AppserverIo\Psr\MessageQueueProtocol\Queue;
use AppserverIo\Psr\MessageQueueProtocol\Message;
use AppserverIo\Psr\MessageQueueProtocol\QueueContext;
use AppserverIo\Psr\Application\ManagerInterface;
use AppserverIo\Psr\Application\ApplicationInterface;

/**
 * The queue manager handles the queues and message beans registered for the application.
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
class QueueManager extends GenericStackable implements QueueContext, ManagerInterface
{

    /**
     * Initializes the queue manager.
     *
     * @return void
     */
    public function __construct()
    {
        $this->webappPath = '';
    }

    /**
     * Injects the storage for the queues.
     *
     * @param \AppserverIo\Storage\GenericStackable $queues An storage for the queues
     *
     * @return void
     */
    public function injectQueues(GenericStackable $queues)
    {
        $this->queues = $queues;
    }

    /**
     * Injects the absolute path to the web application.
     *
     * @param string $webappPath The absolute path to this web application
     *
     * @return void
     */
    public function injectWebappPath($webappPath)
    {
        $this->webappPath = $webappPath;
    }

    /**
     * Injects the resource locator that locates the requested queue.
     *
     * @param \AppserverIo\Appserver\MessageQueue\ResourceLocator $resourceLocator The resource locator
     *
     * @return void
     */
    public function injectResourceLocator(ResourceLocator $resourceLocator)
    {
        $this->resourceLocator = $resourceLocator;
    }

    /**
     * Inject the application instance.
     *
     * @param \AppserverIo\Psr\Application\ApplicationInterface $application The application instance
     *
     * @return void
     */
    public function injectApplication(ApplicationInterface $application)
    {
        $this->application = $application;
    }

    /**
     * Has been automatically invoked by the container after the application
     * instance has been created.
     *
     * @param \AppserverIo\Psr\Application\ApplicationInterface $application The application instance
     *
     * @return void
     * @see \AppserverIo\Psr\Application\ManagerInterface::initialize()
     */
    public function initialize(ApplicationInterface $application)
    {
        $this->registerMessageQueues($application);
    }

    /**
     * Deploys the message queues.
     *
     * @param \AppserverIo\Psr\Application\ApplicationInterface $application The application instance
     *
     * @return void
     */
    protected function registerMessageQueues(ApplicationInterface $application)
    {

        // build up META-INF directory var
        $metaInfDir = $this->getWebappPath() . DIRECTORY_SEPARATOR .'META-INF';

        // check if we've found a valid directory
        if (is_dir($metaInfDir) === false) {
            return;
        }

        // check META-INF + subdirectories for XML files with MQ definitions
        $service = $application->newService('AppserverIo\Appserver\Core\Api\DeploymentService');
        $xmlFiles = $service->globDir($metaInfDir . DIRECTORY_SEPARATOR . '*.xml');

        // initialize the array for the creating the subdirectories
        $this->directories = new GenericStackable();
        $this->directories[] = $application;

        // gather all the deployed web applications
        foreach ($xmlFiles as $file) {

            try {

                // try to initialize a SimpleXMLElement
                $sxe = new \SimpleXMLElement($file, null, true);

                // lookup the MessageQueue's defined in the passed XML node
                if (($nodes = $sxe->xpath('/message-queues/message-queue')) === false) {
                    continue;
                }

                // iterate over all found queues and initialize them
                foreach ($nodes as $node) {

                    // load the nodes attributes
                    $attributes = $node->attributes();

                    // load destination queue and receiver type
                    $destination = (string) $node->destination;
                    $type = (string) $attributes['type'];

                    // create a new queue instance
                    $instance = MessageQueue::createQueue($destination, $type);

                    // register destination and receiver type
                    $this->queues[$instance->getName()] = $instance;

                    // prepare the naming diretory to bind the callbak to
                    $path = explode('/', $destination);

                    for ($i = 0; $i < sizeof($path) - 1; $i++) {
                        try {
                            $this->directories[$i]->search($path[$i]);
                        } catch (NamingException $ne) {
                            $this->directories[$i + 1] = $this->directories[$i]->createSubdirectory($path[$i]);
                        }
                    }

                    // bind the callback for creating a new MQ sender instance to the naming directory => necessary for DI provider
                    $application->bindCallback($destination, array(&$this, 'createSenderForQueue'), array($destination));
                }

            } catch (\Exception $e) { // if class can not be reflected continue with next class

                // log an error message
                $application->getInitialContext()->getSystemLogger()->error($e->__toString());

                // proceed with the nexet bean
                continue;
            }
        }
    }

    /**
     * Returns the array with queue names and the MessageReceiver class
     * names as values.
     *
     * @return array The registered queues
     */
    public function getQueues()
    {
        return $this->queues;
    }

    /**
     * Returns the absolute path to the web application.
     *
     * @return string The absolute path
     */
    public function getWebappPath()
    {
        return $this->webappPath;
    }

    /**
     * Return the resource locator instance.
     *
     * @return \AppserverIo\Appserver\MessageQueue\ResourceLocator The resource locator instance
     */
    public function getResourceLocator()
    {
        return $this->resourceLocator;
    }

    /**
     * Returns the application instance.
     *
     * @return string The application instance
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * Returns TRUE if the application is related with the
     * passed queue instance.
     *
     * @param \AppserverIo\Psr\MessageQueueProtocol\Queue $queue The queue the application has to be related to
     *
     * @return boolean TRUE if the application is related, else FALSE
     */
    public function hasQueue(Queue $queue)
    {
        return array_key_exists($queue->getName(), $this->getQueues());
    }

    /**
     * Tries to locate the queue that handles the request and returns the instance
     * if one can be found.
     *
     * @param \AppserverIo\Psr\MessageQueueProtocol\Queue $queue The queue request
     *
     * @return \AppserverIo\Psr\MessageQueueProtocol\Queue The requested queue instance
     */
    public function locate(Queue $queue)
    {
        return $this->getResourceLocator()->locate($this, $queue);
    }

    /**
     * Runs a lookup for the message queue with the passed class name and
     * session ID.
     *
     * @param string $lookupName The servlet path
     * @param string $sessionId  The session ID
     * @param array  $args       The arguments passed to the queue
     *
     * @return \AppserverIo\Psr\Servlet\GenericServlet The requested servlet
     * @todo Still to implement
     */
    public function lookup($lookupName, $sessionId = null, array $args = array())
    {
        // still to implement
    }

    /**
     * Return a new sender for the message queue with the passed lookup name.
     *
     * @param string $lookupName The lookup name of the queue to return a sender for
     * @param string $sessionId  The session-ID to be passed to the queue session
     *
     * @return \AppserverIo\MessageQueueClient\QueueSender The sender instance
     */
    public function createSenderForQueue($lookupName, $sessionId = null)
    {

        // load the application name
        $applicationName = $this->getApplication()->getName();

        // initialize and return the sender
        $queue = \AppserverIo\MessageQueueClient\MessageQueue::createQueue($lookupName);
        $connection = \AppserverIo\MessageQueueClient\QueueConnectionFactory::createQueueConnection($applicationName);
        $session = $connection->createQueueSession();
        return $session->createSender($queue);
    }

    /**
     * Updates the message monitor.
     *
     * @param \AppserverIo\Psr\MessageQueueProtocol\Message $message The message to update the monitor for
     *
     * @return void
     */
    public function updateMonitor(Message $message)
    {
        error_log('Update message monitor for message: ' . spl_object_hash($message));
    }

    /**
     * Initializes the manager instance.
     *
     * @return void
     * @see \AppserverIo\Psr\Application\ManagerInterface::initialize()
     */
    public function getIdentifier()
    {
        return QueueContext::IDENTIFIER;
    }

    /**
     * Returns the value with the passed name from the context.
     *
     * @param string $key The key of the value to return from the context.
     *
     * @return mixed The requested attribute
     */
    public function getAttribute($key)
    {
        throw new \Exception(sprintf('%s is not implemented yes', __METHOD__));
    }
}
