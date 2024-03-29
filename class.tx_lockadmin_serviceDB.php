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
/**
 * Service base class for 'Locking'.
 *
 * @author	Bernhard Kraft <kraftb@kraftb.at>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */

require_once(PATH_t3lib.'class.t3lib_svbase.php');




/**
 * Locking services class
 *
 * @author	Bernhard Kraft <kraftb@kraftb.at>
 * @package	TYPO3
 * @subpackage	lockadmin
 */
class tx_lockadmin_serviceDB extends t3lib_svbase	{


	/**
	 * Locks a record using the old-fashioned "advisory" locking type. Only a warning
	 * (red exclamation mark) gets shown when a record is edited by a different user
	 * and/or session.
	 *
	 * @param	string		The table to get locked
	 * @param	integer		The uid of the record going to be locked
	 * @param	integer		The pid the record going to be locked is found on
	 * @param	string		A hash/cHash value specifing the record to be locked
	 * @return	boolean		Whether the record could get locked or not
	 */
	function lock($table, $uid = 0, $pid = 0, $hash = '', $lockingMode = '')	{
		$retVal = NULL;
		if ($table)	{
			$userid = intval($GLOBALS['BE_USER']->user['uid']);

			// TODO:
			// Change the following in t3lib_transferdata::fetchRecord (~ line 251)
			// $contentTable = $GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable'];
 			// $this->lockRecord($table, $id, $contentTable==$table?$row['pid']:0);  // Locking the pid if the table edited is the content table.
			// So the pid is always passed. Then we do not need to fetch the PID here ...

			$realPid = $pid;
			if ($uid && !$realPid)	{
				$rec = t3lib_BEfunc::getRecord($table, $uid);
				$realPid = $rec['pid'];
			}

			$pid = 0;
			if (in_array($table, $GLOBALS['TYPO3_CONF_VARS']['BE']['lockPageForTables'])) {
				$pid = $realPid;
			}

			if ($GLOBALS['T3_VARS']['doExplicitLocking']) {
				$userid = -$userid;
			}

			$fields_values = array(
					// The uid of the user who locked the record. The negative uid of
					// the user who locked the record if the record has been explicitely locked
				'userid' => $userid,
					// The time when the record got locked
				'tstamp' => $GLOBALS['EXEC_TIME'],
					// Table of locked record
				'record_table' => $table,
					// uid of locked record
				'record_uid' => $uid,
					// Username of BE user who locked the record
				'username' => $GLOBALS['BE_USER']->user['username'],
					// The pid of the record being locked. Usually this is zero. Only when
					// the page on which thre record resides should also get locked this
					// will get set to the pid of the record.
				'record_pid' => $pid,
					// The "real" pid of the record being locked. Always points to the page
					// on which the locked record can be found.
				'real_pid' => $realPid,
					// Eventually a hash value is also set
				'hash' => $hash,
			);

			/*
			 * Warning: SQL debug output is disabled at this place to inhibit error
			 * messages resulting from "duplicate-key" inserts - this method is used
			 * to let no race conditions occur.
			 */
			$storeDebugOutput = $GLOBALS['TYPO3_DB']->debugOutput;
			$GLOBALS['TYPO3_DB']->debugOutput = false;
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_lockedrecords', $fields_values);
			$id = $GLOBALS['TYPO3_DB']->sql_affected_rows();
			$GLOBALS['TYPO3_DB']->debugOutput = $storeDebugOutput;
			$retVal = $id>0?true:false;
/*
 * TODO: Check if no race conditions / dead-locks can occur.
 */
		}
		return $retVal;
	}

	/**
	 * Locks a record using the new "single" locking type. A locked record can not get
	 * edited by another user at the same time.
	 *
	 * @param	string		The table to get locked
	 * @param	integer		The uid of the record going to be locked
	 * @param	integer		The pid the record going to be locked is found on
	 * @param	string		A hash/cHash value specifing the record to be locked
	 * @return	boolean		Whether the record could get locked or not
	 */
	function lock_single($table, $uid = 0, $pid = 0, $hash = '')	{
		return $this->lock($table, $uid, $pid, $hash, 'single');
	}


