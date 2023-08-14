<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\Table\Table;
use \Joomla\CMS\MVC\Model\AdminModel;
use \Joomla\CMS\Language\Multilanguage;
use \Joomla\CMS\Form\FormFactoryInterface;
use \Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessInterface;

/**
 * Base model class for JoomGallery administration views
 *
 * @package JoomGallery
 * @since   4.0.0
 */
abstract class JoomAdminModel extends AdminModel
{
  /**
	 * Alias to manage history control
	 *
	 * @access  public
   * @var     string
	 */
	public $typeAlias = '';

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
   * JoomGallery extension calss
   *
   * @access  protected
   * @var     Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
   */
  protected $component;

  /**
	 * The prefix to use with controller messages.
	 *
	 * @access  protected
   * @var     string
	 */
	protected $text_prefix = _JOOM_OPTION_UC;

	/**
   * Item object
   *
   * @access  protected
   * @var     object
   */
	protected $item = null;

  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'image';

  /**
   * Constructor.
   *
   * @param   array                 $config       An array of configuration options (name, state, dbo, table_path, ignore_request).
   * @param   MVCFactoryInterface   $factory      The factory.
   * @param   FormFactoryInterface  $formFactory  The form factory.
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  public function __construct($config = [], MVCFactoryInterface $factory = null, FormFactoryInterface $formFactory = null)
  {
    parent::__construct($config, $factory, $formFactory);

    $this->app       = Factory::getApplication('administrator');
    $this->component = $this->app->bootComponent(_JOOM_OPTION);
    $this->user      = Factory::getUser();
    $this->typeAlias = _JOOM_OPTION.'.'.$this->type;
  }

  /**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  Table    A database object
	 *
	 * @since   4.0.0
	 */
	public function getTable($type = 'Image', $prefix = 'Administrator', $config = array())
	{
		return parent::getTable($this->type, $prefix, $config);
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
		$this->component->createAccess();

		return $this->component->getAccess();
	}

   /**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @param   Table  $table  Table Object
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function prepareTable($table)
	{
    $tablename = JoomHelper::$content_types[$this->type];

		if(empty($table->id))
		{
			// Set ordering to the last item if not set
			if(@$table->ordering === '')
			{
				$db = Factory::getDbo();
				$db->setQuery('SELECT MAX(ordering) FROM '.$tablename);
        
				$max             = $db->loadResult();
				$table->ordering = $max + 1;
			}
		}
	}

  /**
	 * Allows preprocessing of the JForm object.
	 *
	 * @param   Form    $form   The form object
	 * @param   array   $data   The data to be merged into the form object
	 * @param   string  $group  The plugin group to be executed
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function preprocessForm(Form $form, $data, $group = 'joomgallery')
	{
		if (!Multilanguage::isEnabled())
		{
			$form->setFieldAttribute('language', 'type', 'hidden');
			$form->setFieldAttribute('language', 'default', '*');
		}

		parent::preprocessForm($form, $data, $group);
	}
}
