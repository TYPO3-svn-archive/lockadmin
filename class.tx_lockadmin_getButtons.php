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

require_once(PATH_lockadmin.'interfaces/interface.SC_db_layout_getButtons.php');
require_once(PATH_lockadmin.'class.tx_lockadmin_funcs.php');

/**
 * class which hooks into SC_db_layout and modifies buttons on top panel
 *
 * @author	Bernhard Kraft  <kraftb@kraftb.at>
 * @package	TYPO3
 * @subpackage	lockadmin
 */
class tx_lockadmin_getButtons implements SC_db_layout_getButtons	{
	var $checkRecordLocking = array();
	var $recordIsLocked = array();

	
	public function __construct()	{
		$this->checkRecordLocking = array();
	}

	/**
	 * modifies Web>Page buttons in top panel
	 *
	 * @param	array		buttons on the Web>Page top panel
	 * @param	object		Instance of calling object
	 * @return	array		The modified buttons
	 */
	public function getButtons($buttons, &$parentObject)	{
		$GLOBALS['T3_VARS']['doExplicitLocking'] = $GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingExplicit'];
		$this->parentObject = &$parentObject;
		$this->id = $parentObject->id;
		$params = array('pages', $this->id);
		$this->checkRecordLocking[] = $params;
		$isLocked = tx_lockadmin_funcs::isRecordLocked($params[0], $params[1]);
		if ($isLocked)	{
			$this->recordIsLocked[] = $params;
		}

		/* Handle Translated pages --- begin */
		$altLanguagePages = t3lib_BEfunc::getRecordsByField('pages_language_overlay', 'pid', $this->id);
		if (is_array($altLanguagePages) && count($altLanguagePages)) {
			foreach ($altLanguagePages as $altLanguagePage) {
				$params = array('pages_language_overlay', $altLanguagePage['uid']);
				$this->checkRecordLocking[] = $params;
				$this->altLanguagePages[$this->id][$altLanguagePage['sys_language_uid']] = $altLanguagePage['uid'];
				$isLocked = tx_lockadmin_funcs::isRecordLocked('pages_language_overlay', $altLanguagePage['uid']);
				if ($isLocked) {
					$this->recordIsLocked[] = $params;
				}
			}
		}
		/* Handle Translated pages --- end */

		$js = '';
		$js .= 'var recordIsLocked = Array();'.chr(10);
		$js .= 'var altLanguagePages = Array();'.chr(10);
		foreach ($this->recordIsLocked as $param) {
			$js .= 'recordIsLocked["'.$param[0].':'.$param[1].'"] = '.($param[2]?'2':'1').';'.chr(10);
		}
		if (is_array($this->altLanguagePages) && count($this->altLanguagePages)) {
			foreach ($this->altLanguagePages as $pageId => $languagePages) {
				$js .= 'altLanguagePages['.$pageId.'] = Array();'.chr(10);
				foreach ($languagePages as $languageUid => $languagePageUid) {
					$js .= 'altLanguagePages['.$pageId.']['.$languageUid.'] = '.$languagePageUid.';'.chr(10);
				}
			}
		}
		$js .= tx_lockadmin_funcs::getLockScript($this->checkRecordLocking, $parentObject->backPath, 0, true);
		$parentObject->doc->JScodeArray['lock_js'] = $js;
		$postJS = $this->getPageLockScript();
		$parentObject->doc->postCode .= $parentObject->doc->wrapScriptTags($postJS);
		return $buttons;
	}

