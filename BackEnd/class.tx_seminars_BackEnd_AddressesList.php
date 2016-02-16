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
 * Class 'addresses list' for the 'seminars' extension.
 *
 * @package TYPO3
 * @subpackage tx_seminars
 *
 */
class tx_seminars_BackEnd_AddressesList extends tx_seminars_BackEnd_List {

	/**
	 * @var string the path to the template file of this list
	 */
	protected $templateFile = 'EXT:seminars/Resources/Private/Templates/BackEnd/AddressesList.html';
	protected $listLimit = 50;


	/**
	 * Generates and prints out a addresses list.
	 *
	 * @return string the HTML source code to display
	 */
	public function show() {
	 global $BE_USER, $TCA;
		$content = '';

		$pageData = $this->page->getPageData();
		$filters = t3lib_div::_GP('filters');
		$add_where = '';
		if (is_array($filters)) { 
			if ($filters['name']<>'')
				$add_where .= ' AND u.name LIKE \'%'.$filters['name'].'%\' ';
				//$add_where .= ' AND CONCAT(u.firest_name, \' \', u.last_name) LIKE \'%'.$filters['name'].'%\' ';
			if ($filters['email']<>'')
				$add_where .= ' AND u.email LIKE \'%'.$filters['email'].'%\' ';
//				$add_where .= ' AND u.email = \''.$filters['email'].'\' ';
		};
		// change news category
		if (t3lib_div::_GP('action')=='changeNewsCategory')
		{
			$add_uid = t3lib_div::_GP('addUid');
			$cat_uids = t3lib_div::_GP('catUid');
			if ($add_uid>0)
				$del1 = $GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_dmail_ttaddress_category_mm', 'uid_local='.$add_uid);
			$sort = 1;
			if (is_array($cat_uids)) 
				foreach ($cat_uids as $k=>$cat_uid) {
					if (($add_uid>0) and ($cat_uid>0))
						$insert1 = $GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_dmail_ttaddress_category_mm', array('uid_local'=>$add_uid, 'uid_foreign'=>$cat_uid, 'sorting'=>$sort++));
				}
		};
		// delete news category
		if (t3lib_div::_GP('action')=='delete_news_subscribe')
		{
			$add_uid = t3lib_div::_GP('addUid');
			if ($add_uid>0)
				$upd1 = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_address', 'uid='.$add_uid, array('deleted'=>'1'));
		};		
		// user info
		$userUid = intval(t3lib_div::_GP('userUid'));				
		if ($userUid>0) {
			$this->fillMarkersUserInfo($userUid);
			$content .= $this->template->getSubpart('ADDRESS_INFO');
			return $content;
		};
		
		$seminar_pids = array();
		$user_groups_ = $BE_USER->user['usergroup'];
		$user_groups = explode(',', $user_groups_);
		if (count($user_groups) > 0)
			foreach($user_groups as $gid)
			{
				$res1 = array();
				$res1 = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'be_groups', 'uid='.$gid);
				// only current page
				if ($pageData['uid']==$res1['tx_seminars_events_folder']) 
					$seminar_pids[] = $res1['tx_seminars_events_folder'];
			}	
		//print_r($seminar_pids);
		$seminar_pids_ = implode(',', $seminar_pids);
//		echo $seminar_pids_;
		
		// fe_users by attendances
		$arr_attendances = array();
		$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('a.user, u.email as email', 'tx_seminars_attendances a LEFT JOIN tx_seminars_seminars s ON a.seminar=s.uid LEFT JOIN fe_users u ON a.user=u.uid ', 's.pid IN ('.$seminar_pids_.') AND s.deleted=0 AND a.deleted=0 AND u.deleted=0 '.$add_where);

		foreach ($res2 as $k => $v)
			if (!in_array($v['user'], $arr_attendances)) {
				$arr_attendances[] = $v['user'];
				$arr_used_emails[] = $v['email'];
			};
//		print_r($arr_attendances);echo '<br>';

		// fe_users by rented rooms
		$arr_booking = array();
		$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('s.owner_feuser, u.email as email', 'tx_seminars_seminars s LEFT JOIN fe_users u ON s.owner_feuser=u.uid ', 's.pid IN ('.$seminar_pids_.') AND s.deleted=0 AND u.deleted=0 '.$add_where);
		foreach ($res2 as $k => $v)
			if (!in_array($v['owner_feuser'], $arr_booking)) {
				$arr_booking[] = $v['owner_feuser'];
				$arr_used_emails[] = $v['email'];
			};
//		print_r($arr_booking);echo '<br>';
		