	/**
	 * Locks a record using the new "extended" locking type. A locked record can not get
	 * edited by another user at the same time. Also other records having the same pid
	 * (residing on the same page) can not get edited when they have apropriate settings
	 * in TCA.
 	 *
	 * @param	string		The table to get locked
	 * @param	integer		The uid of the record going to be locked
	 * @param	integer		The pid the record going to be locked is found on
	 * @param	string		A hash/cHash value specifing the record to be locked
	 * @return	boolean		Whether the record could get locked or not
	 */
	function lock_extended($table, $uid = 0, $pid = 0, $hash = '')	{
		return $this->lock($table, $uid, $pid, $hash, 'extended');
	}


	/**
	 * Unlocks all records locked by the current user when no arguments are given.
	 * When a table-name is passed all locked records of this table are unlocked.
	 * When also a "uid" is supplied only the given record of this tables will get
	 * unlocked. Returns wheter records have been unlocked or not (true/false).
	 *
	 * @param	string		The table to get unlocked
	 * @param	integer		The uid of the record going to be unlocked
	 * @param	string		A hash/cHash value specifing the record to be unlocked
	 * @return	boolean		Whether any record could get unlocked or not
	 */
	function unlock($table='', $uid=0, $hash = '', $user = 0, $tstamp = 0)	{
		if (!($userid = intval($user)))	{
			$userid = intval($GLOBALS['BE_USER']->user['uid']);
		}
		if ($GLOBALS['T3_VARS']['doExplicitLocking']) {
			$where = '(userid AND ABS(userid)='.$userid.')';
		} else {
			$where = '(userid AND userid='.$userid.')';
		}
		if ($table)	{
			$quotedTable = $GLOBALS['TYPO3_DB']->fullQuoteStr($table, 'sys_lockedrecords');
			$where .= ' AND record_table='.$quotedTable;
			if (intval($uid))	{
				$where .= ' AND record_uid='.abs(intval($uid));
			}
			if (intval($tstamp))	{
				$where .= ' AND tstamp='.intval($tstamp);
			}
			if ($hash)	{
				$quotedHash = $GLOBALS['TYPO3_DB']->fullQuoteStr($hash, 'sys_lockedrecords');
				$where .= ' AND hash='.$quotedHash;
			}
		}
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_lockedrecords', $where);
		$affected = $GLOBALS['TYPO3_DB']->sql_affected_rows();
		return $affected?true:false;
	}


	/**
	 * Unlock a record for "single" record locking mode.
	 * See method "unlock" in this class.
	 *
	 * @param	string		See method "unlock"
	 * @param	integer		See method "unlock"
	 * @param	string		See method "unlock"
	 * @return	boolean		See method "unlock"
	 */
	function unlock_single($table='', $uid=0, $hash = '', $tstamp = 0)	{
		return $this->unlock($table, $uid, $hash, $tstamp);
	}


	/**
	 * Unlock a record for "extended" record locking mode.
	 * See method "unlock" in this class.
	 *
	 * @param	string		See method "unlock"
	 * @param	integer		See method "unlock"
	 * @param	string		See method "unlock"
	 * @return	boolean		See method "unlock"
	 */
	function unlock_extended($table='', $uid=0, $hash = '', $tstamp = 0)	{
		return $this->unlock($table, $uid, $hash, $tstamp);
	}


