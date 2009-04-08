<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Bernhard Kraft <kraftb@kraftb.at>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


require_once(PATH_lockadmin.'class.tx_lockadmin_funcs.php');

/**
 * class to lock tceform fields for locked records
 *
 * @author	Bernhard Kraft <kraftb@kraftb.at>
 * @package	TYPO3
 * @subpackage	lockadmin
 */
class tx_lockadmin_tceforms	{

	function getUnlockScript()	{
		$ajax = tx_lockadmin_funcs::getJScode('whatever', '', 0);
		return $ajax.chr(10).'
function unlockRecords()	{
	var url = "'.t3lib_div::getIndpEnv('TYPO3_SITE_URL').'typo3/ajax.php?ajaxID='.rawurlencode('Locking::clearLocks').'&records="+lockedRecordList+"&lockTime='.$GLOBALS['EXEC_TIME'].'";
	top.ajax_doRequest(url);
}
window.onunload = unlockRecords;
';
	}

	function getSingleField_preProcess($table, $field, $row, $altName, $palette, $extra, $pal, &$pObj)	{
		$this->pObj = &$pObj;
		if (!$GLOBALS['SOBE']->doc->JScodeArray['lock_js'])	{
			$js = $this->getUnlockScript();
			$GLOBALS['SOBE']->doc->JScodeArray['lock_js'] = $js;
		}
		// TODO: Improve that - possibly do not use a global variable but a member
		// variable of the current object - check if it gets only instanciated
		// once for multiple fields/records
		if (!$GLOBALS['T3_VARS']['lockadmin_records'])	{
			$GLOBALS['T3_VARS']['lockadmin_records'] = array();
		}
		$GLOBALS['T3_VARS']['lockadmin_records'][] = $table.'-'.$row['uid'];
		$GLOBALS['T3_VARS']['lockadmin_records'] = array_unique($GLOBALS['T3_VARS']['lockadmin_records']);
		$rec_list = implode('.', $GLOBALS['T3_VARS']['lockadmin_records']);
			
		$GLOBALS['SOBE']->doc->JScodeArray['locked_records'] = 'var lockedRecordList = "'.$rec_list.'";'.chr(10);

		$GLOBALS['T3_VARS']['doExplicitLocking'] = $GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingExplicit'];
		$isLocked = tx_lockadmin_funcs::isRecordLocked($table, $row['uid']);
		$GLOBALS['T3_VARS']['doExplicitLocking'] = false;
		$pObj->storeRenderReadonly = $pObj->renderReadonly;
		$renderReadonly = 0;
		if (is_array($isLocked) && count($isLocked)) {
			foreach ($isLocked as $lockRec) {
				if ($lockRec['contentLocked'] && $GLOBALS['TYPO3_CONF_VARS']['BE']['disableEditOnLock_contentLocked']) {
					$renderReadonly = 1;
					break;
				} elseif ((!$lockRec['contentLocked']) && $GLOBALS['TYPO3_CONF_VARS']['BE']['disableEditOnLock']) {
					$renderReadonly = 1;
					break;
				}
			}
		}
		$pObj->renderReadonly = $renderReadonly;
	}

	function getSingleField_postProcess($table, $field, $row, $out, $PA, &$pObj)	{
		$pObj->renderReadonly = $pObj->storeRenderReadonly;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__]);
}

?>
