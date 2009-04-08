<?php

########################################################################
# Extension Manager/Repository config file for ext: "lockadmin"
#
# Auto generated 08-04-2009 14:44
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Locking Admin',
	'description' => 'A Backend module to administrate locked records',
	'category' => 'be',
	'shy' => 0,
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => 0,
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author' => 'Bernhard Kraft',
	'author_email' => 'kraftb@kraftb.at',
	'author_company' => 'think-open.at',
	'version' => '0.0.1',
	'_md5_values_when_last_written' => 'a:42:{s:37:"class.localrecordlist_actionsHook.php";s:4:"be5e";s:28:"class.template.db_layout.php";s:4:"2baa";s:32:"class.template.t3lib_tcemain.php";s:4:"4e1c";s:37:"class.template.t3lib_transferdata.php";s:4:"4acc";s:27:"class.tx_lockadmin_ajax.php";s:4:"7069";s:32:"class.tx_lockadmin_clickmenu.php";s:4:"417f";s:25:"class.tx_lockadmin_db.php";s:4:"b32b";s:28:"class.tx_lockadmin_funcs.php";s:4:"4fe7";s:33:"class.tx_lockadmin_getButtons.php";s:4:"01e7";s:32:"class.tx_lockadmin_serviceDB.php";s:4:"d577";s:37:"class.tx_lockadmin_serviceDB.php.bkup";s:4:"d4ea";s:31:"class.tx_lockadmin_tceforms.php";s:4:"f188";s:30:"class.tx_lockadmin_toolbar.php";s:4:"ddbe";s:22:"class.ux_db_layout.php";s:4:"714d";s:26:"class.ux_t3lib_tcemain.php";s:4:"9340";s:31:"class.ux_t3lib_transferdata.php";s:4:"e246";s:28:"code.tx_lockadmin_xclass.php";s:4:"c76e";s:21:"ext_conf_template.txt";s:4:"18c2";s:12:"ext_icon.gif";s:4:"73d3";s:17:"ext_localconf.php";s:4:"2f22";s:14:"ext_tables.php";s:4:"e154";s:14:"ext_tables.sql";s:4:"38b5";s:15:"icon_unlock.gif";s:4:"31d1";s:13:"locallang.xml";s:4:"3a69";s:11:"sleeper.tmp";s:4:"d2c3";s:20:"xclass.db_layout.php";s:4:"9d5b";s:24:"xclass.t3lib_tcemain.php";s:4:"adc8";s:29:"xclass.t3lib_transferdata.php";s:4:"8435";s:38:"mod_lockadmin/class.tx_kbshop_t3tt.php";s:4:"2e5b";s:23:"mod_lockadmin/clear.gif";s:4:"cc11";s:22:"mod_lockadmin/conf.php";s:4:"4c93";s:23:"mod_lockadmin/index.php";s:4:"520a";s:27:"mod_lockadmin/locallang.xml";s:4:"4285";s:31:"mod_lockadmin/locallang_mod.xml";s:4:"0d8d";s:27:"mod_lockadmin/lockadmin.gif";s:4:"73d3";s:20:"res/clear_locks.html";s:4:"abbf";s:18:"res/lock_update.js";s:4:"0eeb";s:21:"res/send_message.html";s:4:"f74f";s:13:"res/shade.png";s:4:"6dc2";s:11:"res/sleeper";s:4:"b2b5";s:13:"res/sleeper.c";s:4:"13d6";s:48:"interfaces/interface.SC_db_layout_getButtons.php";s:4:"6796";}',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
);

?>