	/**
	 * Checks wheter a given record has currently any locks set.
	 *
	 * @param	string		The table name of the record to check
	 * @param	integer		The uid of the record to check
	 * @param	string		A hash/cHash value specifing the record to be checked
	 * @return	array		An array of the locks for the record queried
	 */
	function isLocked($table, $uid=0, $hash='', $cached = true) {
		global $SV_LOCKED_RECORDS;
		$explicit = $GLOBALS['T3_VARS']['doExplicitLocking']?'1':'0';
		$cacheKey = $explicit.$table;
		$extraLocked = array();
		$lockTablesForPage = $GLOBALS['TYPO3_CONF_VARS']['BE']['lockTablesForPage'];
		if (is_array($table)) {
			$cacheKey = md5(serialize($table));
		}
		if (!$cached) {
			if (!$uid) {
				if (isset($SV_LOCKED_RECORDS[$cacheKey])) {
					$SV_LOCKED_RECORDS[$cacheKey] = array();
				}
			} else {
				if (isset($SV_LOCKED_RECORDS[$cacheKey][$uid])) {
					$SV_LOCKED_RECORDS[$cacheKey][$uid] = false;
				}
			}
		}
		if (!$SV_LOCKED_RECORDS[$cacheKey][$uid]) {
			$expire = $GLOBALS['EXEC_TIME'] - $GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockTimeout'];
			$quotedHash = $GLOBALS['TYPO3_DB']->fullQuoteStr($hash, 'sys_lockedrecords');
			$where = '';
			$where .= ' tstamp>'.$expire;
			$userid = intval($GLOBALS['BE_USER']->user['uid']);
			if ($explicit) {
				$where_u = '(userid AND ABS(userid)!='.$userid.')';
			} else {
				$where_u = '(userid AND userid!='.$userid.')';
			}
			$where .= ' AND '.$where_u;
			$uidArr = false;
			$storeWhere = $where;
			if (is_array($table)) {
					$where .= ' AND record_table IN (\''.implode('\',\'', $table).'\')';
					$where .= ' AND real_pid='.intval(abs($uid));
			} else {
				$tableName = $GLOBALS['TYPO3_DB']->fullQuoteStr($table, 'sys_lockedrecords');
				$lockRec = t3lib_BEfunc::getRecordWSOL($table, $uid);
				if ($table=='pages') {
					if ($uid) {
						$where .= ' AND ((record_table=\'pages\' AND record_uid='.intval($uid).') OR record_pid='.intval($uid).')';
					}
				} else {
					$where .= ' AND record_table='.$tableName;
					if ($uid) {
						$origUid = $lockRec['_ORIG_uid']?$lockRec['_ORIG_uid']:$lockRec['uid'];
						if ($GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingAcrossWS']) {
							$uidArr = $this->getAllWSUids($table, $uid);
							$where .= ' AND record_uid IN ('.implode(',', $uidArr).')';
						} else {
							$where .= ' AND record_uid='.intval($origUid);
						}
					}
				}
			}
			$where .= ' AND hash='.$quotedHash;
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_lockedrecords', $where);
			if (is_array($rows) && count($rows))	{
				$setRows = array();
				foreach ($rows as $idx => $row)	{
					if (is_array($table) && in_array($row['record_table'], $table))	{
						$useIdx = md5(serialize($row));
						$setRows[$useIdx] = $row;
						$setRows[$useIdx]['msg'] = $this->getLockMessage($row);
					} elseif ($row['record_table']==$table)	{
						if ($uidArr && in_array($row['record_uid'], $uidArr) && $uid!=$row['record_uid']) {
								// WS handling
							$row['record_uid'] = $uid;
						}
						$row['msg'] = $this->getLockMessage($row);
						$useIdx = md5(serialize($row));
						$setRows[$useIdx] = $row;
					}
//					if ($row['record_pid'] && $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lockadmin']['pageContentEditWarning'])	{
					if ($row['record_pid']) {
						$msg = $this->getLockMessage($row, 'LLL:EXT:lang/locallang_core.php:labels.lockedRecord_content');
//						$tmpRow = array('msg' => $msg, 'uid' => $row['record_pid'], 'record_table' => 'pages', 'tstamp' => $row['tstamp'], 'username' => $row['username'], 'contentLocked' => true);
						$tmpRow = array('msg' => $msg, 'uid' => $row['record_pid'], 'record_table' => 'pages', 'tstamp' => $row['tstamp'], 'username' => $row['username'], 'contentLocked' => true);
						$useIdx = md5(serialize($tmpRow));
						$SV_LOCKED_RECORDS[$explicit.'pages'][$row['record_pid']][$useIdx] = $tmpRow;
					}
					if (($table==='pages') && is_array($lockTablesForPage) && count($lockTablesForPage)) {
						foreach ($lockTablesForPage as $lockTableForPage) {
							$extraRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', $lockTableForPage, 'pid='.$uid);
							if (is_array($extraRows)) {
								foreach ($extraRows as $extraRow) {
									$tmpRow = array('record_uid' => $extraRow['uid'], 'record_table' => $lockTableForPage, 'real_pid' => $extraRow['pid'], 'tstamp' => $row['tstamp'], 'username' => $row['username'], 'userid' => $row['userid']);
									$tmpMsg = $this->getLockMessage($tmpRow, 'LLL:EXT:lockadmin/locallang.xml:lockedRecord_pageBeingEdited');
									$tmpRow['msg'] = $tmpMsg;
									$useIdx = md5(serialize($tmpRow));
									$SV_LOCKED_RECORDS[$explicit.$lockTableForPage][$extraRow['uid']][$useIdx] = $tmpRow;
									$extraLocked[$useIdx] = $tmpRow;
								}
							}
						}
					}
				}
				$useUid = $row['record_uid'];
				if (is_array($table))	{
					$useUid = $uid;
				}
				$SV_LOCKED_RECORDS[$cacheKey][$useUid] = $setRows;
			}
			if (!is_array($table) && ($table!=='pages') && is_array($lockTablesForPage) && count($lockTablesForPage)) {
				if (in_array($table, $lockTablesForPage)) {
					$where = $storeWhere;
					$where .= ' AND record_table=\'pages\' AND record_uid='.$lockRec['pid'];
					$where .= ' AND hash='.$quotedHash;
					$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_lockedrecords', $where);
					if (is_array($rows) && count($rows)) {
						foreach ($rows as $lockRow) {
							$tmpRow = array('record_uid' => $lockRec['uid'], 'record_table' => $table, 'real_pid' => $lockRec['pid'], 'tstamp' => $lockRow['tstamp'], 'username' => $lockRow['username'], 'userid' => $lockRow['userid']);
							$tmpMsg = $this->getLockMessage($tmpRow, 'LLL:EXT:lockadmin/locallang.xml:lockedRecord_pageBeingEdited');
							$tmpRow['msg'] = $tmpMsg;
							$useIdx = md5(serialize($tmpRow));
							$SV_LOCKED_RECORDS[$explicit.$table][$uid][$useIdx] = $tmpRow;
						}
					}
				}
			}
		}
		if (is_array($table) || !$uid) {
			$ret = array();
			if (is_array($SV_LOCKED_RECORDS[$cacheKey]))	{
				foreach ($SV_LOCKED_RECORDS[$cacheKey] as $lockedRecs)	{
					if (is_array($lockedRecs))	{
						foreach ($lockedRecs as $useIdx => $lockRow) {
							$ret[$useIdx] = $lockRow;
						}
					}
				}
			}
			return $ret;
		} elseif (($table==='pages') && $lockTablesForPage && $uid && count($extraLocked)) {
			$ret = $SV_LOCKED_RECORDS[$cacheKey][$uid];
			foreach ($extraLocked as $idx => $lockRow) {
				$ret[$idx] = $lockRow;
			}
			return $ret;
		}
		return $SV_LOCKED_RECORDS[$cacheKey][$uid];
	}


