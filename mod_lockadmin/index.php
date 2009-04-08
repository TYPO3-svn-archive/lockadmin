<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Bernhard Kraft (kraftb@kraftb.at)
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
* Lock Admin
*
* @author Bernhard Kraft <kraftb@kraftb.at>
*/

unset($MCONF);
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:lockadmin/mod_lockadmin/locallang.php');
require_once(PATH_t3lib.'class.t3lib_scbase.php');
require_once(PATH_t3lib.'class.t3lib_befunc.php');

$extDir = t3lib_extMgm::extPath('lockadmin');

require_once($extDir.'mod_lockadmin/class.tx_kbshop_t3tt.php');
require_once($extDir.'class.tx_lockadmin_funcs.php');

$BE_USER->modAccess($MCONF, 1);


class SC_mod_lockadmin extends t3lib_SCbase	{
	var $backPath;
	var $allowRemove = false;
	var $templateFile = array(
//		'view_locks' => 'EXT:lockadmin/res/view_locks.html',
		'clear_locks' => 'EXT:lockadmin/res/clear_locks.html',
		'send_message' => 'EXT:lockadmin/res/send_message.html',
	);
	var $errors = array();


	/**
	 * Configure menu
	 *
	 * @return	void
	 */
	function menuConfig()	{
		global $LANG;

		$this->MOD_MENU = array(
			'function' => array(
//				'view_locks' => 'View locks',
				'clear_locks' => 'Clear locks',
				'send_message' => 'Send message',
			),
		);
	}

	/**
	 * This is the main function called by the TYPO3 framework
	 *
	 * @return	string		The conntent of the module (HTML)
	 */
	function main() {
		global $BE_USER, $LANG, $BACK_PATH, $TCA_DESCR, $TCA, $CLIENT, $TYPO3_CONF_VARS;

		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingExplicit']) {
			// TODO: Check access
			$lockRecords = t3lib_div::_GP('lockRecords');
			$redirect = t3lib_div::_GP('redirect');
			$GLOBALS['T3_VARS']['doExplicitLocking'] = true;
			if (is_array($lockRecords) && count($lockRecords)) {
				foreach ($lockRecords as $table => $lockUids) {
					foreach ($lockUids as $uid => $lock) {
						if ($lock) {
							tx_lockadmin_funcs::lockRecords($table, $uid);
						} else {
							tx_lockadmin_funcs::lockRecords($table, -$uid);
						}
					}
				}
			}
			if ($redirect) {
				header('Location: '.t3lib_Div::locationHeaderUrl($redirect));
				exit();
			}
		}

		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name'], 'ses');

		/* Setup document template */
		$this->doc = t3lib_div::makeInstance('noDoc');
		$this->doc->docType = 'xhtml_trans';
		$this->doc->divClass = '';
		$this->doc->form = '<form action="index.php" method="POST" name="editform">';
		$this->backPath = $this->doc->backPath = $BACK_PATH;
				// JavaScript
		$this->doc->JScode = '
		<script language="javascript" type="text/javascript">
			script_ended = 0;
			function jumpToUrl(URL)	{
				window.location.href = URL;
			}
		</script>
		';


		$this->content = '';
		$this->content .= $this->doc->startPage($this->MOD_MENU['function'][$this->MOD_SETTINGS['function']]);
		$this->content .= $this->doc->header($LANG->getLL('title'));
		$this->content .= $this->doc->spacer(5);
		$menu = t3lib_BEfunc::getFuncMenu(0, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);
		$this->content .= $this->doc->section('', $menu);

		$mainContent = $this->moduleContent();
		$this->content .= $this->doc->section('', $mainContent);

