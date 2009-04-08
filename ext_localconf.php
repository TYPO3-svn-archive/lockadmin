<?php
if (!defined('TYPO3_MODE'))	{
	die('Access denied.');
}

define('PATH_lockadmin', t3lib_extMgm::extPath('lockadmin'));

$TYPO3_CONF_VARS['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'][] = 'EXT:lockadmin/class.localrecordlist_actionsHook.php:&tx_lockadmin_actionsHook';

$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass'][] = 'EXT:lockadmin/class.tx_lockadmin_tceforms.php:&tx_lockadmin_tceforms';

$TYPO3_CONF_VARS['BE']['AJAX']['Locking::checkLock'] = PATH_lockadmin.'class.tx_lockadmin_ajax.php:tx_lockadmin_ajax->checkLockAjax';
$TYPO3_CONF_VARS['BE']['AJAX']['Locking::clearLocks'] = PATH_lockadmin.'class.tx_lockadmin_ajax.php:tx_lockadmin_ajax->clearLocksAjax';

$classDef = 'EXT:lockadmin/class.tx_lockadmin_getButtons.php:&tx_lockadmin_getButtons';
$TYPO3_CONF_VARS['SC_OPTIONS']['cms/layout/db_layout.php']['getButtons']['lockadmin'] = $classDef;

$TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['recStatInfoHooks']['lockadmin'] = 'EXT:lockadmin/class.tx_lockadmin_getButtons.php:&tx_lockadmin_getButtons->tt_content_drawHeader';

$__lockObj = t3lib_div::getUserObj($classDef);
$GLOBALS['T3_VAR']['getUserObj']['EXT:lockadmin/class.tx_lockadmin_getButtons.php:&tx_lockadmin_getButtons'] = &$__lockObj;
$GLOBALS['T3_VAR']['callUserFunction_classPool']['tx_lockadmin_getButtons'] = &$__lockObj;


$_EXTCONF = unserialize($_EXTCONF);
$GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingMode'] = '';
switch (trim(strtolower($_EXTCONF['recordLockingMode'])))	{
	case 'single':
	case 'extended':
		$GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingMode'] = trim(strtolower($_EXTCONF['recordLockingMode']));
	break;
}
$GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingExplicit'] = intval($_EXTCONF['recordLockingExplicit'])?true:false;
$GLOBALS['TYPO3_CONF_VARS']['BE']['disableEditOnLock'] = (intval($_EXTCONF['disableEditOnLock']) && $GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingMode'])?true:false;
$GLOBALS['TYPO3_CONF_VARS']['BE']['disableEditIconsOnLock'] = (intval($_EXTCONF['disableEditIconsOnLock']) && $GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingMode'])?true:false;
$GLOBALS['TYPO3_CONF_VARS']['BE']['disableEditOnLock_contentLocked'] = (intval($_EXTCONF['disableEditOnLock_contentLocked']) && $GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingMode'])?true:false;
$GLOBALS['TYPO3_CONF_VARS']['BE']['disableEditIconsOnLock_contentLocked'] = (intval($_EXTCONF['disableEditIconsOnLock_contentLocked']) && $GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingMode'])?true:false;
$GLOBALS['TYPO3_CONF_VARS']['BE']['lockPageForTables'] = t3lib_div::trimExplode(',', $_EXTCONF['lockPageForTables']);
$GLOBALS['TYPO3_CONF_VARS']['BE']['lockTablesForPage'] = t3lib_div::trimExplode(',', $_EXTCONF['lockTablesForPage']);
$GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingAcrossWS'] = intval($_EXTCONF['recordLockingAcrossWS'])?true:false;
$GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockTimeout'] = intval($_EXTCONF['recordLockTimeout']);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lockadmin']['messagePid'] = intval($_EXTCONF['messagePid']);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lockadmin']['pageContentEditWarning'] = intval($_EXTCONF['pageContentEditWarning']);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lockadmin']['sleeperBin'] = $_EXTCONF['sleeperBin'];

require_once(PATH_lockadmin.'xclass.t3lib_tcemain.php');
require_once(PATH_lockadmin.'xclass.t3lib_transferdata.php');
require_once(PATH_lockadmin.'xclass.db_layout.php');

t3lib_extMgm::addService($_EXTKEY,  'locking', 'tx_lockadmin_serviceDB', array(
	'title' => 'Record locking',
	'description' => 'Locking of database resources (records)',

	'subtype' => 'lock_db,unlock_db,isLocked_db,getAllLocks_db',

	'available' => TRUE,
	'priority' => 50,
	'quality' => 50,

	'os' => '',
	'exec' => '',

	'classFile' => PATH_lockadmin.'class.tx_lockadmin_serviceDB.php',
	'className' => 'tx_lockadmin_serviceDB',
));

$GLOBALS['TYPO3_CONF_VARS']['typo3/backend.php']['additionalBackendItems'][] = PATH_lockadmin.'class.tx_lockadmin_toolbar.php';

?>
