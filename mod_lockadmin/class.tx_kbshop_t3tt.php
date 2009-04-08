<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Bernhard Kraft (kraftb@kraftb.at)
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Tree template class for TYPO3
 *
 * @author	Bernhard Kraft <kraftb@kraftb.at>
 */

if (TYPO3_MODE=='BE')	{
	require_once(PATH_t3lib.'class.t3lib_parsehtml.php');
}

class tx_kbshop_t3tt	{
	var $config = false;
	var $markerWrap = '';

	function init(&$config)	{
		$this->config = &$config;
			// Hopefully this works all the time 
		if (TYPO3_MODE=='BE')	{
			$this->cObj = t3lib_div::makeInstance('t3lib_parsehtml');
		} else	{
			$this->cObj = &$GLOBALS['TSFE']->cObj;
		}
	}


	function substituteSubpartsAndMarkers_Tree($c, $t, $level = 0)	{
		if (is_array($t))	{
			if (is_array($t['_SUBPARTS'])&&count($t['_SUBPARTS']))	{
				if ($t['_MULTIPLE_SUBPARTS'])	{
					foreach ($t['_SUBPARTS'] as $marker => $sa)	{
						$sc = $this->cObj->getSubpart($c, $marker);
						$acc = '';
						foreach ($sa as $idx => $st)	{
							$acc .= $this->substituteSubpartsAndMarkers_Tree($sc, $st, $level+1);
						}
						$c = $this->cObj->substituteSubpart($c, $marker, $acc);
					}
				} else	{
					foreach ($t['_SUBPARTS'] as $marker => $st)	{
						while (strlen($sc = $this->cObj->getSubpart($c, $marker)))	{
							if (is_array($st))	{
								$c = $this->cObj->substituteSubpart($c, $marker, $this->substituteSubpartsAndMarkers_Tree($sc, $st, $level+1), 0);
							} elseif (is_bool($st))	{
								if ($st)	{
									$sp = $this->cObj->getSubpart($c, $marker);
									$c = $this->cObj->substituteSubpart($c, $marker, $sp, 0);
								} else	{
									$c = $this->cObj->substituteSubpart($c, $marker, '', 0);
								}
							} else	{
								$c = $this->cObj->substituteSubpart($c, $marker, $st, 0);
							}
						}
					}
				}
			}
			if (is_array($t['_MARKERS'])&&count($t['_MARKERS']))	{
				if ($t['_MULTIPLE_MARKERS'])	{
					$acc = '';
					foreach ($t['_MARKERS'] as $key => $ma)	{
						$acc .= $this->cObj->substituteMarkerArray($c, $ma, $this->markerWrap);
					}
					$c = $acc;
				} else	{
					$c = $this->cObj->substituteMarkerArray($c, $t['_MARKERS'], $this->markerWrap);
				}
			}
		}
		if (!$level)	{
			if (preg_match_all('/<\!\-\-\s*(###[^#]+###).*\-\->/sU', $c, $spMatches, PREG_SET_ORDER)>0)	{
				foreach ($spMatches as $match)	{
					$c = $this->cObj->substituteSubpart($c, $match[1], '');
				}
			}
			if (preg_match_all('/###[^#]+###/s', $c, $maMatches,PREG_SET_ORDER)>0)	{
				$ma = array();
				foreach ($maMatches as $match)	{
					$ma[$match[0]] = '';
				}
				$c = $this->cObj->substituteMarkerArray($c, $ma);
			}
		}
		return $c;
	}


}


?>
