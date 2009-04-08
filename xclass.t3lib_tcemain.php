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

$ux_prefix = 'ux_';
$ux_class = 't3lib_TCEmain';
$base_file = 'class.###UX###t3lib_tcemain.php';
$base_path = PATH_lockadmin;
$template_file = $base_path.'class.template.t3lib_tcemain.php';
$includeXCLASS = 1;
$depth = 0;

include(PATH_lockadmin.'code.tx_lockadmin_xclass.php');

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lockadmin/'.__FILE__]);
}

?>