	public function tt_content_drawHeader($params, &$parentObject)	{
		$script = basename($_SERVER['PHP_SELF']);
		if ($script=='db_list.php') return '';
		if ($script=='alt_db_navframe.php') return '';

		$GLOBALS['SOBE']->doc->inDocStylesArray['lockadmin'] = '
table.typo3-page-ceHeader td.bgColor4 a	{
	display: none;
}
table.typo3-page-ceHeader td.bgColor4 span a	{
	display: inline;
}
table.typo3-page-langMode span.colHeader-lockSpan img {
	vertical-align: middle;
}
';

		$row = t3lib_BEfunc::getRecord($params[0], $params[1]);

		$this->checkRecordLocking[] = $params;
			// Get record locking status:
		$lockIcon = tx_lockadmin_funcs::getLockBoxes($params[0], $params[1], $parentObject->backPath, false);
		$lockIconExplicit = '';
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingExplicit']) {
			$GLOBALS['T3_VARS']['doExplicitLocking'] = $GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingExplicit']; 
			$lockIconExplicit = tx_lockadmin_funcs::getLockBoxes($params[0], $params[1], $parentObject->backPath, false);
			$GLOBALS['T3_VARS']['doExplicitLocking'] = false; 
		}
		if ($lockIcon) {
			if (!$lockIconExplicit) {
				$params[2] = 1;
			}
			$this->recordIsLocked[] = $params;
		}
			// Call stats information hook
		return '<span>'.$parentObject->getIcon($params[0], $row).'</span><span id="lockIcon-'.$params[0].'-'.$params[1].'">'.$lockIcon.'</span>';
	}


	function getPageLockScript()	{
		$js = '

var oldOnLoad = window.onload;


function addIconID(nodes, table, uid, tag, useid)	{
	var node = 0;
	var always = 0;
	if (!tag)	{
		tag = "A";
	} else	{
		always = 1;
	}
	var cont = "";
	if (!useid) {
		var useid = 0;
	}
	for (var i in nodes) {
		node = nodes[i];
		if (node.nodeType != 1) continue;
		if (node.nodeName != tag) continue;
		cont = node.innerHTML;
		if (always) {
			node.id = "disableOnLock-"+table+"-"+uid+"-"+useid;
			if (recordIsLocked[table+":"+uid]==1) {
				node.style.visibility = "hidden";
			}
			useid++;
		} else if (cont.search(/Edit/i)!=-1) {
			node.id = "disableOnLock-"+table+"-"+uid+"-"+useid;
			if (recordIsLocked[table+":"+uid]==1) {
				node.style.visibility = "hidden";
			}
			useid++;
		} else if (cont.search(/Hide/i)!=-1) {
			node.id = "disableOnLock-"+table+"-"+uid+"-"+useid;
			if (recordIsLocked[table+":"+uid]==1) {
				node.style.visibility = "hidden";
			}
			useid++;
		} else if (cont.search(/Delete/i)!=-1) {
			node.id = "disableOnLock-"+table+"-"+uid+"-"+useid;
			if (recordIsLocked[table+":"+uid]==1) {
				node.style.visibility = "hidden";
			}
			useid++;
		}
	}
}
function addIconID_langMode(nodes, table, uid, tag) {
	var node = 0;
	var always = 0;
	if (!tag)	{
		tag = "A";
	} else	{
		always = 1;
	}
	var cont = "";
	var useid = 0;
	for (var i in nodes)	{
		node = nodes[i];
		if (node.nodeType != 1) continue;
		if (node.nodeName != tag) continue;
		cont = node.innerHTML;
		if (cont.search(/Edit/i)!=-1) {
			var onclick = node.getAttribute("onclick");
			var result = onclick.match(/edit\[pages_language_overlay\]\[([0-9]*)\]=edit/);
			var overlayID = 0;
			if (result) {
				overlayID = parseInt(result[1]);
			}
			if (overlayID) {
				node.id = "disableOnLock-pages_language_overlay-"+overlayID+"-"+useid;
				if (recordIsLocked["pages_language_overlay:"+overlayID]) {
					node.style.visibility = "hidden";
				}
				var lockSpan = document.createElement("span");
				lockSpan.id = "lockIcon-pages_language_overlay-"+overlayID;
				lockSpan.className = "colHeader-lockSpan";
				node.parentNode.insertBefore(lockSpan, node.parentNode.lastChild);
				useid++;
			}
		}
	}
}


function hideDefaultLockIcons()	{
	var lockElements = top.checkLockElements;
	var lockStr = "";
	var lockArr = Array();
	var lockIcon = 0;
	var prevEl = 0;
	var nextRow = 0;
	var iconTd = 0;
	for (var i in lockElements) {
		lockStr = lockElements[i];
		if ((typeof lockStr)=="string") {
			lockArr = lockStr.split("-");
			lockIcon = document.getElementById("lockIcon-"+lockArr[0]+"-"+lockArr[1]);
			if (lockIcon) {
				prevEl = lockIcon.previousSibling;
				if (prevEl) {
					if ((prevEl.nodeType==1) && (prevEl.nodeName=="A")) {
						lockStr = prevEl.innerHTML;
						if (lockStr.search(/recordlock_warning3/)!=-1) {
							prevEl.parentNode.removeChild(prevEl);
						}
					}
				}
				if (top.disableEditIconsOnLock || top.disableEditIconsOnLock_contentLocked) {
					nextRow = lockIcon.parentNode.parentNode.nextSibling;
					while (nextRow.nodeType != 1) {
						nextRow = nextRow.nextSibling;
					}
					for (var i in nextRow.childNodes) {
						iconTd = nextRow.childNodes[i];
						if (iconTd.nodeType != 1)	continue;
						if (iconTd.nodeName != "TD")	continue;
						addIconID(iconTd.childNodes, lockArr[0], lockArr[1]);
						break;
					}
				}
			}
		}
	}
	if (top.disableEditIconsOnLock || top.disableEditIconsOnLock_contentLocked) {
		var tables = document.getElementsByTagName("table");
		var table = 0;
		for (var i in tables) {
			table = tables[i];
			if (table.className=="typo3-page-buttons") {
				table.id = "disableOnLock-pages-'.$this->id.'-0";
				if (recordIsLocked["pages:'.$this->id.'"]) {
					table.style.visibility = "hidden";
				}
			}
			if (table.className=="typo3-page-colHeader") {
				var language = 0;
				var tmpHTML = table.innerHTML.replace(/&amp;/g, "&");
				var result = tmpHTML.match(/&sys_language_uid=([0-9]*)&uid_pid=([0-9]*)/);
				if (result) {
					language = parseInt(result[1]);
					langUid = parseInt(result[2]);
				}
				var colHeaderUid = '.$this->id.';
				var colHeaderTable = "pages";
				if (language) {
					colHeaderUid = altLanguagePages[langUid][language];
					colHeaderTable = "pages_language_overlay";
				}
				var diveIn = Array();
				diveIn["TR"] = 1;
				diveIn["TBODY"] = 1;
				var checkIn = Array();
				checkIn["TD"] = 1;
				addIconTraverse(table.childNodes, diveIn, checkIn, "A", colHeaderTable, colHeaderUid, addIconID);
			}
			if (table.className=="typo3-page-langMode") {
				var diveIn = Array();
				diveIn["TR"] = 1;
				diveIn["TBODY"] = 1;
				var checkIn = Array();
				checkIn["TD"] = 1;
				addIconTraverse(table.childNodes, diveIn, checkIn, "A", colHeaderTable, colHeaderUid, addIconID_langMode);
			}
		}
	}
	document.body.style.display = "block";
	if ((typeof oldOnLoad)=="function") {
		return oldOnLoad();
	}
	return true;
}

function addIconTraverse(nodes, diveIn, checkIn, tag, table, uid, subFunc) {
	for (var i in nodes) {
		var node = nodes[i];
		if (node.nodeType != 1) continue;
		if (diveIn[node.nodeName]) {
			addIconTraverse(node.childNodes, diveIn, checkIn, tag, table, uid, subFunc);
		} else if (checkIn[node.nodeName]) {
			subFunc(node.childNodes, table, uid, "", 1);
		}
	}
}

window.onload = hideDefaultLockIcons;
';
		return $js;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__]) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__]);
}

?>