	function getAllWSUids($table, $uid)	{
		$rec = t3lib_BEfunc::getRecord($table, $uid);
		if ($rec['pid']<0)	{
			$origUid = $rec['t3_origuid'];
		} else	{
			$origUid = $rec['uid'];
		}
		$uids = array($origUid);
		$recs = t3lib_BEfunc::getRecordsByField($table, 'pid', -1, ' AND t3_origuid='.$origUid);
		if (is_array($recs) && count($recs))	{
			foreach ($recs as $rec)	{
				$uids[] = $rec['uid'];
			}
		}
		return $uids;
	}


	/**
	 * Checks wheter a given record has currently any locks set.
	 *
	 * @param	string		See "isLocked"
	 * @param	integer		See "isLocked"
	 * @param	string		See "isLocked"
	 * @return	array		See "isLocked"
	 */
	function isLocked_single($table, $uid=0, $hash='', $cached = true)	{
		return $this->isLocked($table, $uid, $hash, $cached);
	}


	/**
	 * Checks wheter a given record has currently any locks set.
	 *
	 * @param	string		See "isLocked"
	 * @param	integer		See "isLocked"
	 * @param	string		See "isLocked"
	 * @return	array		See "isLocked"
	 */
	function isLocked_extended($table, $uid=0, $hash='', $cached = true)	{
		if ($GLOBALS['TCA'][$table]['ctrl']['extendedLocking'])	{
			if (!$GLOBALS['T3_VARS']['lockadmin_extTables'])	{
				$GLOBALS['T3_VARS']['lockadmin_extTables'] = array();
				$tableKeys = array_keys($GLOBALS['TCA']);
				foreach ($tableKeys as $tableKey)	{
					if ($GLOBALS['TCA'][$tableKey]['ctrl']['extendedLocking'])	{
						$GLOBALS['T3_VARS']['lockadmin_extTables'][] = $tableKey;
					}
				}
			}
			$rec = t3lib_BEfunc::getRecord($table, $uid);
			if ($rec)	{
				$ret = $this->isLocked($GLOBALS['T3_VARS']['lockadmin_extTables'], -$rec['pid'], $hash, $cached);
				foreach ($ret as $idx => $lRec)	{
					if (($lRec['real_pid']==$rec['pid']) && in_array($table, $GLOBALS['T3_VARS']['lockadmin_extTables']))	{
						$ret[$idx]['record_uid'] = $uid;
						$ret[$idx]['record_table'] = $table;
					}
				}
				return $ret;
			} else	{
				return false;
			}
		} else	{
			return $this->isLocked($table, $uid, $hash, $cached);
		}
	}