		// get typoscript configuration
		require_once(PATH_t3lib . 'class.t3lib_page.php');
		require_once(PATH_t3lib . 'class.t3lib_tstemplate.php');
		require_once(PATH_t3lib . 'class.t3lib_tsparser_ext.php');
		list($page) = t3lib_BEfunc::getRecordsByField('pages', 'pid', 0);
		$pageUid = intval($page['uid']);
		$sysPageObj = t3lib_div::makeInstance('t3lib_pageSelect');
		$rootLine = $sysPageObj->getRootLine($pageUid);
		$TSObj = t3lib_div::makeInstance('t3lib_tsparser_ext');
		$TSObj->tt_track = 0;
		$TSObj->init();
		$TSObj->runThroughTemplates($rootLine);
		$TSObj->generateConfig();
		$conf = $TSObj->setup['plugin.']['tx_seminars_pi1.'];
//		print_r($conf);
		
		// fe_users by news subscribe
		$arr_news = array();		
		if (is_array($conf['newsletter_relations.'])) {
			foreach ($seminar_pids as $spid)
				$news_pids[] = $conf['newsletter_relations.'][$spid];		
			$news_pids_ = implode(',', $news_pids);
				//$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('u.uid', 'tt_address u ', 'u.pid IN ('.$news_pids_.') AND u.deleted=0 '.$add_where);
				$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('u.uid, u.email as email', 'tt_address u ', 'u.pid IN ('.$conf['newsletter_relations.'][$pageData['uid']].') AND u.deleted=0 '.$add_where);
				foreach ($res2 as $k => $v)
					if ((!in_array($v['uid'], $arr_news)) && (!in_array($v['email'], $arr_used_emails)))
							$arr_news[] = $v['uid'];
//			print_r($arr_news);echo '<br>';
		}
		/**/
		$all_users = array_merge($arr_attendances, $arr_booking);
		$all_users = array_unique($all_users);
//		print_r($all_users);		
		$this->template->setMarker('form_action', 'index.php?id=' . $pageData['uid'] . '&amp;subModule=5');
		if (is_array($filters)) {
			$this->template->setMarker('value_name', $filters['name']);
			$this->template->setMarker('value_email', $filters['email']);
		}
		else {
			$this->template->setMarker('value_name', '');
			$this->template->setMarker('value_email', '');
		};
		$this->template->setMarker('label_user_full_name', $GLOBALS['LANG']->getLL('registrationlist.feuser.name'));
		$this->template->setMarker('label_user_email', 'Email');
		$this->template->setMarker('label_count_attendances', 'Attendances count');
		$this->template->setMarker('label_count_booking', 'Booking count');
		$this->template->setMarker('label_count_news', 'News subscription count');					

		$i = 0;			
			// fe_users
		if (count($all_users)>0) {		
			$user_uids = implode(',', $all_users);
			$res_arr=array();
			$users = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'fe_users', 'uid IN ('.$user_uids.') ', 'name');			
			foreach ($users as $user) {
				$res_arr[$i]['name'] = $user['name'];
				if ($user['email']=='')
					$res_arr[$i]['email'] = '- empty -';
				else
					$res_arr[$i]['email'] = $user['email'];
				$res_arr[$i]['uid'] = $user['uid'];					
				$i++;
			};
		};

			//tt_address
		if (count($arr_news)>0) {
				// empty row for template 
				$res_arr[$i]['email'] = ' -----   newsletter subscribers   -----';
				$res_arr[$i]['tt_address'] = '';
				$res_arr[$i]['uid'] = '-1';
				$i++;
			$user_subscriber_uids = implode(',', $arr_news);
			$users = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tt_address', 'uid IN ('.$user_subscriber_uids.')');			
			foreach ($users as $user) {
				$name = $user['first_name'].' '.$user['last_tname'];
				$name = trim($name);
				if ($name=='') $name = $user['name'];
				$res_arr[$i]['name'] = $name;
				if ($user['email']=='')
					$res_arr[$i]['email'] = '- empty -';
				else
					$res_arr[$i]['email'] = $user['email'];
				$res_arr[$i]['tt_address'] = 1;
				$res_arr[$i]['uid'] = $user['uid'];
				$i++;
			};			
		};
		$areAddressesVisible = $this->setAddressesTableMarkers($res_arr);		
