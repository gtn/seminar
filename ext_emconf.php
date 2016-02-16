<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "seminars".
 *
 * Auto generated 18-02-2014 14:55
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'Seminar Manager',
	'description' => 'This extension allows you to create and manage a list of seminars, workshops, lectures, theater performances and other events, allowing front-end users to sign up. FE users also can create and edit events.',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '0.9.6',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod1,BackEnd',
	'state' => 'beta',
	'uploadfolder' => 1,
	'createDirs' => '',
	'modify_tables' => 'be_groups,fe_groups',
	'clearcacheonload' => 1,
	'lockType' => '',
	'author' => 'Oliver Klee',
	'author_email' => 'typo3-coding@oliverklee.de',
	'author_company' => 'oliverklee.de',
	'CGLcompliance' => NULL,
	'CGLcompliance_note' => NULL,
	'constraints' => 
	array (
		'depends' => 
		array (
			'php' => '5.2.0-0.0.0',
			'typo3' => '4.2.0-6.2.20',
		),
		'conflicts' => 
		array (
		),
		'suggests' => 
		array (
		),
	),
);

?>