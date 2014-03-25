<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "fluidpages".
 *
 * Auto generated 25-03-2014 19:21
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Fluid Pages Engine',
	'description' => 'Fluid Page Template engine - integrates compact and highly dynamic page templates with all the benefits of Fluid.',
	'category' => 'misc',
	'author' => 'FluidTYPO3 Team',
	'author_email' => 'claus@namelesscoder.net',
	'author_company' => '',
	'shy' => '',
	'dependencies' => 'cms,flux',
	'conflicts' => 'templavoila',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 1,
	'lockType' => '',
	'version' => '3.0.0',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.1.0-6.2.99',
			'cms' => '',
			'flux' => '7.0.0-7.0.99',
		),
		'conflicts' => array(
			'templavoila' => '',
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
	'_md5_values_when_last_written' => 'a:37:{s:13:"composer.json";s:4:"c311";s:21:"ext_conf_template.txt";s:4:"0bc6";s:12:"ext_icon.gif";s:4:"68b4";s:17:"ext_localconf.php";s:4:"7291";s:14:"ext_tables.php";s:4:"19cd";s:14:"ext_tables.sql";s:4:"9305";s:9:"README.md";s:4:"2db1";s:22:"Build/ImportSchema.sql";s:4:"31b3";s:28:"Build/LocalConfiguration.php";s:4:"7617";s:23:"Build/PackageStates.php";s:4:"0a36";s:33:"Classes/Backend/BackendLayout.php";s:4:"c192";s:38:"Classes/Backend/PageLayoutSelector.php";s:4:"4345";s:45:"Classes/Controller/AbstractPageController.php";s:4:"1bb9";s:37:"Classes/Controller/PageController.php";s:4:"5290";s:46:"Classes/Controller/PageControllerInterface.php";s:4:"814d";s:51:"Classes/Override/Backend/View/BackendLayoutView.php";s:4:"ee70";s:48:"Classes/Override/Backend/View/PageLayoutView.php";s:4:"6283";s:33:"Classes/Provider/PageProvider.php";s:4:"21f4";s:40:"Classes/Service/ConfigurationService.php";s:4:"29af";s:31:"Classes/Service/PageService.php";s:4:"8fe3";s:47:"Classes/UserFunction/NoSubPageConfiguration.php";s:4:"0f71";s:38:"Configuration/TypoScript/constants.txt";s:4:"ebd1";s:34:"Configuration/TypoScript/setup.txt";s:4:"d458";s:33:"Documentation/ComplexityChart.png";s:4:"a410";s:30:"Documentation/PyramidChart.png";s:4:"3a0e";s:33:"Migrations/Code/ClassAliasMap.php";s:4:"af02";s:40:"Resources/Private/Language/locallang.xml";s:4:"0a7e";s:47:"Resources/Private/Partials/Error/Backtrace.html";s:4:"277b";s:46:"Resources/Private/Partials/Error/Extended.html";s:4:"8711";s:44:"Resources/Private/Partials/Error/Header.html";s:4:"a22a";s:46:"Resources/Private/Partials/Error/Standard.html";s:4:"0593";s:43:"Resources/Private/Partials/Error/Style.html";s:4:"83ef";s:52:"Resources/Private/Partials/Exception/1364685651.html";s:4:"fb0f";s:43:"Resources/Private/Templates/Page/Error.html";s:4:"2bc9";s:44:"Resources/Private/Templates/Page/Render.html";s:4:"d41d";s:38:"Resources/Public/js/typo3pageModule.js";s:4:"5ee7";s:17:"Tests/phpunit.xml";s:4:"5a94";}',
);

?>