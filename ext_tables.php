<?php
if (!defined ('TYPO3_MODE'))    die ('Access denied.');

if (TYPO3_MODE=='BE')   t3lib_extMgm::addModule('user','lockadmin','bottom', t3lib_extMgm::extPath($_EXTKEY).'mod_lockadmin/');

$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = array(
	'name' => 'tx_lockadmin_clickmenu',
	'path' => PATH_lockadmin.'class.tx_lockadmin_clickmenu.php',
);

$luFile = PATH_site.'typo3temp/lockadmin_lastupdate.tstamp';
$luFileExists = file_exists($luFile);

if ((!$luFileExists) || ($luFileExists && (filemtime(PATH_typo3conf.'localconf.php')>filemtime($luFile))))	{
	$fd = fopen($luFile, 'wb');
	fwrite($fd, time());
	fclose($fd);
	if ($GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingMode'])	{
		$GLOBALS['TYPO3_DB']->sql_query('ALTER TABLE sys_lockedrecords DROP PRIMARY KEY');
		$GLOBALS['TYPO3_DB']->sql_query('ALTER TABLE sys_lockedrecords ADD PRIMARY KEY (record_table, record_uid, hash)');
	} else	{
		$GLOBALS['TYPO3_DB']->sql_query('ALTER TABLE sys_lockedrecords DROP PRIMARY KEY');
		$GLOBALS['TYPO3_DB']->sql_query('ALTER TABLE sys_lockedrecords ADD PRIMARY KEY (userid, record_table, record_uid, hash)');
	}
}

require_once(PATH_lockadmin.'class.tx_lockadmin_db.php');

?>
