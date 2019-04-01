<?php
$EM_CONF[$_EXTKEY] = array (
  'title' => 'Fluid Pages Engine',
  'description' => 'Fluid Page Template engine - integrates compact and highly dynamic page templates with all the benefits of Fluid.',
  'category' => 'misc',
  'author' => 'FluidTYPO3 Team',
  'author_email' => 'claus@namelesscoder.net',
  'author_company' => '',
  'shy' => '',
  'dependencies' => 'flux',
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
  'version' => '5.0.0',
  'CGLcompliance' => '',
  'CGLcompliance_note' => '',
  'constraints' => 
  array (
    'depends' => 
    array (
      'php' => '7.1.0-7.2.99',
      'typo3' => '8.7.0-9.5.99',
      'flux' => '9.0.0-9.99.99',
    ),
    'conflicts' => 
    array (
      'templavoila' => '',
    ),
    'suggests' => 
    array (
    ),
  ),
  'suggests' => 
  array (
  ),
  '_md5_values_when_last_written' => '',
  'autoload' => 
  array (
    'psr-4' => 
    array (
      'FluidTYPO3\\Fluidpages\\' => 'Classes/',
    ),
  ),
  'autoload-dev' => 
  array (
    'psr-4' => 
    array (
      'FluidTYPO3\\Fluidpages\\Tests\\' => 'Tests/',
    ),
  ),
);
