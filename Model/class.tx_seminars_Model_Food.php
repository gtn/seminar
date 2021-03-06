<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2010 Niels Pardon (mail@niels-pardon.de)
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Class 'tx_seminars_Model_Food' for the 'seminars' extension.
 *
 * This class represents a food.
 *
 * @package TYPO3
 * @subpackage tx_seminars
 *
 * @author Niels Pardon <mail@niels-pardon.de>
 */
class tx_seminars_Model_Food extends tx_oelib_Model {
	/**
	 * Returns our title.
	 *
	 * @return string our title, will not be empty
	 */
	public function getTitle() {		
//			$measure = $GLOBALS['TSFE']->sL('LLL:EXT:seminars/locallang_db.xml:tx_seminars_foods.measure.I.'.$this->getAsString('measure'));
		$seminarObj = t3lib_div::makeInstance('tx_seminars_seminar');
		if ($this->getAsString('measure')<>'')		
			$measure = $seminarObj->translate('measure.I.'.$this->getAsString('measure'));
		else $measure = '';			
//		var_dump($seminarObj);
//print_r($measure);
		if (t3lib_div::_GP('L')==1)	
			$title = $this->getAsString('title_en');
		else
			$title = $this->getAsString('title');
		return $title." ".number_format($this->getAsString('tx_gtnseminarsext_price'),1)."€ (".$measure.') ';
	}

	public function getInput() {
//		$measure = $GLOBALS['TSFE']->sL('LLL:EXT:seminars/locallang.xml:label_title');
		$input = "<input type='text' name='measure".$this->getUid()."'>";
		return $input;
	}


	/**
	 * Sets our title.
	 *
	 * @param string our title to set, must not be empty
	 */
	public function setTitle($title) {
		if ($title == '') {
			throw new InvalidArgumentException('The parameter $title must not be empty.', 1333296826);
		}

		$this->setAsString('title', $title);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/seminars/Model/class.tx_seminars_Model_Food.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/seminars/Model/class.tx_seminars_Model_Food.php']);
}
?>