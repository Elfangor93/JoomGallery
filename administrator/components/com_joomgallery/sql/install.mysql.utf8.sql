CREATE TABLE IF NOT EXISTS `#__joomgallery` (
  `id` int(11) NOT NULL auto_increment,
  `asset_id` int(10) NOT NULL default 0,
  `catid` int(11) NOT NULL default 0,
  `imgtitle` text NOT NULL,
  `alias` varchar(255) NOT NULL default '',
  `imgauthor` varchar(50) default NULL,
  `imgtext` text NOT NULL,
  `imgdate` datetime NOT NULL,
  `hits` int(11) NOT NULL default '0',
  `downloads` int(11) NOT NULL default '0',
  `imgvotes` int(11) NOT NULL default '0',
  `imgvotesum` int(11) NOT NULL default '0',
  `access` tinyint(3) NOT NULL default '0',
  `published` tinyint(1) NOT NULL default '0',
  `hidden` tinyint(1) NOT NULL default '0',
  `featured` tinyint(1) NOT NULL default '0',
  `imgfilename` varchar(255) NOT NULL default '',
  `imgthumbname` varchar(255) NOT NULL default '',
  `checked_out` int(11) NOT NULL default '0',
  `owner` int(11) UNSIGNED NOT NULL default '0',
  `approved` tinyint(1) NOT NULL default '0',
  `useruploaded` tinyint(1) NOT NULL default '0',
  `ordering` int(11) NOT NULL default '0',
  `params` text NOT NULL,
  `metakey` text NOT NULL,
  `metadesc` text NOT NULL,
  PRIMARY KEY (`id`),
  INDEX idx_catid (`catid`),
  INDEX idx_owner (`owner`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__joomgallery_category_details` (
  `id` int(11) NOT NULL,
  `details_key` varchar(255) NOT NULL,
  `details_value` text NOT NULL,
  `ordering` int(11) NOT NULL,
  PRIMARY KEY (`id`,`details_key`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__joomgallery_catg` (
  `cid` int(11) NOT NULL auto_increment,
  `asset_id` int(10) NOT NULL default 0,
  `name` varchar(255) NOT NULL default '',
  `alias` varchar(2048) NOT NULL default '',
  `parent_id` int(11) NOT NULL default 0,
  `lft` int(11) NOT NULL default 0,
  `rgt` int(11) NOT NULL default 0,
  `level` int(1) UNSIGNED NOT NULL default 0,
  `description` text,
  `access` tinyint(3) unsigned NOT NULL default 0,
  `published` tinyint(1) NOT NULL default 0,
  `hidden` tinyint(1) NOT NULL default 0,
  `in_hidden` tinyint(1) NOT NULL default 0,
  `password` varchar(100) NOT NULL default '',
  `owner` int(11) default 0,
  `thumbnail` int(11),
  `img_position` int(10) default 0,
  `catpath` varchar(2048) NOT NULL default '',
  `params` text NOT NULL,
  `metakey` text NOT NULL,
  `metadesc` text NOT NULL,
  `exclude_toplists` int(1) NOT NULL,
  `exclude_search` int(1) NOT NULL,
  PRIMARY KEY (`cid`),
  INDEX idx_parent_id (`parent_id`)
) DEFAULT CHARSET=utf8;

INSERT INTO `#__joomgallery_catg` VALUES ('1', '0', 'ROOT', 'root', '0', '0', '0', '0', NULL, '1', '1', '0', '0', '', '0', NULL, '0', '', '', '', '', 0, 0);

CREATE TABLE IF NOT EXISTS `#__joomgallery_comments` (
  `cmtid` int(11) NOT NULL auto_increment,
  `cmtpic` int(11) NOT NULL default '0',
  `cmtip` varchar(45) NOT NULL default '',
  `userid` int(11) UNSIGNED NOT NULL default '0',
  `cmtname` varchar(50) NOT NULL default '',
  `cmttext` text NOT NULL,
  `cmtdate` datetime NOT NULL,
  `published` tinyint(1) NOT NULL default '0',
  `approved` tinyint(1) NOT NULL default '1',
  PRIMARY KEY (`cmtid`),
  INDEX idx_cmtpic (`cmtpic`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__joomgallery_config` (
  `id` int(1) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL,
  `ordering` int(11) NOT NULL,
  `jg_pathimages` varchar(100) NOT NULL,
  `jg_pathoriginalimages` varchar(100) NOT NULL,
  `jg_paththumbs` varchar(100) NOT NULL,
  `jg_pathftpupload` varchar(100) NOT NULL,
  `jg_pathtemp` varchar(100) NOT NULL,
  `jg_wmpath` varchar(100) NOT NULL,
  `jg_wmfile` varchar(50) NOT NULL,
  `jg_use_real_paths` int(1) NOT NULL,
  `jg_checkupdate` int(1) NOT NULL,
  `jg_filenamewithjs` int(1) NOT NULL,
  `jg_filenamereplace` text NOT NULL,
  `jg_thumbcreation` varchar(5) NOT NULL,
  `jg_fastgd2thumbcreation` int(1) NOT NULL,
  `jg_impath` varchar(50) NOT NULL,
  `jg_resizetomaxwidth` int(1) NOT NULL,
  `jg_maxwidth` int(5) NOT NULL,
  `jg_picturequality` int(3) NOT NULL,
  `jg_useforresizedirection` int(1) NOT NULL,
  `jg_cropposition` int(1) NOT NULL,
  `jg_thumbwidth` int(5) NOT NULL,
  `jg_thumbheight` int(5) NOT NULL,
  `jg_thumbquality` int(3) NOT NULL,
  `jg_uploadorder` int(1) NOT NULL,
  `jg_useorigfilename` int(1) NOT NULL,
  `jg_filenamenumber` int(1) NOT NULL,
  `jg_delete_original` int(1) NOT NULL,
  `jg_msg_upload_type` int(1) NOT NULL,
  `jg_msg_upload_recipients` text NOT NULL,
  `jg_msg_download_type` int(1) NOT NULL,
  `jg_msg_download_recipients` text NOT NULL,
  `jg_msg_zipdownload` int(1) NOT NULL,
  `jg_msg_comment_type` int(1) NOT NULL,
  `jg_msg_comment_recipients` text NOT NULL,
  `jg_msg_comment_toowner` int(1) NOT NULL,
  `jg_msg_nametag_type` int(1) NOT NULL,
  `jg_msg_nametag_recipients` text NOT NULL,
  `jg_msg_nametag_totaggeduser` int(1) NOT NULL,
  `jg_msg_nametag_toowner` int(1) NOT NULL,
  `jg_msg_report_type` int(1) NOT NULL,
  `jg_msg_report_recipients` text NOT NULL,
  `jg_msg_report_toowner` int(1) NOT NULL,
  `jg_msg_rejectimg_type` int(1) NOT NULL,
  `jg_msg_global_from` int(1) NOT NULL,
  `jg_realname` int(1) NOT NULL,
  `jg_contentpluginsenabled` int(1) NOT NULL,
  `jg_itemid` varchar(10) NOT NULL,
  `jg_ajaxcategoryselection` int(1) NOT NULL,
  `jg_disableunrequiredchecks` int(1) NOT NULL,
  `jg_use_listbox_max_user_count` int(1) NOT NULL,
  `jg_userspace` int(1) NOT NULL,
  `jg_useruploaddefaultcat` int(1) NOT NULL,
  `jg_approve` int(1) NOT NULL,
  `jg_unregistered_permissions` int(1) NOT NULL,
  `jg_maxusercat` int(5) NOT NULL,
  `jg_maxuserimage` int(9) NOT NULL,
  `jg_maxuserimage_timespan` int(9) NOT NULL,
  `jg_maxfilesize` int(9) NOT NULL,
  `jg_usercatacc` int(1) NOT NULL,
  `jg_usercatthumbalign` int(1) NOT NULL,
  `jg_useruploadsingle` int(1) NOT NULL,
  `jg_maxuploadfields` int(3) NOT NULL,
  `jg_useruploadajax` int(1) NOT NULL,
  `jg_useruploadbatch` int(1) NOT NULL,
  `jg_useruploadjava` int(1) NOT NULL,
  `jg_useruseorigfilename` int(1) NOT NULL,
  `jg_useruploadnumber` int(1) NOT NULL,
  `jg_special_gif_upload` int(1) NOT NULL,
  `jg_delete_original_user` int(1) NOT NULL,
  `jg_newpiccopyright` int(1) NOT NULL,
  `jg_newpicnote` int(1) NOT NULL,
  `jg_redirect_after_upload` int(1) NOT NULL,
  `jg_edit_metadata` int(1) NOT NULL,
  `jg_download` int(1) NOT NULL,
  `jg_download_unreg` int(1) NOT NULL,
  `jg_download_hint` int(1) NOT NULL,
  `jg_downloadfile` int(1) NOT NULL,
  `jg_downloadwithwatermark` int(1) NOT NULL,
  `jg_showrating` int(1) NOT NULL,
  `jg_maxvoting` int(1) NOT NULL,
  `jg_ratingcalctype` int(1) NOT NULL,
  `jg_ratingdisplaytype` int(1) NOT NULL,
  `jg_ajaxrating` int(1) NOT NULL,
  `jg_votingonlyonce` int(1) NOT NULL,
  `jg_votingonlyreg` int(1) NOT NULL,
  `jg_showcomment` int(1) NOT NULL,
  `jg_anoncomment` int(1) NOT NULL,
  `jg_namedanoncomment` int(1) NOT NULL,
  `jg_anonapprovecom` int(1) NOT NULL,
  `jg_approvecom` int(1) NOT NULL,
  `jg_storecommentip` int(1) NOT NULL,
  `jg_bbcodesupport` int(1) NOT NULL,
  `jg_smiliesupport` int(1) NOT NULL,
  `jg_anismilie` int(1) NOT NULL,
  `jg_smiliescolor` varchar(10) NOT NULL,
  `jg_report_images` int(1) NOT NULL,
  `jg_report_unreg` int(1) NOT NULL,
  `jg_report_hint` int(1) NOT NULL,
  `jg_alternative_layout` varchar(255) NOT NULL,
  `jg_anchors` int(1) NOT NULL,
  `jg_tooltips` int(1) NOT NULL,
  `jg_dyncrop` int(1) NOT NULL,
  `jg_dyncropposition` int(1) NOT NULL,
  `jg_dyncropwidth` int(5) NOT NULL,
  `jg_dyncropheight` int(5) NOT NULL,
  `jg_dyncropbgcol` varchar(12) NOT NULL,
  `jg_hideemptycats` int(1) NOT NULL,
  `jg_skipcatview` int(1) NOT NULL,
  `jg_imgalign` int(3) NOT NULL,
  `jg_showrestrictedcats` int(1) NOT NULL,
  `jg_showrestrictedhint` int(1) NOT NULL,
  `jg_firstorder` varchar(20) NOT NULL,
  `jg_secondorder` varchar(20) NOT NULL,
  `jg_thirdorder` varchar(20) NOT NULL,
  `jg_pagetitle_cat` text NOT NULL,
  `jg_pagetitle_detail` text NOT NULL,
  `jg_showgalleryhead` int(1) NOT NULL,
  `jg_showpathway` int(1) NOT NULL,
  `jg_completebreadcrumbs` int(1) NOT NULL,
  `jg_search` int(1) NOT NULL,
  `jg_searchcols` int(1) NOT NULL,
  `jg_searchthumbalign` int(1) NOT NULL,  
  `jg_searchtextalign` int(1) NOT NULL,
  `jg_showsearchdownload` int(1) NOT NULL,
  `jg_showsearchfavourite` int(1) NOT NULL,
  `jg_search_report_images` int(1) NOT NULL,
  `jg_showsearcheditorlinks` int(1) NOT NULL,
  `jg_showallpics` int(1) NOT NULL,
  `jg_showallhits` int(1) NOT NULL,
  `jg_showbacklink` int(1) NOT NULL,
  `jg_suppresscredits` int(1) NOT NULL,
  `jg_showuserpanel` int(1) NOT NULL,
  `jg_showuserpanel_hint` int(1) NOT NULL,
  `jg_showuserpanel_unreg` int(1) NOT NULL,
  `jg_showallpicstoadmin` int(1) NOT NULL,
  `jg_showminithumbs` int(1) NOT NULL,
  `jg_openjs_padding` int(3) NOT NULL,
  `jg_openjs_background` varchar(12) NOT NULL,
  `jg_dhtml_border` varchar(12) NOT NULL,
  `jg_show_title_in_popup` int(1) NOT NULL,
  `jg_show_description_in_popup` int(1) NOT NULL,
  `jg_lightbox_speed` int(3) NOT NULL,
  `jg_lightbox_slide_all` int(1) NOT NULL,
  `jg_resize_js_image` int(1) NOT NULL,
  `jg_disable_rightclick_original` int(1) NOT NULL,
  `jg_showgallerysubhead` int(1) NOT NULL,
  `jg_showallcathead` int(1) NOT NULL,
  `jg_colcat` int(1) NOT NULL,
  `jg_catperpage` int(1) NOT NULL,
  `jg_ordercatbyalpha` int(1) NOT NULL,
  `jg_showgallerypagenav` int(1) NOT NULL,
  `jg_showcatcount` int(1) NOT NULL,
  `jg_showcatthumb` int(1) NOT NULL,
  `jg_showrandomcatthumb` int(1) NOT NULL,
  `jg_ctalign` int(1) NOT NULL,
  `jg_showtotalcatimages` int(1) NOT NULL,
  `jg_showtotalcathits` int(1) NOT NULL,
  `jg_showcatasnew` int(1) NOT NULL,
  `jg_catdaysnew` int(3) NOT NULL,
  `jg_showdescriptioningalleryview` int(1) NOT NULL,
  `jg_uploadicongallery` int(1) NOT NULL,
  `jg_showsubsingalleryview` int(1) NOT NULL,
  `jg_category_rss` int(9) NOT NULL,
  `jg_category_rss_icon` varchar(10) NOT NULL,
  `jg_uploadiconcategory` int(1) NOT NULL,
  `jg_showcathead` int(1) NOT NULL,
  `jg_usercatorder` int(1) NOT NULL,
  `jg_usercatorderlist` varchar(50) NOT NULL,
  `jg_showcatdescriptionincat` int(1) NOT NULL,
  `jg_showpagenav` int(1) NOT NULL,
  `jg_showpiccount` int(1) NOT NULL,
  `jg_perpage` int(3) NOT NULL,
  `jg_catthumbalign` int(1) NOT NULL,
  `jg_colnumb` int(3) NOT NULL,
  `jg_detailpic_open` varchar(50) NOT NULL,
  `jg_lightboxbigpic` int(1) NOT NULL,
  `jg_showtitle` int(1) NOT NULL,
  `jg_showpicasnew` int(1) NOT NULL,
  `jg_daysnew` int(3) NOT NULL,
  `jg_showhits` int(1) NOT NULL,
  `jg_showdownloads` int(1) NOT NULL,
  `jg_showauthor` int(1) NOT NULL,
  `jg_showowner` int(1) NOT NULL,
  `jg_showcatcom` int(1) NOT NULL,
  `jg_showcatrate` int(1) NOT NULL,
  `jg_showcatdescription` int(1) NOT NULL,
  `jg_showcategorydownload` int(1) NOT NULL,
  `jg_showcategoryfavourite` int(1) NOT NULL,
  `jg_category_report_images` int(1) NOT NULL,
  `jg_showcategoryeditorlinks` int(1) NOT NULL,
  `jg_showsubcathead` int(1) NOT NULL,
  `jg_showsubcatcount` int(1) NOT NULL,
  `jg_colsubcat` int(3) NOT NULL,
  `jg_subperpage` int(3) NOT NULL,
  `jg_showpagenavsubs` INT(1) NOT NULL,
  `jg_subcatthumbalign` int(1) NOT NULL,
  `jg_showsubthumbs` int(1) NOT NULL,
  `jg_showrandomsubthumb` int(1) NOT NULL,
  `jg_showdescriptionincategoryview` int(1) NOT NULL,
  `jg_ordersubcatbyalpha` int(1) NOT NULL,
  `jg_showtotalsubcatimages` int(1) NOT NULL,
  `jg_showtotalsubcathits` int(1) NOT NULL,
  `jg_uploadiconsubcat` int(1) NOT NULL,
  `jg_showdetailpage` int(1) NOT NULL,
  `jg_disabledetailpage` int(1) NOT NULL,
  `jg_showdetailnumberofpics` int(1) NOT NULL,
  `jg_cursor_navigation` int(1) NOT NULL,
  `jg_disable_rightclick_detail` int(1) NOT NULL,
  `jg_detail_report_images` int(1) NOT NULL,
  `jg_showdetaileditorlinks` int(1) NOT NULL,
  `jg_showdetailtitle` int(1) NOT NULL,
  `jg_showdetail` int(1) NOT NULL,
  `jg_showdetailaccordion` int(1) NOT NULL,
  `jg_accordionduration` int(3) NOT NULL,
  `jg_accordiondisplay` int(3) NOT NULL,
  `jg_accordionopacity` int(1) NOT NULL,
  `jg_accordionalwayshide` int(1) NOT NULL,
  `jg_accordioninitialeffect` int(1) NOT NULL,
  `jg_showdetaildescription` int(1) NOT NULL,
  `jg_showdetaildatum` int(1) NOT NULL,
  `jg_showdetailhits` int(1) NOT NULL,
  `jg_showdetaildownloads` int(1) NOT NULL,
  `jg_showdetailrating` int(1) NOT NULL,
  `jg_showdetailfilesize` int(1) NOT NULL,
  `jg_showdetailauthor` int(1) NOT NULL,
  `jg_showoriginalfilesize` int(1) NOT NULL,
  `jg_showdetaildownload` int(1) NOT NULL,
  `jg_watermark` int(1) NOT NULL,
  `jg_watermarkpos` int(1) NOT NULL,
  `jg_watermarkzoom` int(1) NOT NULL,
  `jg_watermarksize` int(1) NOT NULL,
  `jg_bigpic` int(1) NOT NULL,
  `jg_bigpic_unreg` int(1) NOT NULL,
  `jg_bigpic_open` varchar(50) NOT NULL,
  `jg_bbcodelink` int(1) NOT NULL,
  `jg_showcommentsunreg` int(1) NOT NULL,
  `jg_showcommentsarea` int(1) NOT NULL,
  `jg_send2friend` int(1) NOT NULL,
  `jg_minis` int(1) NOT NULL,
  `jg_motionminis` int(1) NOT NULL,
  `jg_motionminiWidth` int(3) NOT NULL,
  `jg_motionminiHeight` int(3) NOT NULL,
  `jg_motionminiLimit` int(2) NOT NULL,
  `jg_miniWidth` int(3) NOT NULL,
  `jg_miniHeight` int(3) NOT NULL,
  `jg_minisprop` int(1) NOT NULL,
  `jg_nameshields` int(1) NOT NULL,
  `jg_nameshields_others` int(1) NOT NULL,
  `jg_nameshields_unreg` int(1) NOT NULL,
  `jg_show_nameshields_unreg` int(1) NOT NULL,
  `jg_nameshields_height` int(3) NOT NULL,
  `jg_nameshields_width` int(3) NOT NULL,
  `jg_slideshow` int(1) NOT NULL,
  `jg_slideshow_timer` int(3) NOT NULL,
  `jg_slideshow_transition` int(1) NOT NULL,
  `jg_slideshow_transtime` int(3) NOT NULL,
  `jg_slideshow_maxdimauto` int(1) NOT NULL,
  `jg_slideshow_width` int(3) NOT NULL,
  `jg_slideshow_heigth` int(3) NOT NULL,
  `jg_slideshow_infopane` int(1) NOT NULL,
  `jg_slideshow_carousel` int(1) NOT NULL,
  `jg_slideshow_arrows` int(1) NOT NULL,
  `jg_slideshow_repeat` int(1) NOT NULL,
  `jg_showexifdata` int(1) NOT NULL,
  `jg_showgeotagging` int(1) NOT NULL,
  `jg_geotaggingkey` text NOT NULL,
  `jg_subifdtags` text NOT NULL,
  `jg_ifdotags` text NOT NULL,
  `jg_gpstags` text NOT NULL,
  `jg_showiptcdata` int(1) NOT NULL,
  `jg_iptctags` text NOT NULL,
  `jg_showtoplist` int(1) NOT NULL,
  `jg_toplist` int(3) NOT NULL,
  `jg_topthumbalign` int(1) NOT NULL,
  `jg_toptextalign` int(1) NOT NULL,
  `jg_toplistcols` int(3) NOT NULL,
  `jg_whereshowtoplist` int(1) NOT NULL,
  `jg_showrate` int(1) NOT NULL,
  `jg_showlatest` int(1) NOT NULL,
  `jg_showcom` int(1) NOT NULL,
  `jg_showthiscomment` int(1) NOT NULL,
  `jg_showmostviewed` int(1) NOT NULL,
  `jg_showtoplistdownload` int(1) NOT NULL,
  `jg_showtoplistfavourite` int(1) NOT NULL,
  `jg_toplist_report_images` int(1) NOT NULL,
  `jg_showtoplisteditorlinks` int(1) NOT NULL,
  `jg_favourites` int(1) NOT NULL,
  `jg_favouritesshownotauth` int(1) NOT NULL,
  `jg_maxfavourites` int(5) NOT NULL,
  `jg_zipdownload` int(1) NOT NULL,
  `jg_usefavouritesforpubliczip` int(1) NOT NULL,
  `jg_usefavouritesforzip` int(1) NOT NULL,
  `jg_allimagesofcategory` int(1) NOT NULL,
  `jg_showfavouritesdownload` int(1) NOT NULL,
  `jg_showfavouriteseditorlinks` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__joomgallery_countstop` (
  `cspicid` int(11) NOT NULL default 0,
  `csip` varchar(45) NOT NULL default '',
  `cssessionid` varchar(200),
  `cstime` DATETIME,
  INDEX idx_cspicid (`cspicid`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__joomgallery_image_details` (
  `id` int(11) NOT NULL,
  `details_key` varchar(255) NOT NULL,
  `details_value` text NOT NULL,
  `ordering` int(11) NOT NULL,
  PRIMARY KEY (`id`,`details_key`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__joomgallery_maintenance` (
  `id` int(11) NOT NULL auto_increment,
  `refid` int(11) NOT NULL,
  `catid` int(11) NOT NULL,
  `owner` int(11) NOT NULL,
  `title` text NOT NULL,
  `thumb` varchar(255) NOT NULL,
  `img` varchar(255) NOT NULL,
  `orig` varchar(255) NOT NULL,
  `thumborphan` int(11) NOT NULL,
  `imgorphan` int(11) NOT NULL,
  `origorphan` int(11) NOT NULL,
  `type` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__joomgallery_nameshields` (
  `nid` int(11) NOT NULL auto_increment,
  `npicid` int(11) NOT NULL default '0',
  `nuserid` int(11) UNSIGNED NOT NULL default '0',
  `nxvalue` int(11) NOT NULL default '0',
  `nyvalue` int(11) NOT NULL default '0',
  `by` int(11) NOT NULL default '0',
  `nuserip` varchar(15) NOT NULL default '0',
  `ndate` datetime NOT NULL,
  `nzindex` int(11) NOT NULL default '0',
  PRIMARY KEY  (`nid`),
  INDEX idx_picid (`npicid`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__joomgallery_orphans` (
  `id` int(11) NOT NULL auto_increment,
  `fullpath` varchar(255) NOT NULL,
  `type` varchar(7) NOT NULL,
  `refid` int(11) NOT NULL,
  `title` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fullpath` (`fullpath`(255))
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__joomgallery_users` (
  `uid` int(11) NOT NULL auto_increment,
  `uuserid` int(11) NOT NULL default '0',
  `piclist` text default NULL,
  `layout` int(1) NOT NULL,
  `time` datetime NOT NULL,
  `zipname` varchar(70) NOT NULL,
  PRIMARY KEY (`uid`),
  INDEX idx_uid (`uuserid`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__joomgallery_votes` (
  `voteid` int(11) NOT NULL auto_increment,
  `picid` int(11) NOT NULL default '0',
  `userid` int(11) UNSIGNED NOT NULL default '0',
  `userip` varchar(15) NOT NULL default '0',
  `datevoted` datetime NOT NULL,
  `vote` int(11) NOT NULL default '0',
  PRIMARY KEY (`voteid`),
  INDEX idx_picid (`picid`)
) DEFAULT CHARSET=utf8;
