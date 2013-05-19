<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "fluidpages".
 *
 * Auto generated 19-05-2013 14:27
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Fluid Pages Engine',
	'description' => 'Fluid Page Template engine - integrates compact and highly dynamic page templates with all the benefits of Fluid.',
	'category' => 'misc',
	'author' => 'Claus Due',
	'author_email' => 'claus@wildside.dk',
	'author_company' => 'Wildside A/S',
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
	'version' => '2.0.0',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.5-0.0.0',
			'cms' => '',
			'flux' => '5.0.0',
		),
		'conflicts' => array(
			'templavoila' => '',
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
	'_md5_values_when_last_written' => 'a:23:{s:16:"ext_autoload.php";s:4:"fd74";s:21:"ext_conf_template.txt";s:4:"8026";s:12:"ext_icon.gif";s:4:"68b4";s:17:"ext_localconf.php";s:4:"3e86";s:14:"ext_tables.php";s:4:"0a3c";s:14:"ext_tables.sql";s:4:"f145";s:9:"README.md";s:4:"5857";s:33:"Classes/Backend/BackendLayout.php";s:4:"ea53";s:38:"Classes/Backend/PageLayoutSelector.php";s:4:"f4de";s:45:"Classes/Controller/AbstractPageController.php";s:4:"2707";s:37:"Classes/Controller/PageController.php";s:4:"a697";s:46:"Classes/Controller/PageControllerInterface.php";s:4:"7388";s:51:"Classes/Override/Backend/View/BackendLayoutView.php";s:4:"b451";s:48:"Classes/Override/Backend/View/PageLayoutView.php";s:4:"7463";s:46:"Classes/Provider/PageConfigurationProvider.php";s:4:"e751";s:40:"Classes/Service/ConfigurationService.php";s:4:"799f";s:31:"Classes/Service/PageService.php";s:4:"cd37";s:38:"Configuration/TypoScript/constants.txt";s:4:"ebd1";s:34:"Configuration/TypoScript/setup.txt";s:4:"d2b8";s:40:"Resources/Private/Language/locallang.xml";s:4:"f0ec";s:52:"Resources/Private/Partials/Exception/1364685651.html";s:4:"fb0f";s:43:"Resources/Private/Templates/Page/Error.html";s:4:"bfd3";s:44:"Resources/Private/Templates/Page/Render.html";s:4:"d41d";}',
);

?>