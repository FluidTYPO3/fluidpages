<?php
namespace FluidTYPO3\Fluidpages\Override\Backend\View;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Include file extending db_list.inc for use with the web_layout module
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */

use FluidTYPO3\Fluidpages\Backend\BackendLayout;

/**
 * Child class for the Web > Page module
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class PageLayoutView extends \TYPO3\CMS\Backend\View\PageLayoutView {

	/**
	 * @var \FluidTYPO3\Fluidpages\Backend\BackendLayout
	 */
	protected $backendLayout;

	/**
	 * @param \FluidTYPO3\Fluidpages\Backend\BackendLayout $backendLayout
	 * @return void
	 */
	public function injectBackendLayout(BackendLayout $backendLayout) {
		$this->backendLayout = $backendLayout;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		/** @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		/** @var \FluidTYPO3\Fluidpages\Backend\BackendLayout $backendLayout */
		$backendLayout = $objectManager->get('FluidTYPO3\\Fluidpages\\Backend\\BackendLayout');
		$this->injectBackendLayout($backendLayout);
	}

	/**
	 * Get backend layout configuration
	 *
	 * @return array
	 */
	public function getBackendLayoutConfiguration() {
		$backendLayoutUid = $this->getSelectedBackendLayoutUid($this->id);
		if (!$backendLayoutUid) {
			$config = array();
			$this->backendLayout->postProcessBackendLayout($this->id, $config);
			$typoScriptArray = $config['__config'];
			$typoScriptArray['colCount'] = $config['__config']['backend_layout.']['colCount'];
			$typoScriptArray['rowCount'] = $config['__config']['backend_layout.']['rowCount'];
			$typoScriptArray['rows.'] = $config['__config']['backend_layout.']['rows.'];
			unset($typoScriptArray['backend_layout.']);
			$config['config'] = $this->compactTypoScriptArray(array('backend_layout.' => $typoScriptArray));
			return $config;
		}
		return \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('backend_layout', intval($backendLayoutUid));
	}

	/**
	 * @param array $array
	 * @param integer $indent
	 * @return string
	 */
	protected function compactTypoScriptArray($array, $indent = 0) {
		$indentation = str_repeat("\t", $indent);
		$string = '';
		foreach ($array as $index => $value) {
			if (is_array($value)) {
				$string .= ($indentation . substr($index, 0, -1) . ' { ' . LF);
				$string .= $this->compactTypoScriptArray($value, $indent + 1);
				$string .= ($indentation . '}' . LF);
			} else {
				$string .= ($indentation . $index . ' = ' . $value . LF);
			}
		}
		return $string;
	}

	/**
	 * @param string $inner
	 * @param string $uri
	 * @return string
	 */
	protected function wrapLink($inner, $uri) {
		return '<a href="' . $uri . '">' . $inner . '</a>' . LF;
	}

	/**
	 * @param integer $pid
	 * @param integer $colPos
	 * @param integer $relativeUid
	 * @param bool $reference
	 * @return string
	 */
	protected function getPasteIcon($pid, $colPos, $relativeUid = 0, $reference = FALSE) {
		$clipData = $GLOBALS['BE_USER']->getModuleData('clipboard', $GLOBALS['BE_USER']->getTSConfigVal('options.saveClipboard') ? '' : 'ses');
		$mode = TRUE === isset($clipData['current']) ? $clipData['current'] : 'normal';
		$hasClip = TRUE === isset($clipData[$mode]['el']) && 0 < count($clipData[$mode]['el']);
		if (FALSE === $hasClip) {
			return NULL;
		}
		if (FALSE === isset($clipData[$mode]['mode']) && TRUE === $reference) {
			return NULL;
		}
		$uid = 0;
		$clipBoard = new \TYPO3\CMS\Backend\Clipboard\Clipboard();
		if (TRUE === $reference) {
			$command = 'reference';
			$label = 'Paste as reference in this position';
			$icon = 'actions-insert-reference';
		} else {
			$command = 'paste';
			$label = 'Paste in this position';
			$icon = 'actions-document-paste-after';
		}
		$relativeTo = $pid . '-' . $command . '-' . $relativeUid . '-' . $uid;
		$relativeTo .= '--' . $colPos;
		$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon($icon, array('title' => $label, 'class' => 't3-icon-actions t3-icon-document-new'));
		$uri = "javascript:top.content.list_frame.location.href=top.TS.PATH_typo3+'";
		$uri .= $clipBoard->pasteUrl('tt_content', $relativeTo);
		$uri .= "';";
		return $this->wrapLink($icon, $uri);
	}

	/**
	 * Renders Content Elements from the tt_content table from page id
	 *
	 * @param integer $id Page id
	 * @return string HTML for the listing
	 * @todo Define visibility
	 */
	public function getTable_tt_content($id) {
		$this->initializeLanguages();
		// Initialize:
		$RTE = $GLOBALS['BE_USER']->isRTE();
		$lMarg = 1;
		$showHidden = $this->tt_contentConfig['showHidden'] ? '' : \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tt_content');
		$pageTitleParamForAltDoc = '&recTitle=' . rawurlencode(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle('pages', \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('pages', $id), TRUE));
		$GLOBALS['SOBE']->doc->getPageRenderer()->loadExtJs();
		$GLOBALS['SOBE']->doc->getPageRenderer()->addJsFile('/' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('fluidpages') . 'Resources/Public/js/typo3pageModule.js');
		// Get labels for CTypes and tt_content element fields in general:
		$this->CType_labels = array();
		foreach ($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] as $val) {
			$this->CType_labels[$val[1]] = $GLOBALS['LANG']->sL($val[0]);
		}
		$this->itemLabels = array();
		foreach ($GLOBALS['TCA']['tt_content']['columns'] as $name => $val) {
			$this->itemLabels[$name] = $GLOBALS['LANG']->sL($val['label']);
		}
		// Select display mode:
		// MULTIPLE column display mode, side by side:
		if (!$this->tt_contentConfig['single']) {
			// Setting language list:
			$langList = $this->tt_contentConfig['sys_language_uid'];
			if ($this->tt_contentConfig['languageMode']) {
				if ($this->tt_contentConfig['languageColsPointer']) {
					$langList = '0,' . $this->tt_contentConfig['languageColsPointer'];
				} else {
					$langList = implode(',', array_keys($this->tt_contentConfig['languageCols']));
				}
				$languageColumn = array();
			}
			$langListArr = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $langList);
			$defLanguageCount = array();
			$defLangBinding = array();
			// For each languages... :
			// If not languageMode, then we'll only be through this once.
			foreach ($langListArr as $lP) {
				$showLanguage = ' AND sys_language_uid IN (' . intval($lP) . ',-1)';
				$cList = explode(',', $this->tt_contentConfig['cols']);
				$content = array();
				$head = array();

				// Select content records per column
				$contentRecordsPerColumn = $this->getContentRecordsPerColumn('table', $id, array_values($cList), $showHidden . $showLanguage);
				// For each column, render the content into a variable:
				foreach ($cList as $key) {
					if (!$lP) {
						$defLanguageCount[$key] = array();
					}
					// Start wrapping div
					$content[$key] .= '<div class="t3-page-ce-wrapper">';
					// Add new content at the top most position
					$pasteIcon = $this->getPasteIcon($id, $key);
					$pasteReferenceIcon = $this->getPasteIcon($id, $key, 0, TRUE);
					$content[$key] .= '
					<div class="t3-page-ce" id="' . uniqid() . '">
						<div class="t3-page-ce-dropzone" id="colpos-' . $key . '-' . 'page-' . $id . '-' . uniqid() . '">
							<div class="t3-page-ce-wrapper-new-ce">
								<a href="#" onclick="' . htmlspecialchars($this->newContentElementOnClick($id, $key, $lP)) . '" title="' . $GLOBALS['LANG']->getLL('newRecordHere', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-new') . '</a>
								' . $pasteIcon . $pasteReferenceIcon . '
							</div>
						</div>
					</div>
					';
					$editUidList = '';
					$rowArr = $contentRecordsPerColumn[$key];
					foreach ((array) $rowArr as $rKey => $row) {
						if ($this->tt_contentConfig['languageMode']) {
							$languageColumn[$key][$lP] = $head[$key] . $content[$key];
							if (!$this->defLangBinding) {
								$languageColumn[$key][$lP] .= '<br /><br />' . $this->newLanguageButton($this->getNonTranslatedTTcontentUids($defLanguageCount[$key], $id, $lP), $lP);
							}
						}
						if (is_array($row) && (int) $row['t3ver_state'] != 2) {
							$singleElementHTML = '';
							if (!$lP && $row['sys_language_uid'] != -1) {
								$defLanguageCount[$key][] = $row['uid'];
							}
							$editUidList .= $row['uid'] . ',';
							$disableMoveAndNewButtons = $this->defLangBinding && $lP > 0;
							if (!$this->tt_contentConfig['languageMode']) {
								$singleElementHTML .= '<div class="t3-page-ce-dragitem" id="' . uniqid() . '">';
							}
							$singleElementHTML .= $this->tt_content_drawHeader($row, $this->tt_contentConfig['showInfo'] ? 15 : 5, $disableMoveAndNewButtons, TRUE,
								!$this->tt_contentConfig['languageMode']);
							$isRTE = $RTE && $this->isRTEforField('tt_content', $row, 'bodytext');
							$innerContent = '<div ' . ($row['_ORIG_uid'] ? ' class="ver-element"' : '') . '>' . $this->tt_content_drawItem($row, $isRTE) . '</div>';
							$singleElementHTML .= '<div class="t3-page-ce-body-inner">' . $innerContent . '</div>' . $this->tt_content_drawFooter($row);
							// NOTE: this is the end tag for <div class="t3-page-ce-body">
							// because of bad (historic) conception, starting tag has to be placed inside tt_content_drawHeader()
							$singleElementHTML .= '</div>';
							$statusHidden = $this->isDisabled('tt_content', $row) ? ' t3-page-ce-hidden' : '';
							$singleElementHTML = '<div class="t3-page-ce' . $statusHidden . '" id="element-tt_content-' . $row['uid'] . '">' . $singleElementHTML . '</div>';
							$singleElementHTML .= '<div class="t3-page-ce-dropzone" id="colpos-' . $key . '-' . 'page-' . $id .
								'-' . uniqid() . '">';
							// Add icon "new content element below"
							if (!$disableMoveAndNewButtons) {
								// New content element:
								if ($this->option_newWizard) {
									$onClick = 'window.location.href=\'db_new_content_el.php?id=' . $row['pid'] . '&sys_language_uid=' . $row['sys_language_uid'] . '&colPos=' . $row['colPos'] . '&uid_pid=' . -$row['uid'] . '&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . '\';';
								} else {
									$params = '&edit[tt_content][' . -$row['uid'] . ']=new';
									$onClick = \TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick($params, $this->backPath);
								}
								$pasteIcon = $this->getPasteIcon($id, $key, $row['uid']);
								$pasteReferenceIcon = $this->getPasteIcon($id, $key, $row['uid'], TRUE);
								$singleElementHTML .= '
									<div class="t3-page-ce-wrapper-new-ce">
										<a href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . $GLOBALS['LANG']->getLL('newRecordHere', 1) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-new') . '</a>
										' . $pasteIcon . $pasteReferenceIcon . '
									</div>
								';
							}
							if (!$this->tt_contentConfig['languageMode']) {
								$singleElementHTML .= '
								</div>';
							}
							$singleElementHTML .= '
							</div>';
							if ($this->defLangBinding && $this->tt_contentConfig['languageMode']) {
								$defLangBinding[$key][$lP][$row[$lP ? 'l18n_parent' : 'uid']] = $singleElementHTML;
							} else {
								$content[$key] .= $singleElementHTML;
							}
						} else {
							unset($rowArr[$rKey]);
						}
					}
					$content[$key] .= '</div>';
					// Add new-icon link, header:
					$newP = $this->newContentElementOnClick($id, $key, $lP);
					$colTitle = \TYPO3\CMS\Backend\Utility\BackendUtility::getProcessedValue('tt_content', 'colPos', $key);
					$tcaItems = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction('TYPO3\\CMS\\Backend\\View\\BackendLayoutView->getColPosListItemsParsed', $id, $this);
					foreach ($tcaItems as $item) {
						if ($item[1] == $key) {
							$colTitle = $GLOBALS['LANG']->sL($item[0]);
						}
					}
					$head[$key] .= $this->tt_content_drawColHeader($colTitle, $this->doEdit && count($rowArr) ? '&edit[tt_content][' . $editUidList . ']=edit' . $pageTitleParamForAltDoc : '', $newP);
					$editUidList = '';
				}
				// For each column, fit the rendered content into a table cell:
				$out = '';
				if ($this->tt_contentConfig['languageMode']) {
					// in language mode process the content elements, but only fill $languageColumn. output will be generated later
					foreach ($cList as $k => $key) {
						$languageColumn[$key][$lP] = $head[$key] . $content[$key];
						if (!$this->defLangBinding) {
							$languageColumn[$key][$lP] .= '<br /><br />' . $this->newLanguageButton($this->getNonTranslatedTTcontentUids($defLanguageCount[$key], $id, $lP), $lP);
						}
					}
				} else {
					$backendLayoutRecord = $this->getBackendLayoutConfiguration();
					// GRID VIEW:
					// Initialize TS parser to parse config to array
					$parser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
					$parser->parse($backendLayoutRecord['config']);
					$grid .= '<div class="t3-gridContainer"><table border="0" cellspacing="0" cellpadding="0" width="100%" height="100%" class="t3-page-columns t3-gridTable">';
					// Add colgroups
					$colCount = intval($parser->setup['backend_layout.']['colCount']);
					$rowCount = intval($parser->setup['backend_layout.']['rowCount']);
					$grid .= '<colgroup>';
					for ($i = 0; $i < $colCount; $i++) {
						$grid .= '<col style="width:' . 100 / $colCount . '%"></col>';
					}
					$grid .= '</colgroup>';
					// Cycle through rows
					for ($row = 1; $row <= $rowCount; $row++) {
						$rowConfig = $parser->setup['backend_layout.']['rows.'][$row . '.'];
						if (!isset($rowConfig)) {
							continue;
						}
						$grid .= '<tr>';
						for ($col = 1; $col <= $colCount; $col++) {
							$columnConfig = $rowConfig['columns.'][$col . '.'];
							if (!isset($columnConfig)) {
								continue;
							}
							// Which tt_content colPos should be displayed inside this cell
							$columnKey = intval($columnConfig['colPos']);
							// Render the grid cell
							$colSpan = intval($columnConfig['colspan']);
							$rowSpan = intval($columnConfig['rowspan']);
							$grid .= '<td valign="top"' . ($colSpan > 0 ? ' colspan="' . $colSpan . '"' : '') . ($rowSpan > 0 ? ' rowspan="' . $rowSpan . '"' : '') . ' class="t3-gridCell t3-page-column t3-page-column-' . $columnKey . (!isset($columnConfig['colPos']) ? ' t3-gridCell-unassigned' : '') . (isset($columnConfig['colPos']) && !$head[$columnKey] ? ' t3-gridCell-restricted' : '') . ($colSpan > 0 ? ' t3-gridCell-width' . $colSpan : '') . ($rowSpan > 0 ? ' t3-gridCell-height' . $rowSpan : '') . '">';
							// Draw the pre-generated header with edit and new buttons if a colPos is assigned.
							// If not, a new header without any buttons will be generated.
							if (isset($columnConfig['colPos']) && $head[$columnKey]) {
								$grid .= $head[$columnKey] . $content[$columnKey];
							} elseif ($columnConfig['colPos']) {
								$grid .= $this->tt_content_drawColHeader($GLOBALS['LANG']->getLL('noAccess'), '', '');
							} else {
								$grid .= $this->tt_content_drawColHeader($GLOBALS['LANG']->getLL('notAssigned'), '', '');
							}
							$grid .= '</td>';
						}
						$grid .= '</tr>';
					}
					$out .= $grid . '</table></div>';
				}
				// CSH:
				$out .= \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem($this->descrTable, 'columns_multi', $GLOBALS['BACK_PATH']);
			}
			// If language mode, then make another presentation:
			// Notice that THIS presentation will override the value of $out! But it needs the code above to execute since $languageColumn is filled with content we need!
			if ($this->tt_contentConfig['languageMode']) {
				// Get language selector:
				$languageSelector = $this->languageSelector($id);
				// Reset out - we will make new content here:
				$out = '';
				// Traverse languages found on the page and build up the table displaying them side by side:
				$cCont = array();
				$sCont = array();
				foreach ($langListArr as $lP) {
					// Header:
					$cCont[$lP] = '
						<td valign="top" class="t3-page-lang-column">
							<h3>' . htmlspecialchars($this->tt_contentConfig['languageCols'][$lP]) . '</h3>
						</td>';

					// "View page" icon is added:
					$viewLink = '<a href="#" onclick="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::viewOnClick($this->id, $this->backPath, \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($this->id), '', '', ('&L=' . $lP))) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-view') . '</a>';
					// Language overlay page header:
					if ($lP) {
						list($lpRecord) = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField('pages_language_overlay', 'pid', $id, 'AND sys_language_uid=' . intval($lP));
						\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL('pages_language_overlay', $lpRecord);
						$params = '&edit[pages_language_overlay][' . $lpRecord['uid'] . ']=edit&overrideVals[pages_language_overlay][sys_language_uid]=' . $lP;
						$lPLabel = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('pages_language_overlay', $lpRecord), 'pages_language_overlay', $lpRecord['uid']) . $viewLink . ($GLOBALS['BE_USER']->check('tables_modify', 'pages_language_overlay') ? '<a href="#" onclick="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick($params, $this->backPath)) . '" title="' . $GLOBALS['LANG']->getLL('edit', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open') . '</a>' : '') . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($lpRecord['title'], 20));
					} else {
						$lPLabel = $viewLink;
					}
					$sCont[$lP] = '
						<td nowrap="nowrap" class="t3-page-lang-column t3-page-lang-label">' . $lPLabel . '</td>';
				}
				// Add headers:
				$out .= '<tr>' . implode($cCont) . '</tr>';
				$out .= '<tr>' . implode($sCont) . '</tr>';
				// Traverse previously built content for the columns:
				foreach ($languageColumn as $cKey => $cCont) {
					$out .= '
					<tr>
						<td valign="top" class="t3-gridCell t3-page-lang-column">' . implode(('</td>' . '
						<td valign="top" class="t3-gridCell t3-page-lang-column">'), $cCont) . '</td>
					</tr>';
					if ($this->defLangBinding) {
						// "defLangBinding" mode
						foreach ($defLanguageCount[$cKey] as $defUid) {
							$cCont = array();
							foreach ($langListArr as $lP) {
								$cCont[] = $defLangBinding[$cKey][$lP][$defUid] . '<br/>' . $this->newLanguageButton($this->getNonTranslatedTTcontentUids(array($defUid), $id, $lP), $lP);
							}
							$out .= '
							<tr>
								<td valign="top" class="t3-page-lang-column">' . implode(('</td>' . '
								<td valign="top" class="t3-page-lang-column">'), $cCont) . '</td>
							</tr>';
						}
						// Create spacer:
						$cCont = array();
						foreach ($langListArr as $lP) {
							$cCont[] = '&nbsp;';
						}
						$out .= '
						<tr>
							<td valign="top" class="t3-page-lang-column">' . implode(('</td>' . '
							<td valign="top" class="t3-page-lang-column">'), $cCont) . '</td>
						</tr>';
					}
				}
				// Finally, wrap it all in a table and add the language selector on top of it:
				$out = $languageSelector . '
					<div class="t3-lang-gridContainer">
						<table cellpadding="0" cellspacing="0" class="t3-page-langMode">
							' . $out . '
						</table>
					</div>';
				// CSH:
				$out .= \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem($this->descrTable, 'language_list', $GLOBALS['BACK_PATH']);
			}
		} else {
			// SINGLE column mode (columns shown beneath each other):
			if ($this->tt_contentConfig['sys_language_uid'] == 0 || !$this->defLangBinding) {
				// Initialize:
				if ($this->defLangBinding && $this->tt_contentConfig['sys_language_uid'] == 0) {
					$showLanguage = ' AND sys_language_uid IN (0,-1)';
					$lP = 0;
				} else {
					$showLanguage = ' AND sys_language_uid=' . $this->tt_contentConfig['sys_language_uid'];
					$lP = $this->tt_contentConfig['sys_language_uid'];
				}
				$cList = explode(',', $this->tt_contentConfig['showSingleCol']);
				$content = array();
				$out = '';
				// Expand the table to some preset dimensions:
				$out .= '
					<tr>
						<td><img src="clear.gif" width="' . $lMarg . '" height="1" alt="" /></td>
						<td valign="top"><img src="clear.gif" width="150" height="1" alt="" /></td>
						<td><img src="clear.gif" width="10" height="1" alt="" /></td>
						<td valign="top"><img src="clear.gif" width="300" height="1" alt="" /></td>
					</tr>';

				// Select content records per column
				$contentRecordsPerColumn = $this->getContentRecordsPerColumn('tt_content', $id, array_values($cList), $showHidden . $showLanguage);
				// Traverse columns to display top-on-top
				foreach ($cList as $counter => $key) {
					$c = 0;
					$rowArr = $contentRecordsPerColumn[$key];
					$numberOfContentElementsInColumn = count($rowArr);
					$rowOut = '';
					// If it turns out that there are not content elements in the column, then display a big button which links directly to the wizard script:
					if ($this->doEdit && $this->option_showBigButtons && !intval($key) && $numberOfContentElementsInColumn == 0) {
						$onClick = 'window.location.href=\'db_new_content_el.php?id=' . $id . '&colPos=' . intval($key) . '&sys_language_uid=' . $lP . '&uid_pid=' . $id . '&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . '\';';
						$theNewButton = $GLOBALS['SOBE']->doc->t3Button($onClick, $GLOBALS['LANG']->getLL('newPageContent'));
						$theNewButton = '<img src="clear.gif" width="1" height="5" alt="" /><br />' . $theNewButton;
					} else {
						$theNewButton = '';
					}
					// Traverse any selected elements:
					foreach ($rowArr as $rKey => $row) {
						if (is_array($row) && (int) $row['t3ver_state'] != 2) {
							$c++;
							$editUidList .= $row['uid'] . ',';
							$isRTE = $RTE && $this->isRTEforField('tt_content', $row, 'bodytext');
							// Create row output:
							$rowOut .= '
								<tr>
									<td></td>
									<td valign="top">' . $this->tt_content_drawHeader($row) . '</td>
									<td>&nbsp;</td>
									<td' . ($row['_ORIG_uid'] ? ' class="ver-element"' : '') . ' valign="top">' . $this->tt_content_drawItem($row, $isRTE) . '</td>
								</tr>';
							// If the element was not the last element, add a divider line:
							if ($c != $numberOfContentElementsInColumn) {
								$rowOut .= '
								<tr>
									<td></td>
									<td colspan="3"><img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, 'gfx/stiblet_medium2.gif', 'width="468" height="1"') . ' class="c-divider" alt="" /></td>
								</tr>';
							}
						} else {
							unset($rowArr[$rKey]);
						}
					}
					// Add spacer between sections in the vertical list
					if ($counter) {
						$out .= '
							<tr>
								<td></td>
								<td colspan="3"><br /><br /><br /><br /></td>
							</tr>';
					}
					// Add section header:
					$newP = $this->newContentElementOnClick($id, $key, $this->tt_contentConfig['sys_language_uid']);
					$out .= '

						<!-- Column header: -->
						<tr>
							<td></td>
							<td valign="top" colspan="3">' . $this->tt_content_drawColHeader(\TYPO3\CMS\Backend\Utility\BackendUtility::getProcessedValue('tt_content', 'colPos', $key), ($this->doEdit && count($rowArr) ? '&edit[tt_content][' . $editUidList . ']=edit' . $pageTitleParamForAltDoc : ''), $newP) . $theNewButton . '<br /></td>
						</tr>';
					// Finally, add the content from the records in this column:
					$out .= $rowOut;
				}
				// Finally, wrap all table rows in one, big table:
				$out = '
					<table border="0" cellpadding="0" cellspacing="0" width="400" class="typo3-page-columnsMode">
						' . $out . '
					</table>';
				// CSH:
				$out .= \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem($this->descrTable, 'columns_single', $GLOBALS['BACK_PATH']);
			} else {
				$out = '<br/><br/>' . $GLOBALS['SOBE']->doc->icons(1) . 'Sorry, you cannot view a single language in this localization mode (Default Language Binding is enabled)<br/><br/>';
			}
		}
		// Add the big buttons to page:
		if ($this->option_showBigButtons) {
			$bArray = array();
			if (!$GLOBALS['SOBE']->current_sys_language) {
				if ($this->ext_CALC_PERMS & 2) {
					$bArray[0] = $GLOBALS['SOBE']->doc->t3Button(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick('&edit[pages][' . $id . ']=edit', $this->backPath, ''), $GLOBALS['LANG']->getLL('editPageProperties'));
				}
			} else {
				if ($this->doEdit && $GLOBALS['BE_USER']->check('tables_modify', 'pages_language_overlay')) {
					list($languageOverlayRecord) = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField('pages_language_overlay', 'pid', $id, 'AND sys_language_uid=' . intval($GLOBALS['SOBE']->current_sys_language));
					$bArray[0] = $GLOBALS['SOBE']->doc->t3Button(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick('&edit[pages_language_overlay][' . $languageOverlayRecord['uid'] . ']=edit', $this->backPath, ''), $GLOBALS['LANG']->getLL('editPageProperties_curLang'));
				}
			}
			if ($this->ext_CALC_PERMS & 4 || $this->ext_CALC_PERMS & 2) {
				$bArray[1] = $GLOBALS['SOBE']->doc->t3Button('window.location.href=\'' . $this->backPath . 'move_el.php?table=pages&uid=' . $id . '&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . '\';', $GLOBALS['LANG']->getLL('move_page'));
			}
			if ($this->ext_CALC_PERMS & 8) {
				$bArray[2] = $GLOBALS['SOBE']->doc->t3Button('window.location.href=\'' . $this->backPath . 'db_new.php?id=' . $id . '&pagesOnly=1&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . '\';', $GLOBALS['LANG']->getLL('newPage2'));
			}
			if ($this->doEdit && $this->ext_function == 1) {
				$bArray[3] = $GLOBALS['SOBE']->doc->t3Button('window.location.href=\'db_new_content_el.php?id=' . $id . '&sys_language_uid=' . $GLOBALS['SOBE']->current_sys_language . '&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . '\';', $GLOBALS['LANG']->getLL('newPageContent2'));
			}
			$out = '
				<table border="0" cellpadding="4" cellspacing="0" class="typo3-page-buttons">
					<tr>
						<td>' . implode('</td>
						<td>', $bArray) . '</td>
						<td>' . \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem($this->descrTable, 'button_panel', $GLOBALS['BACK_PATH']) . '</td>
					</tr>
				</table>
				<br />
				' . $out;
		}
		// Return content:
		return $out;
	}

}
