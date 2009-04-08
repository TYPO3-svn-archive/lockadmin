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

$base_class = $ux_class;
do	{
	$cur_class = $ux_prefix.$ux_class;
	if (!class_exists($ux_prefix.$ux_class))	{
		$cur_file = $base_path.str_replace('###UX###', $ux_prefix, $base_file);
		if ((!file_exists($cur_file)) || (filemtime($cur_file)<filemtime($template_file)))	{
			tx_lockadmin_funcs::updateXCLASS($template_file, $cur_file, $cur_class, $base_class);
		}
		require_once($cur_file);
		$includeXCLASS = 0;
	} else	{
		$ux_prefix .= 'ux_';
	}
	$depth++;
	$base_class = $cur_class;
} while ($includeXCLASS && ($depth < 5));

if ($includeXCLASS)	{
	die(__FILE__.': '.$ux_class.' already XCLASSed '.$depth.' times !');
}


?>
