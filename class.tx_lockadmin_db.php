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
 * alternate db class taking care of sys_lockedrecords
 *
 * @author	Bernhard Kraft  <kraftb@kraftb.at>
 * @package	TYPO3
 * @subpackage	lockadmin
 */
class tx_lockadmin_db extends t3lib_DB	{
	var $port = 23456;

	function exec_INSERTquery($table,$fields_values,$no_quote_fields=FALSE)	{
		if ($table=='sys_lockedrecords')	{
			tx_lockadmin_funcs::lock();
			$ret = $GLOBALS['TYPO3_DB_ORIG']->exec_INSERTquery($table, $fields_values, $no_quote_fields);
			$this->dataAvailable();
			tx_lockadmin_funcs::unlock();
		} else	{
			$ret = $GLOBALS['TYPO3_DB_ORIG']->exec_INSERTquery($table, $fields_values, $no_quote_fields);
		}
		return $ret;
	}


	function exec_DELETEquery($table,$where)	{
		if (($table=='sys_lockedrecords') && !$GLOBALS['T3_VAR']['inhibitLockNotify'])	{
			tx_lockadmin_funcs::lock();
			$ret = $GLOBALS['TYPO3_DB_ORIG']->exec_DELETEquery($table, $where);
			$this->dataAvailable();
			tx_lockadmin_funcs::unlock();
		} else	{
			$ret = $GLOBALS['TYPO3_DB_ORIG']->exec_DELETEquery($table, $where);
		}
		return $ret;
	}

	function dataAvailable()	{
		$lockFiles = tx_lockadmin_funcs::getLockFiles();
		if (is_array($lockFiles))	{
			foreach ($lockFiles as $lockFile)	{
				$pid = intval(basename($lockFile));
				posix_kill($pid, 15);
				@unlink($lockFile);
			}
		}
	}

}


$GLOBALS['TYPO3_DB_ORIG'] = &$GLOBALS['TYPO3_DB'];
unset($GLOBALS['TYPO3_DB']);
$GLOBALS['TYPO3_DB'] = t3lib_div::makeInstance('tx_lockadmin_db');

$GLOBALS['TYPO3_DB']->debugOutput = &$GLOBALS['TYPO3_DB_ORIG']->debugOutput;
$GLOBALS['TYPO3_DB']->debug_lastBuiltQuery = &$GLOBALS['TYPO3_DB_ORIG']->debug_lastBuiltQuery;
$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = &$GLOBALS['TYPO3_DB_ORIG']->store_lastBuiltQuery;
$GLOBALS['TYPO3_DB']->explainOutput = &$GLOBALS['TYPO3_DB_ORIG']->explainOutput;
$GLOBALS['TYPO3_DB']->link = &$GLOBALS['TYPO3_DB_ORIG']->link;

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__]);
}

?>
