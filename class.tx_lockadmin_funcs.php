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
 * classes containing supporting methods for locking. Called by reference
 *
 * @author	Bernhard Kraft  <kraftb@kraftb.at>
 * @package	TYPO3
 * @subpackage	lockadmin
 */
class tx_lockadmin_funcs	{

	/**
	 * Unlock or Lock a record from $table with $uid
	 * If $table or $uid is not set, then all locking for the current BE_USER is removed!
	 * Usage: 5
	 *
	 * @param	string		Table name
	 * @param	integer		Record uid
	 * @param	integer		Record pid
	 * @return	NULL/bool	When no locking-method could get used NULL gets returned. When record locking/unlocking was blocked/succeded a boolean value false/true gets returned.
	 * @internal
	 * @see t3lib_transferData::lockRecord(), alt_doc.php, db_layout.php, db_list.php, wizard_rte.php
	 */
	function lockRecords($table='',$uid=0,$pid=0)	{
		$locked = NULL;
		if ($table && $uid>0)	{
			$lockType = 'lock';
		} else	{
			$lockType = 'unlock';
		}
		if ($lockingMethod = $GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingMode'])	{
			$lockType .= '_'.$lockingMethod;
		}
		/*
		 * Different record locking methods can get achieved by installing different
		 * locking service extensions and setting priority appropriate. Reason for having
		 * configuration variable allowing to set locking Method ?
		*/
		if ($mechanism = $GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockMechanism'])	{
			$lockMechanism .= 'lock_'.$mechanism;
		} else	{
			$lockMechanism .= 'lock_db';
		}
		$serviceChain = '';
		while (($locked===NULL) && is_object($serviceObj = t3lib_div::makeInstanceService('locking', $lockMechanism, $serviceChain)))	{
			$serviceChain .= ','.$serviceObj->getServiceKey();
			if ($table && $uid)	{
				$locked = $serviceObj->$lockType($table, $uid, $pid, '');
			} else	{
				$locked = $serviceObj->$lockType($table, $uid);
			}
			unset($serviceObj);
			if (is_bool($locked))	{
				break;
			}
		}
		return $locked;
	}

	/**
	 * Unlock a record from $table with $uid and probably $user/$hash set
	 *
	 * @param	string		Table name
	 * @param	integer		Record uid
	 * @param	string 		Lock hash
	 * @return	NULL/bool	Wheter unlocking has succeded / TODO !!!
	 */
	function unlockRecord($table='',$uid=0, $hash='',$user=0, $tstamp = 0)	{
		/*
		 * Different record locking methods can get achieved by installing different
		 * locking service extensions and setting priority appropriate. Reason for having
		 * configuration variable allowing to set locking Method ?
		*/
		if ($mechanism = $GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockMechanism'])	{
			$lockMechanism .= 'unlock_'.$mechanism;
		} else	{
			$lockMechanism .= 'unlock_db';
		}
		$serviceChain = '';
		$ok = false;
		while (($locked===NULL) && is_object($serviceObj = t3lib_div::makeInstanceService('locking', $lockMechanism, $serviceChain)))	{
			$serviceChain .= ','.$serviceObj->getServiceKey();
			$ok |= $serviceObj->unlock($table, $uid, $hash, $user, $tstamp);
			unset($serviceObj);
		}
		return $ok;
	}

	function isRecordLocked($table, $uid, $cached = true)	{
		$locked = array();
		$method = 'isLocked';
		if ($lockingMethod = $GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingMode'])	{
			$method .= '_'.$lockingMethod;
		}
		/*
		 * Different record locking methods can get achieved by installing different
		 * locking service extensions and setting priority appropriate. Reason for having
		 * configuration variable allowing to set locking Method ?
		*/
		if ($mechanism = $GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockMechanism'])	{
			$lockMechanism .= 'isLocked_'.$mechanism;
		} else	{
			$lockMechanism .= 'isLocked_db';
		}
		$serviceChain = '';
		while (is_object($serviceObj = t3lib_div::makeInstanceService('locking', $lockMechanism, $serviceChain)))	{
			$serviceChain .= ','.$serviceObj->getServiceKey();
			$tmp_locked = $serviceObj->$method($table, $uid, '', $cached);
			unset($serviceObj);
			if (is_array($tmp_locked)&&count($tmp_locked))	{
				$locked = array_merge($locked, $tmp_locked);
			}
		}
		return $locked;
	}

	function getAllLocks($table = '', $uid = 0)	{
		/*
		 * Different record locking methods can get achieved by installing different
		 * locking service extensions and setting priority appropriate. Reason for having
		 * configuration variable allowing to set locking Method ?
		*/
		if ($mechanism = $GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockMechanism'])	{
			$lockMechanism .= 'getAllLocks_'.$mechanism;
		} else	{
			$lockMechanism .= 'getAllLocks_db';
		}
		$serviceChain = '';
		$locked = array();
		while (is_object($serviceObj = t3lib_div::makeInstanceService('locking', $lockMechanism, $serviceChain)))	{
			$serviceChain .= ','.$serviceObj->getServiceKey();
			$tmp_locked = $serviceObj->getAllLocks($table, $uid);
			unset($serviceObj);
			if (is_array($tmp_locked)&&count($tmp_locked))	{
				$locked = array_merge($locked, $tmp_locked);
			}
		}
		return $locked;
	}