		if (is_array($this->errors) && count($this->errors))	{
			$errorContent = $this->moduleErrors();
			$this->content .= $this->doc->section('', $errorContent);
		}

	}


	
	function moduleErrors()	{
		return '<p class="error">'.implode('</p><p class="error">', $this->errors).'</p>';
	}

	function moduleContent()	{
		$content = '';

//		$this->MOD_SETTINGS['function'] = 'clear_locks';

		$TSconfig = $GLOBALS['BE_USER']->getTSConfig('mod.'.$this->MCONF['name']);
		$this->TSconfig = $TSconfig['properties'];
		$this->subConfig = $this->TSconfig[$this->MOD_SETTINGS['function'].'.'];
		if (($templateFile = $this->getTemplateFile())===false)	{
			return;
		}
		$templateData = t3lib_div::getURL($templateFile);
		$dataArray = array();
		switch($this->MOD_SETTINGS['function']) {

				// Always use this function:
			case 'clear_locks':
				$this->takeAction();
				$this->allowRemove = true;
				$this->render_viewLocks($dataArray);
			break;

			case 'send_message':
				if ($this->sendMessage())	{
					return '<br /><br /><strong>Message sent successfully !</strong><br /><br />';
				} else	{
					$this->render_sendMessage($dataArray);
				}
			break;

				// Never !
			default:
			case 'view_locks':
				$this->render_viewLocks($dataArray);
			break;
		}
		$t3tt = t3lib_div::makeInstance('tx_kbshop_t3tt');
		$t3tt->init($this->TSconfig);
		$t3tt->markerWrap = '###|###';
		$content = $t3tt->substituteSubpartsAndMarkers_Tree($templateData, $dataArray);
		return $content;
	}


	function sendMessage()	{
		$recipient = intval(t3lib_div::_GP('recipient'));
		$message = t3lib_div::_GP('message');
		$urgent = intval(t3lib_div::_GP('urgent'))?1:0;
		$pid = intval($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lockadmin']['messagePid']);
		if (!($pid && $message && $recipient))	{
			return false;
		}
		$msg = array(
			'pid' => $pid,
			'tstamp' => time(),
			'crdate' => time(),
			'cruser_id' => $GLOBALS['BE_USER']->user['uid'],
			'recipient' => $recipient,
			'message' => strip_tags($message),
			'urgent' => $urgent,
		);
		tx_lockadmin_funcs::lock();
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_lockadmin_messages', $msg);
		$id = $GLOBALS['TYPO3_DB']->sql_insert_id();
		$GLOBALS['TYPO3_DB']->dataAvailable();
		tx_lockadmin_funcs::unlock();
		return $id;
	}


	function takeAction()	{
		$cmd = t3lib_div::_GP('cmd');
		$lock = t3lib_div::_GP('lock');
		switch ($cmd)	{
			case 'remove':
				$this->action_remove($lock);
			break;
		}
	}

	function action_remove($lock)	{
		$userid = $lock['userid']?$lock['userid']:$lock['userid_nokey'];
		if (!$userid)	{
			return;
		}
		tx_lockadmin_funcs::unlockRecord($lock['record_table'], $lock['record_uid'], $lock['hash'], $userid);
	}


	function render_sendMessage(&$markerArray)	{
		$currentSessions = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'be_sessions', 'ses_tstamp>(unix_timestamp()-3600)', 'ses_userid');
		$userArray = array();
		foreach ($currentSessions as $session)	{
				if ($session['ses_userid']==$GLOBALS['BE_USER']->user['uid'])	{
					continue;
				}
				$userRec = t3lib_BEfunc::getRecord('be_users', $session['ses_userid']);
				$userRow = array();
				foreach ($userRec as $field => $value)	{
					$userRow['user_'.$field] = $value;
				}
				$userArray[]['_MARKERS'] = $userRow;
		}
		$markerArray = array(
			'_SUBPARTS' => array(
				'LIST__USERS' => array(
					'_MULTIPLE_SUBPARTS' => 1,
					'_SUBPARTS' => array(
						'ITEM__USERS' => $userArray,
					),
				),
			),
		);
	}

	function render_viewLocks(&$markerArray)	{
		$locks = tx_lockadmin_funcs::getAllLocks();
		$BE_USER_cache = array();
		$LOCKREC_cache = array();
		if (is_array($locks) && count($locks))	{
			foreach ($locks as $idx => $lock)	{
				$origLock = $lock;
				$this->getRelRecord($locks[$idx], $BE_USER_cache, 'be_users', 0, '', 'userid', 'be_user__');
				$orig = $this->getRelRecord($locks[$idx], $LOCKREC_cache, '', 0, 'record_table', 'record_uid', 'lockrec__');
				$title = t3lib_BEfunc::getRecordTitle($lock['record_table'], $orig);
				$locks[$idx]['lockrec__TITLE__'] = $title;
				$age = $GLOBALS['EXEC_TIME']-$locks[$idx]['tstamp'];
				$locks[$idx]['tstamp_label'] = strftime('%Y-%m-%d %H:%M:%S', $locks[$idx]['tstamp']);
				$locks[$idx]['tstamp_age'] = t3lib_BEfunc::calcAge($age);
				if ($this->allowRemove)	{
					$locks[$idx]['LINK_removeLock'] = $this->getLink('remove', $origLock);
				}
				$locksArray[$idx]['_MARKERS'] = $locks[$idx];
				$subparts = array(
					'LOCKITEM_expired' => $lock['expired']?true:false,
				);
				$locksArray[$idx]['_SUBPARTS'] = $subparts;
			}
		}
		if ($locksArray)	{
			$markerArray = array(
				'_SUBPARTS' => array(
					'LIST__CURRENT_LOCKS' => array(
						'_MULTIPLE_SUBPARTS' => 1,
						'_SUBPARTS' => array(
							'ITEM__CURRENT_LOCKS' => $locksArray,
						),
					),
				),
			);
		}
	}


	function getLink($type, $origLock)	{
		switch ($type)	{
			case 'remove':
				return t3lib_div::linkThisScript(array('cmd' => 'remove', 'lock' => $origLock));
			break;
		}
		return '#';
	}


	function prefixArray($arr, $prefix)	{
		$ret = array();
		if (is_array($arr)) {
			foreach ($arr as $key => $value)	{
				$ret[$prefix.$key] = $value;
			}
		}
		return $ret;
	}

	function getRelRecord(&$lock, &$cache, $table, $uid, $tablefield, $uidfield, $prefix)	{
		if ($uidfield)	{
			$useuid = intval($lock[$uidfield]);
		} else	{
			$useuid = intval($uid);
		}
		if ($tablefield)	{
			$usetable = $lock[$tablefield];
		} else	{
			$usetable = $table;
		}
		if (!$cache[$usetable][$useuid])	{
			$rec = t3lib_BEfunc::getRecord($usetable, $useuid);
			$cache[$usetable]['orig__'.$useuid] = $rec;
			$cache[$usetable][$useuid] = $this->prefixArray($rec, $prefix);
		}
		if ($cache[$usetable][$useuid])	{
			$lock = array_merge($lock, $cache[$usetable][$useuid]);
			return $cache[$usetable]['orig__'.$useuid];
		}
	}


	function getTemplateFile()	{
		$filename = t3lib_div::getFileAbsFileName($this->subConfig['templateFile']);
		if (!(is_file($filename) && is_readable($filename)))	{
			$filename = t3lib_div::getFileAbsFileName($this->templateFile[$this->MOD_SETTINGS['function']]);
		}
		if (!(is_file($filename) && is_readable($filename)))	{
			$this->errors[] = 'No template file available !';
			return false;
		}
		return $filename;
		
	}


	/**
	 * Output the content of the object to the browser
	 *
	 * @return	void
	 */
	function printContent() {
		$this->content .= $this->doc->endPage();
		echo $this->content;
	}


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/mod_lockadmin/index.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/mod_lockadmin/index.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_lockadmin');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();


?>
