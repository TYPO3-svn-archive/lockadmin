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

require_once(PATH_t3lib.'class.t3lib_tcemain.php');

class ###CLASS### extends ###BASE_CLASS###	{

	function doesRecordExist($table,$id,$perms)	{
		$ret = parent::doesRecordExist($table, $id, $perms);
		if (($GLOBALS['TYPO3_CONF_VARS']['BE']['disableEditOnLock'] || $GLOBALS['TYPO3_CONF_VARS']['BE']['disableEditOnLock_contentLocked']) && $ret) {
			$GLOBALS['T3_VARS']['doExplicitLocking'] = $GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingExplicit'];
			$locks = tx_lockadmin_funcs::isRecordLocked($table,$id);
			$GLOBALS['T3_VARS']['doExplicitLocking'] = false;
			if (is_array($locks) && count($locks)) {
				$lockingUsers = array();
				foreach ($locks as $lockRows)	{
					if ($GLOBALS['TYPO3_CONF_VARS']['BE']['disableEditOnLock'] && !$lockRows['contentLocked']) {
						$lockingUsers[] = $lockRows['username'];
					} elseif ($GLOBALS['TYPO3_CONF_VARS']['BE']['disableEditOnLock_contentLocked'] && $lockRows['contentLocked']) {
						$lockingUsers[] = $lockRows['username'];
					}
				}
				if (count($lockingUsers)) {
					$this->newLog('doesRecordExist(),perms='.$perms.': '.$table.':'.$id.' was locked by: '.implode(' / ', $lockingUsers), 1);
					return false;
				}
			}
		}
		return $ret;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__]);
}

?>
