<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\Registry\Registry;
use \Joomla\CMS\MVC\Model\ListModel;
use \Joomla\CMS\User\CurrentUserInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessInterface;

/**
 * Base model class for JoomGallery list of items
 *
 * @package JoomGallery
 * @since   4.0.0
 */
abstract class JoomListModel extends ListModel
{
  /**
   * Joomla application class
   *
   * @access  protected
   * @var     Joomla\CMS\Application\AdministratorApplication
   */
  protected $app;

  /**
   * Joomla user object
   *
   * @access  protected
   * @var     Joomla\CMS\User\User
   */
  protected $user;

  /**
   * JoomGallery extension class
   *
   * @access  protected
   * @var     Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
   */
  protected $component;

  /**
   * JoomGallery access service
   *
   * @access  protected
   * @var     Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessInterface
   */
  protected $acl = null;

  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'image';

  /**
   * Constructor
   * 
   * @param   array  $config  An optional associative array of configuration settings.
   *
   * @return  void
   * @since   4.0.0
   */
  function __construct($config = array())
  {
    parent::__construct($config);

    $this->app       = Factory::getApplication('administrator');
    $this->component = $this->app->bootComponent(_JOOM_OPTION);
    $this->user      = $this->component->getMVCFactory()->getIdentity();
  }

  /**
	 * Method to get parameters from model state.
	 *
	 * @return  Registry[]   List of parameters
   * @since   4.0.0
	 */
	public function getParams(): array
	{
		$params = array('component' => $this->getState('parameters.component'),
										'menu'      => $this->getState('parameters.menu'),
									  'configs'   => $this->getState('parameters.configs')
									);

		return $params;
	}

	/**
	 * Method to get the access service class.
	 *
	 * @return  AccessInterface   Object on success, false on failure.
   * @since   4.0.0
	 */
	public function getAcl(): AccessInterface
	{
    // Create access service
    if(\is_null($this->acl))
    {
      $this->component->createAccess();
      $this->acl = $this->component->getAccess();
    }

		return $this->acl;
	}

  /**
	 * Method to load component specific parameters into model state.
	 *
	 * @return  void
   * @since   4.0.0
	 */
  protected function loadComponentParams()
  {
    // Load the componen parameters.
		$params       = Factory::getApplication('com_joomgallery')->getParams();
		$params_array = $params->toArray();

		if(isset($params_array['item_id']))
		{
			$this->setState($this->type.'.id', $params_array['item_id']);
		}

		$this->setState('parameters.component', $params);

		// Load the configs from config service
		$this->component->createConfig('com_joomgallery');
		$configArray = $this->component->getConfig()->getProperties();
		$configs     = new Registry($configArray);

		$this->setState('parameters.configs', $configs);
  }

  /**
   * Method to load and return a table object.
   *
   * @param   string  $name    The name of the view
   * @param   string  $prefix  The class prefix. Optional.
   * @param   array   $config  Configuration settings to pass to Table::getInstance
   *
   * @return  Table|boolean  Table object or boolean false if failed
   *
   * @since   4.0.0
   */
  protected function _createTable($name, $prefix = 'Table', $config = [])
  {
    $table = parent::_createTable($name, $prefix, $config);

    if($table instanceof CurrentUserInterface)
    {
      $table->setCurrentUser($this->component->getMVCFactory()->getIdentity());
    }

    return $table;
  }

  /**
     * Method to get an array of data items.
     *
     * @return  mixed  An array of data items on success, false on failure.
     *
     * @since   1.6
     */
    public function getItems()
    {
        // Get a storage key.
        $store = $this->getStoreId();

        // Try to load the data from internal storage.
        if (isset($this->cache[$store])) {
            return $this->cache[$store];
        }

        try {
            // Load the list items and add the items to the internal cache.
            $this->cache[$store] = $this->_getList($this->_getListQuery(), $this->getStart(), $this->getState('list.limit'));
        } catch (\RuntimeException $e) {
            $this->setError($e->getMessage());

            return false;
        }

        return $this->cache[$store];
    }

