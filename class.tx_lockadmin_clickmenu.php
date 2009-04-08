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




/**
 * class for modifing the clickmenu
 *
 * @author	Bernhard Kraft  <kraftb@kraftb.at>
 * @package	TYPO3
 * @subpackage	lockadmin
 */
class tx_lockadmin_clickmenu	{
	var $disabledItems = array('edit', 'delete', 'history', 'hide', 'cut', 'edit_access', 'move_wizard');

	function main(&$parentObject, $menuItems, $table, $uid)	{

		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['disableEditOnLock'] || $GLOBALS['TYPO3_CONF_VARS']['BE']['disableEditOnLock_contentLocked']) {
			$locks = tx_lockadmin_funcs::isRecordLocked($table, $uid);
			$result = array();
			foreach ($locks as $idx => $lock) {
				if ($lock['contentLocked'] && $GLOBALS['TYPO3_CONF_VARS']['BE']['disableEditOnLock_contentLocked']) {
					$result[$idx] = $lock;
				} elseif ((!$lock['contentLocked']) && $GLOBALS['TYPO3_CONF_VARS']['BE']['disableEditOnLock']) {
					$result[$idx] = $lock;
				}
			}
			$locks = $result;
			if (is_array($locks) && count($locks))  {
				$tmpItems = $menuItems;
				foreach ($tmpItems as $key => $conf) {
					if (in_array($key, $this->disabledItems))	{
						unset($menuItems[$key]);
					} elseif (strpos(strtolower($conf[1]), 'hide')!==false)	{
						unset($menuItems[$key]);
					} elseif (strpos(strtolower($conf[1]), 'visibility')!==false)	{
						unset($menuItems[$key]);
					} elseif (strpos(strtolower($conf[1]), 'edit page properties')!==false)	{
						unset($menuItems[$key]);
					} elseif (strpos($conf[3], 'alt_doc.php?edit[')!==false)	{
						unset($menuItems[$key]);
					}
				}
                        }
		}
		return $menuItems;
	}

	public function log($string, $logWhich) {
		$string .= chr(10);
		$log_fd = fopen('/tmp/locking.log', 'ab');
		$userAgent = $_SERVER['HTTP_USER_AGENT'];
		if ($GLOBALS['BE_USER']->user['username']==='locktest2') {
			fwrite($log_fd, $logWhich.': '.$string);
		}
		fclose($log_fd);
	}


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__]);
}

?>