	function getLockBoxes($table, $uid, $backPath, $box = true, $spacer = 0, $onlyOne = false)	{
		$lockIcons = array();
		if (is_array($lockInfo = tx_lockadmin_funcs::isRecordLocked($table,$uid)) && count($lockInfo))	{
			foreach ($lockInfo as $lockInfoRow)	{
				if ($box)	{
					$res = tx_lockadmin_funcs::lockInfoBox($lockInfoRow, $backPath);
					if (trim($res)) {
						$lockIcons[] = trim($res);
					}
				} else	{
					$res = tx_lockadmin_funcs::lockInfoMessage($lockInfoRow, $backPath);
					if (trim($res)) {
						$lockIcons[] = trim($res);
					}
				}
			}
		}
		if ($spacer && !($box || count($lockIcons)))	{
			$lockIcons[] = '<img src="'.$backPath.'clear.gif" width="'.$spacer.'" height="1"/>';
		}
		if ($onlyOne) {
			return array_pop($lockIcons);
		}
		return implode('', $lockIcons);
	}

	function lockInfoBox($lockInfo, $backPath)	{
		return '
			<!-- Warning box: -->
			<table border="0" cellpadding="0" cellspacing="0" class="warningbox">
				<tr>
					<td><img'.t3lib_iconWorks::skinImg($backPath,'gfx/recordlock_warning3.gif','width="17" height="12"').' alt="" /></td>
					<td>'.htmlspecialchars($lockInfo['msg']).'</td>
				</tr>
			</table>
		';
	}

	function lockInfoMessage($lockInfo, $backPath)	{
		return '<a href="#" onclick="'.htmlspecialchars('alert('.$GLOBALS['LANG']->JScharCode($lockInfo['msg']).');return false;').'"><img'.t3lib_iconWorks::skinImg($backPath,'gfx/recordlock_warning3.gif','width="17" height="12"').' title="'.htmlspecialchars($lockInfo['msg']).'" alt="" /></a>';
	}

	/**
	 * Gets the JavaScript code needed to handle an XMLHTTP request in the frontend.
	 * All JS functions have to call ajax_doRequest(url) to make a request to the server.
 	 * USE:
	 * See examples of using this function in template.php -> getContextMenuCode and alt_clickmenu.php -> printContent
	 *
	 * @param	string		JS function handling the XML data from the server. That function gets the returned XML data as parameter.
	 * @param	string		JS fallback function which is called with the URL of the request in case ajax is not available.
	 * @param	boolean		If set to 1, the returned XML data is outputted as text in an alert window - useful for debugging, PHP errors are shown there, ...
	 * @return	string		JavaScript code needed to make and handle an XMLHTTP request
	 */
	function getJScode($handlerFunction, $fallback='', $debug=0)	{
			// Init the XMLHTTP request object
		$code = '
		function ajax_initObject()	{
			var A;
			try	{
				A=new ActiveXObject("Msxml2.XMLHTTP");
			} catch (e)	{
				try	{
					A=new ActiveXObject("Microsoft.XMLHTTP");
				} catch (oc)	{
					A=null;
				}
			}
			if(!A && typeof XMLHttpRequest != "undefined")	{
				A = new XMLHttpRequest();
			}
			return A;
		}';
			// in case AJAX is not available, fallback function
		if($fallback)	{
			$fallback .= '(url)';
		} else {
			$fallback = 'return';
		}
		$code .= '
		function ajax_doRequest(url)	{
			var x;

			x = ajax_initObject();
			if(!x)	{
				'.$fallback.';
			}
			x.open("GET", url, true);

			x.onreadystatechange = function()	{
				if (x.readyState != 4)	{
					return;
				}
				'.($debug?'alert(x.responseText)':'').'
				var xmldoc = x.responseXML;
				if (xmldoc)	{
					var t3ajax = xmldoc.getElementsByTagName("t3ajax")[0];
				} else	{
					var t3ajax = false;
				}
				if (typeof '.$handlerFunction.'=="function")	{
					'.$handlerFunction.'(t3ajax);
				}
			}
			x.send("");

			delete x;
		}';

		return $code;
	}



