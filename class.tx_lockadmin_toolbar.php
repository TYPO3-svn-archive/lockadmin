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


require_once(PATH_typo3.'interfaces/interface.backend_toolbaritem.php');

/**
 * class which adds the long-polling AJAX code to backend.php + additional icon in toolbar
 *
 * @author	Bernhard Kraft  <kraftb@kraftb.at>
 * @package	TYPO3
 * @subpackage	lockadmin
 */
class tx_lockadmin_toolbar implements backend_toolbarItem	{

	/**
	 * sets the reference to the backend object
	 *
	 * @param	TYPO3backend	TYPO3 backend object reference
	 * @return	void
	 */
//	public function setBackend(&$backendReference)	{
	public function __construct(TYPO3backend &$backendReference = NULL)	{
		$this->pObj = &$backendReference;
		$GLOBALS['TBE_TEMPLATE']->inDocStylesArray['lockadmin'] = '

div.body-shade	{
	position: absolute;
	left: 0px;
	top: 0px;
	width: 100%;
	height: 100%;
	z-index: 10;
	overflow: hidden;
	background-image: url(../typo3conf/ext/lockadmin/res/shade.png);
	background-repeat: repeat;
}

div.message-box	{
	position: absolute;
	left: 0px;
	top: 0px;
	width: 400px;
	border: 2px solid #333333;	
	background-color: #dddddd;
	z-index: 20;
	visibility: hidden;
}
div.message-box h1	{
	font-size: 20px;
	font-weight: bold;
}
div.message-box h2	{
	font-size: 13px;
	font-weight: bold;
}
div.message-box h3	{
	font-size: 13px;
	font-weight: bold;
	font-style: italic;
}
div.message-box p	{
	padding: 15px;
}
div.message-box a	{
	display: block;
	background-color: #888888;
	padding: 5px;
	text-align: center;
	font-size: 13px;
	font-weight: bold;
	text-decoration: none;
/*	width: 100%; */
}
div.message-box a:hover	{
	background-color: #aaaaaa;
}
';
	}

