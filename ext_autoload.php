<?php

$extensionClassesPath = t3lib_extMgm::extPath('fluidpages') . 'Classes/';
return array(
	'tx_fluidpages_backend_backendlayout' => $extensionClassesPath . 'Backend/BackendLayout.php',
	'tx_fluidpages_backend_pagelayoutselector' => $extensionClassesPath . 'Backend/PageLayoutSelector.php',
	'tx_fluidpages_controller_pagecontroller' => $extensionClassesPath . 'Controller/PageController.php',
	'tx_fluidpages_override_backend_view_backendlayoutview' => $extensionClassesPath . 'Override/Backend/View/BackendLayoutView.php',
	'tx_fluidpages_override_backend_view_pagelayoutview' => $extensionClassesPath . 'Override/Backend/View/PageLayoutView.php',
	'tx_fluidpages_provider_pageconfigurationprovider' => $extensionClassesPath . 'Provider/PageConfigurationProvider.php',
	'tx_fluidpages_service_configurationservice' => $extensionClassesPath . 'Service/ConfigurationService.php',
	'tx_fluidpages_service_pageservice' => $extensionClassesPath . 'Service/PageService.php',
);
?>