//			$areAddressesSubscribers = $this->setAddressesSubscribersTableMarkers($user_subscriber_uids);
		$AddressesTable .= $this->template->getSubpart('ADDRESSES_TABLE');
			
	

		$this->template->setMarker('complete_table', $AddressesTable);
		$page_browser = $this->getPageBrowser(count($res_arr), $this->listLimit);
		$this->template->setMarker('page_browser', $page_browser);

		$content .= $this->template->getSubpart('SEMINARS_ADDRESSES_LIST');
		$content .= $this->configCheckWarnings;

		return $content;
	}


	private function setAddressesTableMarkers(&$res_arr) {
		$pageData = $this->page->getPageData();

//		$users = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'fe_users', 'uid IN ('.$user_uids.') ', 'name');
//		var_dump($res_arr);
		$minus_count = 0;
		if (t3lib_div::_GP('start'))
			$start = t3lib_div::_GP('start');
		else $start = 0;
		$end = $start + $this->listLimit;
		for($i=$start;$i<$end;$i++) {
			if ($res_arr[$i]['tt_address']==1) {
				$minus_count = 1;
				$name_link = '<a href="index.php?id=' . $pageData['uid'] . '&amp;subModule=5&amp;userUid=' . $res_arr[$i]['uid'] . '&amp;tt_address=1">' . $res_arr[$i]['name']. '</a>';				
				$email_link = '<a href="index.php?id=' . $pageData['uid'] . '&amp;subModule=5&amp;userUid=' . $res_arr[$i]['uid'] . '&amp;tt_address=1">' . $res_arr[$i]['email'] . '</a>';				
			}
			else {
				$name_link = '<a href="index.php?id=' . $pageData['uid'] . '&amp;subModule=5&amp;userUid=' . $res_arr[$i]['uid'] . '">' . $res_arr[$i]['name']. '</a>';				
				$email_link = '<a href="index.php?id=' . $pageData['uid'] . '&amp;subModule=5&amp;userUid=' . $res_arr[$i]['uid'] . '">' . $res_arr[$i]['email'] . '</a>';				
			};
			$this->template->setMarker('user_full_name', $name_link);
			$this->template->setMarker('user_email', $email_link);			
			$this->template->setMarker('count_attendances', $this->getCountAttendances($res_arr[$i]['uid'], $res_arr[$i]['tt_address']));			
			$this->template->setMarker('count_booking', $this->getCountBooking($res_arr[$i]['uid'], $res_arr[$i]['tt_address']));			
			$this->template->setMarker('count_news', $this->getCountNews($res_arr[$i]['uid'], $res_arr[$i]['tt_address'], $res_arr[$i]['email']));	
			
			$tableRows .= $this->template->getSubpart('ADDRESSES_TABLE_ROW');
		};

		$this->template->setMarker(
			'label_addresses', 'Addresses'
		);
		$this->template->setMarker(
			'number_of_addresses', count($res_arr) - $minus_count
		);
		$this->template->setMarker(
			'table_header',
			$this->template->getSubpart('ADDRESSES_TABLE_HEADING')
		);
		$this->template->setMarker('table_rows', $tableRows);
/**/
		return $result;
	}
	

	private function getCountAttendances($user_uid, $tt_address) 
	{
		if ($user_uid=='-1') return '';
		if ($tt_address==1)
			//$users = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('count(*) as c', 'tt_address a LEFT JOIN fe_users u ON a.email = u.email LEFT JOIN tx_seminars_attendances att ON att.user=u.uid LEFT JOIN tx_seminars_seminars s ON att.seminar=s.uid', 'a.uid='.$user_uid.' AND u.email<>\'NULL\' AND u.email<>\'\' AND u.deleted=0 AND s.uid<>\'NULL\'');		
			$users = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('DISTINCT count(*) as c', 'tt_address a, fe_users u, tx_seminars_attendances att, tx_seminars_seminars s', 'a.email = u.email AND u.uid = att.user AND att.seminar = s.uid AND a.uid ='.$user_uid.' AND a.email<>\'\'');		
		else
			$users = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('count(*) as c', 'tx_seminars_attendances', 'user = '.$user_uid.' AND deleted=0');		
		return $users['c'];
	}	
	private function getCountBooking($user_uid, $tt_address) 
	{
		if ($user_uid=='-1') return '';
		if ($tt_address==1)
			$users = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('count(*) as c', 'tt_address a, fe_users u, tx_seminars_seminars s', ' a.uid = '.$user_uid.' AND a.email = u.email AND a.email <> \'\' AND u.uid = s.owner_feuser AND s.deleted=0');
			//$users = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('count(*) as c', 'tt_address a LEFT JOIN fe_users u ON a.email = u.email LEFT JOIN tx_seminars_seminars s ON s.owner_feuser=u.uid', 'a.uid='.$user_uid.' AND u.email<>\'NULL\' AND u.email<>\'\' AND u.deleted=0');	
		else
			$users = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('count(*) as c', 'tx_seminars_seminars', 'owner_feuser = '.$user_uid.' AND deleted=0');		
		return $users['c'];
	}	
	private function getCountNews($user_uid, $tt_address, $email) 
	{
		if ($user_uid=='-1') return '';
		if (($tt_address==1) && ($email<>''))	
			$users = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('count(*) as c', 'tt_address a', 'a.uid =\''.$user_uid.'\' AND a.deleted=0');		
		else 
			$users = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('count(*) as c', 'tt_address a LEFT JOIN fe_users u ON a.email=u.email', 'u.uid = '.$user_uid.' AND a.deleted=0 AND u.deleted=0');		
		return $users['c'];
	}		
	
	private function fillMarkersUserInfo($userUid) 
	{
		$tt_address = intval(t3lib_div::_GP('tt_address'));	
		if ($tt_address==1)
			$user = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'tt_address', 'uid = '.$userUid);		
		else 
			$user = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'fe_users', 'uid = '.$userUid);		
		$this->template->setMarker('user_name', $user['name']);
		$this->template->setMarker('user_email', $user['email']);
		
		$this->createTableHeading();
		
		$this->template->setMarker('users_events_table_header', $this->template->getSubpart('USERS_EVENTS_TABLE_HEADING'));

		$attendances = $this->getAttendancesForUser($userUid);
		$this->template->setMarker('users_attendances', $attendances);
		
		$manageEvents = $this->getManageEventsForUser($userUid);		
		$this->template->setMarker('users_manager', $manageEvents);

		
		$userEmail = $user['email'];
		$this->template->setMarker('users_news_table_header', $this->template->getSubpart('USERS_NEWS_TABLE_HEADING'));		
		$userNews = $this->getNewsForUser($userEmail);		
		$this->template->setMarker('users_news', $userNews);
		
		return;
	}
	
	private function getAttendancesForUser($userUid)
	{
		$tt_address = intval(t3lib_div::_GP('tt_address'));			
		$this->template->setMarker('users_events_table_rows', '');			
		if ($tt_address==1) 
			//$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tt_address a LEFT JOIN fe_users u ON a.email = u.email LEFT JOIN tx_seminars_attendances att ON att.user=u.uid LEFT JOIN tx_seminars_seminars s ON att.seminar=s.uid', 'a.uid='.$userUid.' AND u.email<>\'NULL\' AND u.email<>\'\' AND u.deleted=0 AND s.uid<>\'NULL\'');	
			$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('DISTINCT s.uid as suid', 'tt_address a, fe_users u, tx_seminars_attendances att, tx_seminars_seminars s', 'a.email = u.email AND u.uid = att.user AND att.seminar = s.uid AND a.uid ='.$userUid.' AND a.email<>\'\'');		
		else 
			$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('seminar as suid, uid as attuid', 'tx_seminars_attendances', 'user = '.$userUid.' AND deleted=0');
		foreach ($res2 as $k => $v) {
			$rows .= $this->getEventRow($v['suid'], $v['attuid'], 0);
		}	
		$this->template->setMarker('users_events_table_rows', $rows);			
		$content = $this->template->getSubpart('USERS_EVENTS_TABLE');
		return $content;
	}
	
	private function getManageEventsForUser($userUid)
	{
		$this->template->setMarker('users_events_table_rows', '');			
		$tt_address = intval(t3lib_div::_GP('tt_address'));
		if ($tt_address==1) 		
			$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('s.uid as suid', 'tt_address a, fe_users u, tx_seminars_seminars s', ' a.uid = '.$userUid.' AND a.email = u.email AND u.uid = s.owner_feuser AND s.deleted=0');
		else 
			$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid as suid', 'tx_seminars_seminars s', 'owner_feuser = '.$userUid.' AND deleted=0');
		foreach ($res2 as $k => $v) {
			$rows .= $this->getEventRow($v['suid'], $userUid, 1);
		}	
		$this->template->setMarker('users_events_table_rows', $rows);			
		$content = $this->template->getSubpart('USERS_EVENTS_TABLE');
		return $content;
	}	

	private function getNewsForUser($userEmail)
	{
//		$this->template->setMarker('users_news_table_rows', '');			
		$category_title = '';
		//$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('DISTINCT a.pid as pid, a.uid as auid, dmc.category as category_title, dmc.deleted as cat_deleted', 'tt_address a LEFT JOIN sys_dmail_category dmc ON dmc.uid=a.module_sys_dmail_category', 'a.email = \''.$userEmail.'\' AND a.deleted=0');
		$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('DISTINCT a.pid as pid, a.uid as auid, dmc.category as category_title, dmc.deleted as cat_deleted', 'tt_address a LEFT JOIN sys_dmail_category dmc ON dmc.uid=a.module_sys_dmail_category', 'a.email = \''.$userEmail.'\' AND a.deleted=0');		
		foreach ($res2 as $k => $v) {
			$res3 = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('dmc.category as category_title, dmc.deleted as cat_deleted', 'sys_dmail_ttaddress_category_mm mm LEFT JOIN sys_dmail_category dmc ON dmc.uid=mm.uid_foreign', 'mm.uid_local = '.$v['auid'].'');		
			foreach ($res3 as $l => $v2) {
				$category_title .= ', '.$v2['category_title'];
				if ($v2['cat_deleted']==1)
					$category_title .= ' (deleted)';
			};
			$category_title = substr($category_title, 2);
			$rows .= $this->getNewsRow($v['pid'], $v['auid'], $category_title);
		};
		$this->template->setMarker('users_news_table_rows', $rows);			
		$content = $this->template->getSubpart('USERS_NEWS_TABLE');
		return $content;
	}

	private function getEventRow($eventUid, $userUid, $manager=0)
	{	
		$pageData = $this->page->getPageData();
		$event = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'tx_seminars_seminars', 'uid = '.$eventUid.' AND deleted=0');		
			$this->template->setMarker('uid', $event['uid']);