	/**
	 * renders the toolbar item
	 *
	 * @return	string	the toolbar item rendered as HTML string
	 */
	public function render()	{
		$ajaxCode = tx_lockadmin_funcs::getJScode('handleLockCheck', '', 0);
		$js_code = t3lib_div::getURL(PATH_lockadmin.'res/lock_update.js');

		if (!is_object($GLOBALS['LANG']))	{
			require_once(t3lib_extMgm::extPath('lang').'lang.php');
			$GLOBALS['LANG'] = t3lib_div::makeInstance('language');
			$GLOBALS['LANG']->init($BE_USER->uc['lang']);
		}
		$msg_lockedRecord = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.lockedRecord'), '###USER###', '###AGE###');
		$msg_lockedContent = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.lockedRecord_content'), '###USER###', '###AGE###');
		$msg_lockedRecord_js = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.lockedRecord'), '###USERJS###', '###AGEJS###');
		$msg_lockedContent_js = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.lockedRecord_content'), '###USERJS###', '###AGEJS###');
		$disableEditOnLock = intval($GLOBALS['TYPO3_CONF_VARS']['BE']['disableEditOnLock'])?1:0;
		$disableEditOnLock_contentLocked = intval($GLOBALS['TYPO3_CONF_VARS']['BE']['disableEditOnLock_contentLocked'])?1:0;
		$disableEditIconsOnLock = intval($GLOBALS['TYPO3_CONF_VARS']['BE']['disableEditIconsOnLock'])?1:0;
		$disableEditIconsOnLock_contentLocked = intval($GLOBALS['TYPO3_CONF_VARS']['BE']['disableEditIconsOnLock_contentLocked'])?1:0;
		$pageContentEditWarning = intval($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lockadmin']['pageContentEditWarning'])?1:0;
		$lockingAcrossLanguages = intval($GLOBALS['TYPO3_CONF_VARS']['BE']['recordLockingAcrossLanguages']);
		$lockedRecord = t3lib_div::quoteJSvalue('<a href="#" onclick="'.htmlspecialchars('alert('.$GLOBALS['LANG']->JScharCode($msg_lockedRecord_js).'.replace(/###USERJS###/, \'###USER###\').replace(/###AGEJS###/, \'###AGE###\'));return false;').'"><img src="'.t3lib_div::getIndpEnv('TYPO3_SITE_URL').'typo3/gfx/recordlock_warning3.gif" width="17" height="12" title="'.htmlspecialchars($msg_lockedRecord).'" alt="" /></a>', true);
		$lockedContent = t3lib_div::quoteJSvalue('<a href="#" onclick="'.htmlspecialchars('alert('.$GLOBALS['LANG']->JScharCode($msg_lockedContent_js).'.replace(/###USERJS###/, \'###USER###\').replace(/###AGEJS###/, \'###AGE###\'));return false;').'"><img src="'.t3lib_div::getIndpEnv('TYPO3_SITE_URL').'typo3/gfx/recordlock_warning3.gif" width="17" height="12" title="'.htmlspecialchars($msg_lockedContent).'" alt="" /></a>', true);

		$js = '
<script type="text/javascript">
var changeVisibility = 1
'.$js_code.'
</script>
<script type="text/javascript">
'.$ajaxCode.'
var checkLockElements = Array();
var lockData = Array();
var pageData = Array();
var lockBackPath = "";
var lockSpacer = 0;
var currentRequest = 0;
var disableEditOnLock = '.$disableEditOnLock.';
var disableEditOnLock_contentLocked = '.$disableEditOnLock_contentLocked.';
var disableEditIconsOnLock = '.$disableEditIconsOnLock.';
var disableEditIconsOnLock_contentLocked = '.$disableEditIconsOnLock_contentLocked.';
var lockingAcrossLanguages = '.$lockingAcrossLanguages.';
var pageContentEditWarning = '.$pageContentEditWarning.';
var msg_lockedRecord = '.$lockedRecord.';
var msg_lockedContent = '.$lockedContent.';


function checkLock()	{
	if (currentRequest) return;
	var checkStr = "";
	var checkEl = "";
	// array "checkLockElements" contains all elements for which locking should get checked.
	// the values of this array are in the form "table-uid".
	// The array "lockData" contains information about all locked or open records. The array
	// keys of this array are in the same form ("table-uid"), and the value is 1 if the record
	// mentioned in the key is locked or 0 if not.
	for (var i in checkLockElements)	{
		if (isNaN(i)) continue;
		checkEl = checkLockElements[i];
		if (checkStr.length)	{
			checkStr += ".";
		}
		checkStr += checkEl;
		checkStr += "-"+lockData[checkEl];
	}
	// pageStatus contains information about all pages being watched
	// i.e. "123-0.456-0.789-1.1111-0" means all pages being not locked except "789"
	// Information gets also set in array "pageData" with "uid-locked" as array key.
	var pageStatus = "";
	for (var i in lockData)	{
		var parts = i.split("-");
		if (parts[0]=="pages")	{
			if (pageStatus.length)	{
				pageStatus += ".";
			}
			pageData["pages-"+parts[1]] = lockData[i];
			pageStatus += parts[1]+"-"+lockData[i];
		}
	}
	var url = "'.t3lib_div::getIndpEnv('TYPO3_SITE_URL').'typo3/ajax.php?ajaxID='.rawurlencode('Locking::checkLock').'&check="+checkStr+"&pageStatus="+pageStatus+"&backPath="+lockBackPath+"&spacer="+lockSpacer;
	currentRequest = 1;
	ajax_doRequest(url);
}

checkLock();
</script>
';

		$icon = '<a id="message-link" href="#" onclick="showStoredMessages();" style="background-color: #aaaaff; padding: 3px 5px 3px 5px; display: none;"><img src="'.t3lib_extMgm::extRelPath('lockadmin').'ext_icon.gif" /></a>';
		return $icon.chr(10).$js;	
	}

	/**
	 * returns additional attributes for the list item in the toolbar
	 *
	 * @return	string		list item HTML attibutes
	 */
	public function getAdditionalAttributes()	{
		return '';
	}


	/**
	 * returns additional attributes for the list item in the toolbar
	 *
	 * @return	string		list item HTML attibutes
	 */
	public function checkAccess()	{
		return true;
	}
	
}

$TYPO3backend->addToolbarItem('lockadmin', 'tx_lockadmin_toolbar');

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__]);
}

?>
