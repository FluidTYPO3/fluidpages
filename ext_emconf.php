<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "fluidpages".
 *
 * Auto generated 19-09-2014 13:48
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
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 1,
	'lockType' => '',
	'version' => '3.2.0',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.1.0-6.2.99',
			'cms' => '',
			'flux' => '7.1.0-7.1.99',
		),
		'conflicts' => array(
			'templavoila' => '',
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
	'_md5_values_when_last_written' => 'a:41:{s:10:"bower.json";s:4:"44a5";s:20:"class.ext_update.php";s:4:"b621";s:13:"composer.json";s:4:"68f3";s:16:"doktype_icon.png";s:4:"cf0d";s:21:"ext_conf_template.txt";s:4:"b307";s:12:"ext_icon.gif";s:4:"68b4";s:17:"ext_localconf.php";s:4:"ed29";s:14:"ext_tables.php";s:4:"d3a4";s:14:"ext_tables.sql";s:4:"31de";s:10:"LICENSE.md";s:4:"c813";s:9:"README.md";s:4:"2f2e";s:22:"Build/ImportSchema.sql";s:4:"aeff";s:28:"Build/LocalConfiguration.php";s:4:"7617";s:23:"Build/PackageStates.php";s:4:"bced";s:33:"Classes/Backend/BackendLayout.php";s:4:"9c0e";s:45:"Classes/Backend/BackendLayoutDataProvider.php";s:4:"a025";s:38:"Classes/Backend/PageLayoutSelector.php";s:4:"a070";s:46:"Classes/Backend/TemplateFileLayoutSelector.php";s:4:"8665";s:45:"Classes/Controller/AbstractPageController.php";s:4:"e9e1";s:37:"Classes/Controller/PageController.php";s:4:"5290";s:46:"Classes/Controller/PageControllerInterface.php";s:4:"c122";s:51:"Classes/Override/Backend/View/BackendLayoutView.php";s:4:"ee70";s:33:"Classes/Provider/PageProvider.php";s:4:"6d11";s:40:"Classes/Service/ConfigurationService.php";s:4:"449f";s:31:"Classes/Service/PageService.php";s:4:"e8a5";s:38:"Configuration/TypoScript/constants.txt";s:4:"ebd1";s:34:"Configuration/TypoScript/setup.txt";s:4:"5bab";s:33:"Documentation/ComplexityChart.png";s:4:"a410";s:30:"Documentation/PyramidChart.png";s:4:"3a0e";s:33:"Migrations/Code/ClassAliasMap.php";s:4:"af02";s:40:"Resources/Private/Language/locallang.xlf";s:4:"aeca";s:47:"Resources/Private/Partials/Error/Backtrace.html";s:4:"277b";s:46:"Resources/Private/Partials/Error/Extended.html";s:4:"8711";s:44:"Resources/Private/Partials/Error/Header.html";s:4:"a22a";s:46:"Resources/Private/Partials/Error/Standard.html";s:4:"0593";s:43:"Resources/Private/Partials/Error/Style.html";s:4:"83ef";s:52:"Resources/Private/Partials/Exception/1364685651.html";s:4:"fb0f";s:43:"Resources/Private/Templates/Page/Error.html";s:4:"2bc9";s:44:"Resources/Private/Templates/Page/Render.html";s:4:"d41d";s:38:"Resources/Public/js/typo3pageModule.js";s:4:"5ee7";s:17:"Tests/phpunit.xml";s:4:"5a94";}',
);

?>