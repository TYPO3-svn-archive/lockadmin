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

$script = basename($_SERVER['PHP_SELF']);
if ($script!='db_layout.php') return '';

require_once(PATH_lockadmin.'interfaces/interface.SC_db_layout_getButtons.php');

class ###CLASS### extends ###BASE_CLASS###	{

	function getButtons($function = '')	{
		$buttons = parent::getButtons($function);

		/**
		 * @hook	getButtons: Allows to change the buttons shown in the Web>Page module
		 * @date	2008-03-21
		 * @request	Bernhard Kraft  <krafbt@kraftb.at>
		 * @usage	On top of the Web>Page module buttons are shown - those can get modified using the hook below
		 */

		$this->doc->inDocStylesArray['hideBody'] = '
body	{
	display: none;
}
';

		if(is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['getButtons']))	{
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['getButtons'] as $classData)	{
				$hookObject = &t3lib_div::getUserObj($classData);
				if(!($hookObject instanceof SC_db_layout_getButtons))	{
					throw new UnexpectedValueException('$hookObject must implement interface SC_db_layout_getButtons', 1206105247);
				}
				$buttons = $hookObject->getButtons($buttons, $this);
			}
		}

		return $buttons;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__]);
}

?>