	/**
	 * Returns the message to get displayed next to any locked record
	 *
	 * @param	array		The database row of the lock
	 * @param	string		The locallang-label to get used for the locking message
	 * @return	string		The lock message
	 */
	function getLockMessage($row, $label = 'LLL:EXT:lang/locallang_core.php:labels.lockedRecord') {
		if (!is_object($GLOBALS['LANG'])) {
			require_once(t3lib_extMgm::extPath('lang').'lang.php');
			$GLOBALS['LANG'] = t3lib_div::makeInstance('language');
			$GLOBALS['LANG']->init($BE_USER->uc['lang']);
		}
		return sprintf($GLOBALS['LANG']->sL($label), $row['username'], t3lib_BEfunc::calcAge($GLOBALS['EXEC_TIME']-$row['tstamp'], $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears')));
	}


	function getAllLocks($table = '', $uid = 0) {
			$expire = $GLOBALS['EXEC_TIME']-$GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockTimeout'];
			$where = '';
			if ($table) {
				$tableName = $GLOBALS['TYPO3_DB']->fullQuoteStr($table, 'sys_lockedrecords');
				$where .= ' AND record_table='.$tableName;
			}
			if ($uid) {
				$where .= ' AND record_uid='.intval($uid);
			}
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*, if(tstamp>'.$expire.', 0, 1) AS expired, if(userid_nokey, userid_nokey, userid) AS userid', 'sys_lockedrecords', $where);
				// Todo: handle "pages" locks and "extended locks", set expire flag
			return $rows;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__]);
}

?>
