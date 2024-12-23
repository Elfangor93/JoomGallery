<?php
/** 
******************************************************************************************
**   @version    4.0.0-beta1                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Field;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Language\Text;
use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Form\Field\ListField;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

class JgimagetypeField extends ListField
{
  /**
	 * A dropdown field with all activated imagetypes
	 *
	 * @var    string
	 * @since  1.6
	 */
	public $type = 'jgimagetype';

  /**
	 * Method to get a list of categories that respects access controls and can be used for
	 * either category assignment or parent category assignment in edit screens.
	 * Use the parent element to indicate that the field will be used for assigning parent categories.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   1.6
	 */
	protected function getOptions()
	{	
    // Get all imagetypes	
		$imagetypes = JoomHelper::getRecords('imagetypes');

		// Prepare the empty array
		$options = array();

		foreach($imagetypes as $imagetype) 
		{
      if($imagetype->params->get('jg_imgtype', '1'))
      {
        $options[] = HTMLHelper::_('select.option', $imagetype->typename, $imagetype->typename);
      }			
		}

		return $options;
	}
}
