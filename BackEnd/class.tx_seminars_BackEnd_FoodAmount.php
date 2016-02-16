<?php
/***************************************************************
* Copyright notice
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
 * Class 'food amount' for the 'seminars' extension.
 *
 * @package TYPO3
 * @subpackage tx_seminars
 *
 */
class tx_seminars_BackEnd_FoodAmount extends tx_seminars_BackEnd_List {

	/**
	 * @var string the path to the template file of this list
	 */
	protected $templateFile = 'EXT:seminars/Resources/Private/Templates/BackEnd/AddressesList.html';
	protected $listLimit = 50;


	/**
	 * Generates and prints form with foods. Also Saving records.
	 *
	 * @return string the HTML source code to display
	 */
	public function show() {
	 global $BE_USER, $TCA;
		$content = '';
		
		$pid = t3lib_div::_GP('id');
		$seminarid = t3lib_div::_GP('seminar');
		
		if (!$seminarid) {
			echo 'No seminar selected!';
			return false;
		}
		
		// Save foods
		if (t3lib_div::_GP('save') || t3lib_div::_GP('saveclose')) {
			$checkedfoods = t3lib_div::_GP('foodcheck');
			$foodamounts = t3lib_div::_GP('foodamount');
			$sorting = 1;
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_seminars_seminars_foods_mm', 'uid_local='.$seminarid);
			if (isset($checkedfoods) && count($checkedfoods)>0) {
				foreach($checkedfoods as $id=>$v) {
					if ($foodamounts[$id]>0) {
						$setparams = array();
						$setparams['uid_local'] = $seminarid;
						$setparams['uid_foreign'] = $id;
						$setparams['food_amount'] = $foodamounts[$id];
						$setparams['sorting'] = $sorting;
						$ins = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_seminars_seminars_foods_mm', $setparams);
						$sorting++;
					}
				};
			};
		};
		
		// redirect
		if (t3lib_div::_GP('cancel') || t3lib_div::_GP('saveclose')) {
			header('Location: /typo3conf/ext/seminars/BackEnd/index.php?id='.$pid.'&subModule=1');
			die();
		};
		
		// List of foods and Form
		$allfoods = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows ('*', 'tx_seminars_foods f', 'deleted=0');
		
		$seminarfoodsrecords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows ('*', 'tx_seminars_seminars_foods_mm', 'uid_local='.$seminarid);
		foreach ($seminarfoodsrecords as $rec) {
			$seminarfoods[$rec['uid_foreign']] = $rec['food_amount'];
		}
		
		$content .= '<h3>Available foods</h3>';
		$content .= '<form method="post" action="/typo3conf/ext/seminars/BackEnd/index.php?subModule=6">';
		$content .= '<input type="hidden" name="seminar" value="'.$seminarid.'">';
		$content .= '<input type="hidden" name="id" value="'.$pid.'">';
		$content .= '<table class="typo3-dblist">';
		$content .= '<tr class="t3-row-header"><td>Select for this seminar</td><td>Title of food</td><td>Amount of food</td></tr>';
		foreach ($allfoods as $food) {
			$content .= '<tr>';
			$content .= '<td><input type="checkbox" name="foodcheck['.$food['uid'].']" '.(array_key_exists($food['uid'], $seminarfoods)? 'checked="checked"':'').'></td>';
			$content .= '<td>'.$food['title'].'</td>';
			$content .= '<td><input type="text" name="foodamount['.$food['uid'].']" value="'.$seminarfoods[$food['uid']].'"></td>';
			$content .= '</tr>';
		};
		$content .= '<tr><td style="text-align: right;" colspan="3"">';
		$content .= '<input type="submit" name="save" value="Save">&nbsp;';
		$content .= '<input type="submit" name="saveclose" value="Save and close">&nbsp;';
		$content .= '<input type="submit" name="cancel" value="Cancel">';
		$content .= '</td></tr>';
		$content .= '</table>';
		$content .= '</form>';
			
		return $content;
	}
	
	/**
	 * Returns the storage folder for new registration records.
	 *
	 * This will be determined by the registration folder storage setting of the
	 * currently logged-in BE-user.
	 *
	 * @return integer the PID for new registration records, will be >= 0
	 */
	protected function getNewRecordPid() {
		return $this->getLoggedInUser()->getRegistrationFolderFromGroup();
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/seminars/BackEnd/class.tx_seminars_BackEnd_RegistrationsList.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/seminars/BackEnd/class.tx_seminars_BackEnd_RegistrationsList.php']);
}
?>