	function getLockScript($checkRecordLocking, $backPath = '', $spacer = 0, $noScriptTags = false)	{
		$checkArr = array(); 
		$lockData = array(); 
		$checkArr[] = 'top.checkLockElements = Array();';
		$lockData[] = 'top.lockData = Array();';
		$GLOBALS['T3_VARS']['doExplicitLocking'] = $GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingExplicit'];
		foreach ($checkRecordLocking as $lockRec)	{
			$isLocked = 0;
			$lockInfo = tx_lockadmin_funcs::isRecordLocked($lockRec[0], $lockRec[1]);
			if (is_array($lockInfo) && count($lockInfo))	{
				$isLocked = 1;
			}
			$recStr = $lockRec[0].'-'.$lockRec[1];
			$checkArr[] = 'top.checkLockElements.push(\''.$recStr.'\');';
			$lockData[] = 'top.lockData[\''.$recStr.'\'] = '.$isLocked.';';
			if ($lockRec[0]!='pages')	{
				$isLocked = 0;
				$rec = t3lib_BEfunc::getRecord($lockRec[0], $lockRec[1]);
				if (is_array($lockInfo = tx_lockadmin_funcs::isRecordLocked('pages',$rec['pid'])) && count($lockInfo))	{
					$isLocked = 1;
				}
				$recStr = 'pages-'.$rec['pid'];
				$checkArr[] = 'top.checkLockElements.push(\''.$recStr.'\');';
				$lockData[] = 'top.lockData[\''.$recStr.'\'] = '.$isLocked.';';
			}
		}
		$checkArr = array_unique($checkArr);
		$lockData = array_unique($lockData);
		$checkStr = implode(chr(10), $checkArr);
		$lockStr = implode(chr(10), $lockData);
		if ($GLOBALS['SOBE']->doc->backPath && !$backPath)	{
			$backPath = $GLOBALS['SOBE']->doc->backPath;
		}
		$sc1 = '<script type="text/javascript">';
		$sc2 = '</script>';
		if ($noScriptTags)	{
			$sc1 = '';
			$sc2 = '';
		}
		$GLOBALS['T3_VARS']['doExplicitLocking'] = false;
		return chr(10).$sc1.'
'.$checkStr.'
'.$lockStr.'
top.lockBackPath = "'.$backPath.'";
top.lockSpacer = '.intval($spacer).';
'.$sc2.chr(10);
	}


	function updateXCLASS($template, $current, $classname, $base_classname)	{
		$content = t3lib_div::getURL($template);
		$content = str_replace('###CLASS###', $classname, $content);
		$content = str_replace('###BASE_CLASS###', $base_classname, $content);
		t3lib_div::writeFile($current, $content);
	}

	function lockfileName($num, $what = false)	{
		$path = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lockadmin']['lockfilePath'];
		if (!$path)	{
			$path = PATH_site.'typo3temp/lockadmin/';
		}
		if ($what=='path') return $path;
		$ext = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lockadmin']['lockfileExt'];
		if (!$ext)	{
			$ext = 'pid';
		}
		if ($what=='ext') return $ext;
		return $path.$num.'.'.$ext;
	}


	function getLockFiles()	{
		$path = tx_lockadmin_funcs::lockfileName(0, 'path');
		$ext = tx_lockadmin_funcs::lockfileName(0, 'ext');
		return t3lib_div::getFilesInDir($path, $ext, 1);
	}

	function lock()	{
		if (!$GLOBALS['TYPO3_CONF_VARS']['EXT']['lockadmin']['lockFD'])	{
			$GLOBALS['TYPO3_CONF_VARS']['EXT']['lockadmin']['lockFD'] = fopen(PATH_site.'typo3temp/lockadmin.lock', 'ab');
		}
		// Eventually use shared locks to reduce dead-times
		tx_lockadmin_funcs::debug('LOCK: Try to aquire lock');
		$before = time();
		flock($GLOBALS['TYPO3_CONF_VARS']['EXT']['lockadmin']['lockFD'], LOCK_EX);
		$after = time();
		tx_lockadmin_funcs::debug('LOCK: Got lock');
		$diff = $after-$before;
		if ($diff>2) {
			tx_lockadmin_funcs::debug('STALLED LOCK: '.t3lib_div::debug_trail());
		}
	}

	function unlock()	{
		if ($GLOBALS['TYPO3_CONF_VARS']['EXT']['lockadmin']['lockFD'])	{
			tx_lockadmin_funcs::debug('UNLOCK: Remove lock');
			flock($GLOBALS['TYPO3_CONF_VARS']['EXT']['lockadmin']['lockFD'], LOCK_UN);
			tx_lockadmin_funcs::debug('UNLOCK: Lock removed');
		}
	}

	function debug($obj)	{
		$user = $GLOBALS['BE_USER']->user['username'];
		/*
		if ($user=='kraftb')	{
			return;
		}
		*/
		$fd = fopen('/tmp/debug.log', 'ab');
		$time = strftime('%Y-%m-%d %H:%M:%S', time());
		if (is_string($obj)) {
			fwrite($fd, $time.': '.$obj."\n");
		} else {
			fwrite($fd, $time.': '.print_r($obj, 1)."\n");
		}
		fclose($fd);
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__]);
}

?>
