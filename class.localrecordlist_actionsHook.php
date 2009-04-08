<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Bernhard Kraft  <kraftb@kraftb.at>
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

require_once(PATH_typo3.'interfaces/interface.localrecordlist_actionsHook.php');
require_once(PATH_lockadmin.'class.tx_lockadmin_funcs.php');

/**
 * interface for classes which hook into localRecordList and modify clip-icons
 *
 * @author	Bernhard Kraft  <kraftb@kraftb.at>
 * @package	TYPO3
 * @subpackage	lockadmin
 */
class tx_lockadmin_actionsHook implements localRecordList_actionsHook	{


	/**
	 * constructor, initializes several variables
	 *
	 * @return	void
	 */
	public function __construct()	{
		$this->checkRecordLocking = array();
	}


	/**
	 * modifies Web>List clip icons (copy, cut, paste, etc.) of a displayed row
	 *
	 * @param	string		the current database table
	 * @param	array		the current record row
	 * @param	array		the default clip-icons to get modified
	 * @param	object		Instance of calling object
	 * @return	array		the modified clip-icons
	 */
	public function makeClip($table, $row, $cells, &$parentObject)	{
		$locked = tx_lockadmin_funcs::isRecordLocked($table, $row['uid']);
		$lockSpanStyle = '';
		if (is_array($locked) && count($locked) && $GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingMode'])      {
			$lockSpanStyle = 'style="visibility: hidden;"';
                }
		foreach ($cells as $key => $cell)	{
			$span1 = '';
			$span2 = '';
			switch ($key)	{
				case 'cut':
					$span1 = '<span id="disableOnLock-'.$table.'-'.$row['uid'].'-'.($this->lsCnt++).'" '.$lockSpanStyle.'>';
					$span2 = '</span>';
				break;
			}
			$cells[$key] = $span1.$cell.$span2;
		}
		return $cells;
	}


	/**
	 * modifies Web>List control icons of a displayed row
	 *
	 * @param	string		the current database table
	 * @param	array		the current record row
	 * @param	array		the default control-icons to get modified
	 * @param	object		Instance of calling object
	 * @return	array		the modified control-icons
	 */
	public function makeControl($table, $row, $cells, &$parentObject)	{
		$GLOBALS['T3_VARS']['doExplicitLocking'] = $GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingExplicit'];
		$locked = tx_lockadmin_funcs::isRecordLocked($table, $row['uid']);
		$GLOBALS['T3_VARS']['doExplicitLocking'] = false;
		$lockSpanStyle = '';
		if (is_array($locked) && count($locked) && $GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingMode'])      {
			$lockSpanStyle = 'style="visibility: hidden;"';
		}
		if (($this->oldTable!=$table) || ($this->oldUid!=$row['uid']))	{
			$this->lsCnt = 0;
		}
		foreach ($cells as $key => $cell)	{
			$span1 = '';
			$span2 = '';
			switch ($key)	{
				case 'edit':
				case 'move':
				case 'moveUp':
				case 'moveDown':
				case 'moveLeft':
				case 'moveRight':
				case 'hide':
				case 'delete':
				case 'history':
					$span1 = '<span id="disableOnLock-'.$table.'-'.$row['uid'].'-'.($this->lsCnt++).'" '.$lockSpanStyle.'>';
					$span2 = '</span>';
				break;
			}
			$cells[$key] = $span1.$cell.$span2;
		}
		$this->checkRecordLocking[] = array($table, $row['uid']);
		$lockBox = tx_lockadmin_funcs::getLockBoxes($table, $row['uid'], $parentObject->backPath, false, 17, true);
		$cells['locked'] = '<span id="lockIcon-'.$table.'-'.$row['uid'].'" style="width: 17px;">'.$lockBox.'</span>';
		$locked = tx_lockadmin_funcs::isRecordLocked($table, $row['uid']);
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingExplicit']) {
			$allowUnlock = false;
			if (is_array($locked) && count($locked)) {
				foreach ($locked as $lockRow) {
					if ($lockRow['userid']==-$GLOBALS['BE_USER']->user['uid']) {
						$allowUnlock = true;
						break;
					}
				}
			}
			if ($allowUnlock) {
				$cells['explicit_locked'] = $this->getExplicitLockIcon($table, $row, 0);
			} else {
				if (!(is_array($locked) && count($locked))) {
					$span1 = '<span id="disableOnLock-'.$table.'-'.$row['uid'].'-'.($this->lsCnt++).'" '.$lockSpanStyle.'>';
					$cells['explicit_locked'] = $span1.$this->getExplicitLockIcon($table, $row).'</span>';
				}
			}
		}
		return $cells;
	}


	public function getExplicitLockIcon($table, $row, $lock = 1) {
		if ($lock) {
			$img = '<img src="'.t3lib_extMgm::extRelPath('lockadmin').'/ext_icon.gif" width="16" height="16" alt="Manually lock record" title="Manually lock record" />';
		} else {
			$img = '<img src="'.t3lib_extMgm::extRelPath('lockadmin').'/icon_unlock.gif" width="16" height="16" alt="Unlock manually locked record" title="Unlock manually locked record" />';
		}
		$params = '&lockRecords['.$table.']['.$row['uid'].']='.$lock;
		$onclick = htmlspecialchars('return jumpToUrl(\''.$this->issueCommand($params).'\');');
		$link = '<a href="#" onclick="'.$onclick.'">';
		return $link.$img.'</a>';
	}

	public function issueCommand($params,$rUrl='')	{
		$rUrl = $rUrl ? $rUrl : t3lib_div::getIndpEnv('REQUEST_URI');
		return t3lib_extMgm::extRelPath('lockadmin').'mod_lockadmin/index.php?'.
				$params.
				'&redirect='.($rUrl==-1?"'+T3_THIS_LOCATION+'":rawurlencode($rUrl));
	}

	/**
	 * modifies Web>List header row columns/cells
	 *
	 * @param	string		the current database table
	 * @param	array		Array of the currently displayed uids of the table
	 * @param	array		An array of rendered cells/columns
	 * @param	object		Instance of calling (parent) object
	 * @return	array		Array of modified cells/columns
	 */
	public function renderListHeader($table, $currentIdList, $headerColumns, &$parentObject)	{
		return $headerColumns;
	}


	/**
	 * modifies Web>List header row clipboard/action icons
	 *
	 * @param	string		the current database table
	 * @param	array		Array of the currently displayed uids of the table
	 * @param	array		An array of the current clipboard/action icons
	 * @param	object		Instance of calling (parent) object
	 * @return	array		Array of modified clipboard/action icons
	 */
	public function renderListHeaderActions($table, $currentIdList, $cells, &$parentObject)	{
		$js = tx_lockadmin_funcs::getLockScript($this->checkRecordLocking, $parentObject->backPath, 17, false);
		$GLOBALS['SOBE']->doc->JScodeLibArray['lock_js'] = $js;
		$GLOBALS['SOBE']->doc->inDocStylesArray['lock_css'] = '
table.typo3-dblist tr.db_list_normal td.col-title a img {
	display: none;
}
';
		return $cells;
	}


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__]);
}

?>