//			$this->template->setMarker('icon', $event->getRecordIcon());
			$this->template->setMarker('title',$event['title']);
			if ($event['begin_date'])
				$date_begin = date('d.m.Y', $event['begin_date']);
			else 
				$date_begin = '';
			if ($event['end_date'])
				$date_end = '-'.date('d.m.Y', $event['end_date']);
			else 
				$date_end = '';
			$this->template->setMarker('date', $date_begin . $date_end);
			if ($manager==1)
				$this->template->setMarker('link_edit',$this->getEditEventIcon($event['uid'], 'tx_seminars_seminars', $pageData['uid']));
			else 
				$this->template->setMarker('link_edit',$this->getEditEventIcon($userUid, 'tx_seminars_attendances', $pageData['uid']));
			$this->template->setMarker('invoice_button',$this->getInvoiceButton($event['uid'], $userUid, $manager));						
			//$this->template->setMarker('hide_unhide_button',$this->getHideUnhideIcon($event->getUid(), $event->getPageUid(), $event->isHidden()));
			//$this->template->setMarker('up_down_buttons',$this->getUpDownIcons($useManualSorting, $sortList, $event->getUid()));
			//$this->template->setMarker('csv_registration_export_button',(($event->needsRegistration() && !$event->isHidden())? $this->getRegistrationsCsvIcon($event) : ''));
			//$this->template->setMarker('number_of_attendees',($event->needsRegistration() ? $event->getAttendances() : ''));
			//$this->template->setMarker('show_registrations',((!$event->isHidden()&& $event->needsRegistration()&& $event->hasAttendances())? $this->createEventRegistrationsLink($event) : ''));
			//$this->template->setMarker('number_of_attendees_on_queue',($event->hasRegistrationQueue()? $event->getAttendancesOnRegistrationQueue() : ''));
			//$this->template->setMarker('minimum_number_of_attendees',($event->needsRegistration() ? $event->getAttendancesMin() : ''));
			//$this->template->setMarker('maximum_number_of_attendees',($event->needsRegistration() ? $event->getAttendancesMax() : ''));
			//$this->template->setMarker('has_enough_attendees',($event->needsRegistration()? (!$event->hasEnoughAttendances() ? $GLOBALS['LANG']->getLL('no') : $GLOBALS['LANG']->getLL('yes')): ''));
			//$this->template->setMarker('is_fully_booked',($event->needsRegistration()? (!$event->isFull()? $GLOBALS['LANG']->getLL('no') : $GLOBALS['LANG']->getLL('yes')): ''));
			//$this->template->setMarker('status', $this->getStatusIcon($event));

			$tableRow .= $this->template->getSubpart('USERS_EVENTS_TABLE_ROW');	
			return $tableRow;
	}
	
	private function getNewsRow($pageID, $add_uid, $category_title='')
	{
		global $BACK_PATH, $LANG;	
		$page = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'pages', 'uid = '.$pageID.' AND deleted=0');		
			$this->template->setMarker('uid', $page['uid']);
