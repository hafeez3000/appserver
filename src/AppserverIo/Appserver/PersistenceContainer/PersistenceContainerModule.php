<?php

/**
 * AppserverIo\Appserver\PersistenceContainer\PersistenceContainerModule
 *
 * PHP version 5
 *
 * @category   Server
 * @package    Appserver
 * @subpackage Application
 * @author     Tim Wagner <tw@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.appserver.io
 */

namespace AppserverIo\Appserver\PersistenceContainer;

use AppserverIo\Storage\GenericStackable;
use AppserverIo\Appserver\ServletEngine\ServletEngine;
use AppserverIo\Server\Interfaces\ServerContextInterface;
use AppserverIo\Appserver\PersistenceContainer\PersistenceContainerValve;

/**
 * A persistence container module implementation.
 *
 * @category   Server
 * @package    Appserver
 * @subpackage Application
 * @author     Tim Wagner <tw@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.appserver.io
 */
class PersistenceContainerModule extends ServletEngine
{
    /**
     * The unique module name in the web server context.
     *
     * @var string
     */
    const MODULE_NAME = 'persistence-container';

    /**
     * Initialize the module.
     *
     * @return void
     */
    public function __construct()
    {

        // call parent constructor
        parent::__construct();

        // initialize the member variables
        $this->garbageCollectors = new GenericStackable();
    }

    /**
     * Returns the module name.
     *
     * @return string The module name
     */
    public function getModuleName()
    {
        return PersistenceContainerModule::MODULE_NAME;
    }

    /**
     * Initialize the valves that handles the requests.
     *
     * @return void
     */
    public function initValves()
    {
        $this->valves[] = new PersistenceContainerValve();
    }

    /**
     * Initializes the module.
     *
     * @param \AppserverIo\Server\Interfaces\ServerContextInterface $serverContext The servers context instance
     *
     * @return void
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function init(ServerContextInterface $serverContext)
    {

        // call parent init() method
        parent::init($serverContext);

        // add a garbage collector and timer service workers for each application
        foreach ($this->getApplications() as $application) {
            $this->garbageCollectors[] = new StandardGarbageCollector($application);
        }
    }
}
