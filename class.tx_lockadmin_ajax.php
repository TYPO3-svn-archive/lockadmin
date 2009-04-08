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


if(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX) {
	require_once(PATH_typo3.'sysext/lang/lang.php');

	$GLOBALS['LANG'] = t3lib_div::makeInstance('language');
	$GLOBALS['LANG']->init($GLOBALS['BE_USER']->uc['lang']);
	$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_misc.xml');
}

require_once(PATH_lockadmin.'class.tx_lockadmin_funcs.php');

/**
 * class to return the current locking state of a record
 *
 * @author	Bernhard Kraft <kraftb@kraftb.at>
 * @package	TYPO3
 * @subpackage	lockadmin
 */
class tx_lockadmin_ajax	{
	var $minPort = 23000;
	var $maxPort = 24000;

	/**
	 * constructor, initializes several variables
	 *
	 * @return	void
	 */
	public function __construct()	{
		$this->timeout = intval(floatval(ini_get('max_execution_time'))*0.8);
		$this->startTime = time();
		$this->endTime = $this->startTime+$this->timeout;

		$this->backPath = t3lib_div::_GP('backPath');
		$this->spacer = intval(t3lib_div::_GP('spacer'));
		
		$this->recordLockingExplicit = intval($GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingExplicit']);
	}

	public function __destruct()	{
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

	function parseCheckString($var = 'check', $key = -1, $value = -1)	{
		$checkStr = t3lib_div::_GP($var);
		$tmpArr = t3lib_div::trimExplode('.', $checkStr, 1);
		$checkArr = array();
		foreach ($tmpArr as $check)	{
			$check = trim($check);
			if (!$check)	{
				continue;
			}
			$tmp = t3lib_div::trimExplode('-', $check);
			if ($key>=0)	{
				if ($value>=0)	{
					$checkArr[$tmp[$key]] = $tmp[$value];
				} else	{
					$checkArr[$tmp[$key]] = $tmp;
				}
			} else	{
				if ($value>=0)	{
					$checkArr[] = $tmp[$value];
				} else	{
					$checkArr[] = $tmp;
				}
			}
		}
		return $checkArr;
	}


	function checkLocked($checkArr)	{
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lockadmin']['pageContentEditWarning'])	{
			$checkArr[] = array('pages', 0, false);
		}
		$pageStatus = $this->parseCheckString('pageStatus', 0, 1);
		$checkAllPages = false;
		foreach ($checkArr as $checkRec)	{
			if (($checkRec[0]=='pages') && !$checkRec[1])	{
				$checkAllPages = true;
			}
		}
		$newCheckArr = array();
		foreach ($checkArr as $checkRec)	{
			if (!(($checkRec[0]=='pages') && $checkRec[1] && $checkAllPages))	{
				$newCheckArr[] = $checkRec;
			}
		}
		$checkArr = $newCheckArr;
		$GLOBALS['T3_VARS']['logObj'] = &$this;
		foreach ($checkArr as $checkRec)	{
			$locked = tx_lockadmin_funcs::isRecordLocked($checkRec[0], $checkRec[1], false);
			$lockedExplicit = false;
			if ($this->recordLockingExplicit) {
				$GLOBALS['T3_VARS']['doExplicitLocking'] = $this->recordLockingExplicit;
				$lockedExplicit = tx_lockadmin_funcs::isRecordLocked($checkRec[0], $checkRec[1], false);
				$GLOBALS['T3_VARS']['doExplicitLocking'] = false;
			}
			$isLocked = (is_array($locked) && count($locked))?1:0;
			$isLockedExplicit = (is_array($lockedExplicit) && count($lockedExplicit))?1:0;
			if ($checkRec[2]!==false)	{
				$currentStatus = intval($checkRec[2]);
				if (($currentStatus===1)&&$isLocked&&($isLockedExplicit || !$this->recordLockingExplicit)) {
					continue;
				}
				if (($currentStatus===2)&&$isLocked&&!$isLockedExplicit) {
					continue;
				}
				if (($currentStatus===0)&&!($isLocked||$isLockedExplicit)) {
					continue;
				}
//				if ($isLocked==$currentStatus) continue;
			}
			$msg = '';
			$box = '';
			if ($isLocked)	{
				foreach ($locked as $useIdx => $lockedRec)	{
					$explicit = 0;
					$lockedRecExplicit = $lockedExplicit[$useIdx];
					if ($this->recordLockingExplicit && !$lockedRecExplicit) {
						$explicit = 1;
					}
					$lockedUid = (($lockedRec['record_table']=='pages') && $lockedRec['uid']) ? $lockedRec['uid'] : $lockedRec['record_uid'];
					if (($lockedRec['record_table']=='pages') && $pageStatus[$lockedUid])	{
						continue;
					}
					$msg = '';
					$box = '';
					if ($lockedRec['msg'] && !$lockedRec['userid'])	{
						$type = 'content';
						$box = tx_lockadmin_funcs::getLockBoxes($checkRec[0], $checkRec[1], $this->backPath, true);
						$msg = tx_lockadmin_funcs::getLockBoxes($checkRec[0], $checkRec[1], $this->backPath, false);
					} elseif ($lockedRec['msg'] && $lockedRec['userid'])	{
						$type = 'record';
						$msg = tx_lockadmin_funcs::getLockBoxes($checkRec[0], $checkRec[1], $this->backPath, false, $this->spacer);
					}
					$age = t3lib_BEfunc::calcAge($GLOBALS['EXEC_TIME']-$lockedRec['tstamp'], $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears'));
					$user = $lockedRec['username'];
					$realPid = $lockedRec['real_pid'];
//					$resArr[] = '<record><locked>'.$isLocked.'</locked><table>'.$lockedRec['record_table'].'</table><uid>'.$lockedUid.'</uid><box><![CDATA['.$box.']]></box><user>'.$user.'</user><age>'.$age.'</age><type>'.$type.'</type></record>';
					$resArr[] = '<record><locked>'.$isLocked.'</locked><table>'.$lockedRec['record_table'].'</table><uid>'.$lockedUid.'</uid><user>'.$user.'</user><age>'.$age.'</age><type>'.$type.'</type><pid>'.$realPid.'</pid><contentLocked>'.$lockedRec['contentLocked'].'</contentLocked><explicit>'.$explicit.'</explicit></record>';
				}
			} else {
				if ($checkRec[1]) {
					if ($checkRec[0]==='pages_language_overlay') {
						$lockedRec = t3lib_BEfunc::getRecord($checkRec[0], $checkRec[1]);
						$msg = '<img src="'.$this->backPath.'clear.gif" width="'.$this->spacer.'" height="1" />';
						$resArr[] = '<record><locked>'.$isLocked.'</locked><table>'.$checkRec[0].'</table><uid>'.$checkRec[1].'</uid><user></user><age></age><type></type><pid>'.$lockedRec['pid'].'</pid><contentLocked></contentLocked><explicit></explicit></record>';
					} else {
						$msg = '<img src="'.$this->backPath.'clear.gif" width="'.$this->spacer.'" height="1" />';
						$resArr[] = '<record><locked>'.$isLocked.'</locked><table>'.$checkRec[0].'</table><uid>'.$checkRec[1].'</uid><user></user><age></age><type></type><pid></pid><contentLocked></contentLocked><explicit></explicit></record>';
					}
				} elseif ($checkRec[0]==='pages') {
					if (count($pageStatus)) {
						$set = intval(implode('', $pageStatus));
						if ($set) {
							$resArr[] = '<record><locked>0</locked><table>'.$checkRec[0].'</table><uid>-1</uid><user></user><age></age><type></type><pid></pid><contentLocked></contentLocked><explicit></explicit></record>';
						}
					}
				}
			}
		}
		return $resArr;
	}

	/**
	 * Returns the locking state of requested record as AJAX reply
	 *
	 * @param	array		array of parameters from the AJAX interface, currently unused
	 * @param	TYPO3AJAX	object of type TYPO3AJAX
	 * @return	void
	 */
	public function clearLocksAjax($params = array(), TYPO3AJAX &$ajaxObj = null)	{
		$clear = $this->parseCheckString('records');
		$tstamp = intval(t3lib_div::_GP('lockTime'));
		foreach ($clear as $recInfo)	{
			$GLOBALS['T3_VAR']['inhibitLockNotify']	= 1;
			tx_lockadmin_funcs::unlockRecord($recInfo[0], $recInfo[1], '', 0, $tstamp);
			$GLOBALS['T3_VAR']['inhibitLockNotify']	= 0;
			tx_lockadmin_funcs::lock();
			$GLOBALS['TYPO3_DB']->dataAvailable();
			tx_lockadmin_funcs::unlock();
		}
	}

	public function getNewMessages()	{
		$user = intval($GLOBALS['BE_USER']->user['uid']);
		$messages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_lockadmin_messages', 'recipient='.$user.' AND sent=0');
		$uidList = array();
		foreach ($messages as $message)	{
			$text = $message['message'];
			$text = nl2br($text);
			$tstamp = $message['tstamp'];
			$from_uid = intval($message['cruser_id']);
			$from_rec = t3lib_BEfunc::getRecord('be_users', $from_uid);
			$from_username = $from_rec['username'];
			$resArr[] = '<message><from_uid>'.$from_uid.'</from_uid><from_username>'.$from_username.'</from_username><message><![CDATA['.$text.']]></message><tstamp>'.$tstamp.'</tstamp><urgent>'.intval($message['urgent']).'</urgent></message>';
			$uidList[] = $message['uid'];
		}
		$uidStr = implode(',', $uidList);
		if ($uidStr)	{
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_lockadmin_messages', 'uid IN ('.$uidStr.')', array('sent' => 1));
		}
		return $resArr;
	}

	/**
	 * Returns the locking state of requested record as AJAX reply
	 *
	 * @param	array		array of parameters from the AJAX interface, currently unused
	 * @param	TYPO3AJAX	object of type TYPO3AJAX
	 * @return	void
	 */
	public function checkLockAjax($params = array(), TYPO3AJAX &$ajaxObj = null) {
		$checkArr = $this->parseCheckString();

		$resArr = array();

		tx_lockadmin_funcs::lock();
		$resArr = $this->checkLocked($checkArr);
		$msgArr = $this->getNewMessages();
		if (!count($resArr))	{
			$eventOccured = $this->wait4change();
			if ($eventOccured)	{
				$resArr = $this->checkLocked($checkArr);
				$msgArr = $this->getNewMessages();
			}
		} else	{
			tx_lockadmin_funcs::unlock();
		}
		if (count($msgArr)||count($resArr))	{
			if (!is_array($msgArr))	{
				$msgArr = array();
			}
			if (!is_array($resArr))	{
				$resArr = array();
			}
			$msgStr = '';
			if (count($msgArr)) {
				$msgStr = '<messages>'.implode(chr(10), $msgArr).'</messages>';
			}
			$lockingResult = '<t3ajax>'.$msgStr.'<records>'.implode(chr(10), $resArr).'</records></t3ajax>';
		} else	{
			$lockingResult = '<t3ajax><again>1</again></t3ajax>';
		}


		$ajaxObj->addContent('locking-result', $lockingResult);
		$ajaxObj->setContentFormat('xml');
	}
	

	function fallback()	{
		sleep(4);
		if (time()>$this->endTime)	{
			return true;
		}
		return false;
	}

	function wait4change()	{
		$timeout = intval($this->endTime-time());
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lockadmin']['sleeperBin'])	{
			$sleeperBin = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lockadmin']['sleeperBin'];
			if (substr($sleeperBin, 0, 1)!='/')	{
				$sleeperBin = PATH_site.$sleeperBin;
			}
		} else	{
			$sleeperBin = t3lib_extMgm::extPath('lockadmin').'sleeper';
		}
		$tempPath = tx_lockadmin_funcs::lockfileName(0, 'path');
		$before = time();
		$pd = popen($sleeperBin.' '.escapeshellarg($tempPath).' '.$timeout, 'r');
		$pid = fread($pd, 20);
		tx_lockadmin_funcs::unlock();
	
		// Returns 0 when nothing happend and waiting finished. When process got
		// interrupted an integer>0 gets returned
		$ret = pclose($pd);
		$after = time();
		$lockfile = tx_lockadmin_funcs::lockfileName($pid);
		tx_lockadmin_funcs::lock();
		@unlink($lockfile);
		tx_lockadmin_funcs::unlock();
		return $ret;
	}


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__]);
}

?>