//			$this->template->setMarker('icon', $page->getRecordIcon());
			$this->template->setMarker('title', $page['title']);
			$this->template->setMarker('category', $category_title);
			$this->template->setMarker('change_category_form', $this->getChangeNewsCategoryForm($add_uid));			
			$this->template->setMarker('link_delete', $this->getDeleteNewsForm($add_uid));			
//			$this->template->setMarker('date', date('d.m.Y', $page['begin_date']).'-'.date('d.m.Y', $page['end_date']));

			$tableRow .= $this->template->getSubpart('USERS_NEWS_TABLE_ROW');	
			return $tableRow;
	}	
	
	private function createTableHeading() {
		$this->template->setMarker(
			'label_accreditation_number',
			$GLOBALS['LANG']->getLL('eventlist.accreditation_number')
		);
		$this->template->setMarker(
			'label_title', $GLOBALS['LANG']->getLL('eventlist.title')
		);
		$this->template->setMarker(
			'label_date', $GLOBALS['LANG']->getLL('eventlist.date')
		);
		$this->template->setMarker(
			'label_edit', ''
		);
		$this->template->setMarker(
			'label_invoice', ''
		);		
		$this->template->setMarker(
			'label_category', 'Category'
		);	
		$this->template->setMarker(
			'label_change_category', ''//;'Change category to:'
		);	
		$this->template->setMarker(
			'label_attendees', $GLOBALS['LANG']->getLL('eventlist.attendees')
		);
		$this->template->setMarker(
			'label_number_of_attendees_on_queue',
			$GLOBALS['LANG']->getLL('eventlist.attendeesOnRegistrationQueue')
		);
		$this->template->setMarker(
			'label_minimum_number_of_attendees',
			$GLOBALS['LANG']->getLL('eventlist.attendees_min')
		);
		$this->template->setMarker(
			'label_maximum_number_of_attendees',
			$GLOBALS['LANG']->getLL('eventlist.attendees_max')
		);
		$this->template->setMarker(
			'label_has_enough_attendees',
			$GLOBALS['LANG']->getLL('eventlist.enough_attendees')
		);
		$this->template->setMarker(
			'label_is_fully_booked', $GLOBALS['LANG']->getLL('eventlist.is_full')
		);
		$this->template->setMarker(
			'label_status', $GLOBALS['LANG']->getLL('eventlist_status')
		);
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

	/**
	 * Returns the parameters to add to the CSV icon link.
	 *
	 * @return string the additional link parameters for the CSV icon link, will
	 *                always start with an &amp and be htmlspecialchared, may
	 *                be empty
	 */
	protected function getAdditionalCsvParameters() {
		if ($this->eventUid > 0) {
			$result = '&amp;tx_seminars_pi2[eventUid]=' . $this->eventUid;
		} else {
			$result = parent::getAdditionalCsvParameters();
		}

		return $result;
	}
	
	protected function getPageBrowser($count, $listLimit) {
		$pageData = $this->page->getPageData();
		$pageCount = ceil( $count / $listLimit);
		$start = t3lib_div::_GP('start');
		$filters = t3lib_div::_GP('filters');
		for( $i = 0; $i < $pageCount; $i++ ) {   
			//$content .= '<a href="index.php?category=' . $critery . '&start=' . ($i * $limit)  . '">' . ($i + 1)  . '</a></li>';
			$href = 'index.php?id=' . $pageData['uid'] . '&amp;subModule=5&amp;start='.($i * $listLimit);
			if ($filters['name'] <> '') 
				$href .= '&amp;filters[name]='.$filters['name'];
			if ($filters['email'] <> '') 
				$href .= '&amp;filters[email]='.$filters['email'];				
			$content .= '<a href="'.$href.'">';
			if ($start == ($i * $listLimit))
				$content .= '<b>'.($i + 1).'</b>';
			else 
				$content .= ($i + 1);
			$content .= '</a>&nbsp; ';				
		}
		return $content;
	}
	
	public function getEditEventIcon($uid, $table, $pageUid) {
		global $BACK_PATH, $LANG, $BE_USER;

		$result = '';
		if ($BE_USER->check('tables_modify', $table)
			&& $this->doesUserHaveAccess($pageUid)
		) {
			$params = '&edit['.$table.']['.$uid.']=edit';
			$editOnClick = $this->editNewUrl($params, $BACK_PATH);
			$langEdit = $LANG->getLL('edit');
			$result = '<a href="'.htmlspecialchars($editOnClick).'">'
				.'<img '
				.t3lib_iconWorks::skinImg(
					$BACK_PATH,
					'gfx/edit2.gif',
					'width="11" height="12"')
				.' title="'.$langEdit.'" alt="'.$langEdit.'" class="icon" />'
				.'</a>';
		}

		return $result;
	}
	
	
	public function getInvoiceButton($event_uid, $user_uid='', $manager=0) {
		global $BACK_PATH, $LANG, $BE_USER;	
		$result = '';
		if ($manager==1)
			$result .= '<form target="_blank" method="post" action="/index.php?eID=seminars_invoice_manager"><button class="create_pdf_participants">Invoice</button>';
		else
			$result .= '<form target="_blank" method="post" action="/index.php?eID=seminars_invoice"><button class="create_pdf_participants">Invoice</button>';
		$result .= '<input type="hidden" value="'.$event_uid.'" name="eventUid">';
		if ($manager==0)
			$result .= '<input type="hidden" value="'.$user_uid.'" name="userUid">';
		$result .= '<input type="hidden" value="invoiceEvent" name="action">';
		$result .= '</form>';	
		return $result;
	}
	
	public function getChangeNewsCategoryForm($add_uid) {
		global $BACK_PATH, $LANG, $BE_USER;	
		$result = '';
		$link_form = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$result .= '<form method="post" action="'.$link_form.'">';
		$result .= '<select name="catUid[]" multiple="multiple" size="3">';
		$categories = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_dmail_category', "hidden=0 and deleted=0"); 
		foreach ($categories as $k => $v) {
			$result .= '<option value="'.$v['uid'].'">'.$v['category'].'</option>';
		};		
		$result .= '</select><br>';		
		$result .= '<input type="hidden" value="'.$add_uid.'" name="addUid">';
		$result .= '<input type="hidden" value="changeNewsCategory" name="action">';
		$result .= '<input type="submit" name="setNewsCategory" value="Set category">';
		$result .= '</form>';	
		return $result;
	}	
	
	public function getDeleteNewsForm($add_uid) {
		global $BACK_PATH, $LANG, $BE_USER;	
		$result = '';
		
// delete icon
		$confirmation = htmlspecialchars(
			'if (confirm('
			.$LANG->JScharCode( $LANG->getLL('deleteWarning').$referenceWarning)
			.')) {document.formdelete'.$add_uid.'.submit();} else {return false;}');			
		//onclick="'.$confirmation.'";
		$link_form = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$result .= '<form name="formdelete'.$add_uid.'" method="post" action="'.$link_form.'">';
		$result .= '<a href="javascript: void(0)" onclick="'.$confirmation.'"><img'.t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/garbage.gif', 'width="11" height="12"').' title="Delete" alt="Delete" class="deleteicon" /></a>';		
		$result .= '<input type="hidden" value="'.$add_uid.'" name="addUid">';
		$result .= '<input type="hidden" value="delete_news_subscribe" name="action">';
		$result .= '</form>';	
		return $result;
	}		
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/seminars/BackEnd/class.tx_seminars_BackEnd_RegistrationsList.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/seminars/BackEnd/class.tx_seminars_BackEnd_RegistrationsList.php']);
}
?>