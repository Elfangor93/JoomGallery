<?php
/****************************************************************************************\
**   JoomGallery 3                                                                      **
**   By: JoomGallery::ProjectTeam                                                       **
**   Copyright (C) 2008 - 2021  JoomGallery::ProjectTeam                                **
**   Based on: JoomGallery 1.0.0 by JoomGallery::ProjectTeam                            **
**   Released under GNU GPL Public License                                              **
**   License: http://www.gnu.org/copyleft/gpl.html or have a look                       **
**   at administrator/components/com_joomgallery/LICENSE.TXT                            **
\****************************************************************************************/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

/**
 * JoomGallery maintenance table class
 *
 * @package JoomGallery
 * @since   1.5.5
 */
class TableJoomgalleryOrphans extends JTable
{
  /** @var int Primary key */
  var $id           = null;
  /** @var string */
  var $fullpath     = null;
  /** @var string */
  var $type         = null;
  /** @var int */
  var $refid        = null;
  /** @var string */
  var $title        = null;
  
  function __construct($db)
  {
    parent::__construct(_JOOM_TABLE_ORPHANS, 'id', $db);
  }
}