    /**
     * Method to get a \JPagination object for the data set.
     *
     * @return  Pagination  A Pagination object for the data set.
     *
     * @since   1.6
     */
    public function getPagination()
    {
        // Get a storage key.
        $store = $this->getStoreId('getPagination');

        // Try to load the data from internal storage.
        if (isset($this->cache[$store])) {
            return $this->cache[$store];
        }

        $limit = (int) $this->getState('list.limit') - (int) $this->getState('list.links');

        $total = $this->getTotal();
        $this->component->addLog('[' . STOPWATCH_ID . '] Pagination getTotal(): ' . \strval(microtime(true) - STOPWATCH_START), 128, 'stopwatch');

        $start = $this->getStart();
        $this->component->addLog('[' . STOPWATCH_ID . '] Pagination getStart(): ' . \strval(microtime(true) - STOPWATCH_START), 128, 'stopwatch');

        // Create the pagination object and add the object to the internal cache.
        $this->cache[$store] = new Pagination($total, $start, $limit);
        $this->component->addLog('[' . STOPWATCH_ID . '] Initialise pagination: ' . \strval(microtime(true) - STOPWATCH_START), 128, 'stopwatch');

        return $this->cache[$store];
    }

    /**
     * Returns a record count for the query.
     *
     * Note: Current implementation of this method assumes that getListQuery() returns a set of unique rows,
     * thus it uses SELECT COUNT(*) to count the rows. In cases that getListQuery() uses DISTINCT
     * then either this method must be overridden by a custom implementation at the derived Model Class
     * or a GROUP BY clause should be used to make the set unique.
     *
     * @param   DatabaseQuery|string  $query  The query.
     *
     * @return  integer  Number of rows for query.
     *
     * @since   3.0
     */
    protected function _getListCount($query)
    {
        // Use fast COUNT(*) on DatabaseQuery objects if there is no GROUP BY or HAVING clause:
        if (
            $query instanceof DatabaseQuery
            && $query->type === 'select'
            && $query->group === null
            && $query->merge === null
            && $query->querySet === null
            && $query->having === null
        ) {
            $query = clone $query;
            $query->clear('select')->clear('order')->clear('limit')->clear('offset')->select('COUNT(*)');

            $this->component->addLog('[' . STOPWATCH_ID . '] Use COUNT(*) on DatabaseQuery objects.', 128, 'stopwatch');
            $this->component->addLog('[' . STOPWATCH_ID . '] Query: ' . $query->__toString(), 128, 'stopwatch');

            $this->getDatabase()->setQuery($query);

            $res = (int) $this->getDatabase()->loadResult();

            $this->component->addLog('[' . STOPWATCH_ID . '] ListCount-Query executed: ' . \strval(microtime(true) - STOPWATCH_START), 128, 'stopwatch');

            return $res;
        }

        // Otherwise fall back to inefficient way of counting all results.

        // Remove the limit, offset and order parts if it's a DatabaseQuery object
        if ($query instanceof DatabaseQuery) {
            $query = clone $query;
            $query->clear('limit')->clear('offset')->clear('order');
        }

        $this->component->addLog('[' . STOPWATCH_ID . '] Count the result of the query.', 128, 'stopwatch');
        $this->component->addLog('[' . STOPWATCH_ID . '] Query: ' . $query->__toString(), 128, 'stopwatch');

        $this->getDatabase()->setQuery($query);
        $this->getDatabase()->execute();

        $this->component->addLog('[' . STOPWATCH_ID . '] ListCount-Query executed: ' . \strval(microtime(true) - STOPWATCH_START), 128, 'stopwatch');

        $num = (int) $this->getDatabase()->getNumRows();

        $this->component->addLog('[' . STOPWATCH_ID . '] Rows counted: ' . \strval(microtime(true) - STOPWATCH_START), 128, 'stopwatch');

        return $num;
    }
}
