<?php
namespace TYPO3\CMS\Opendocs\Backend\ToolbarItems;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;

/**
 * Adding a list of all open documents of a user to the backend.php
 *
 * @author Benjamin Mack <benni@typo3.org>
 * @author Ingo Renner <ingo@typo3.org>
 */
class OpendocsToolbarItem implements ToolbarItemInterface {

	/**
	 * @var \TYPO3\CMS\Backend\Controller\BackendController
	 */
	protected $backendReference;

	/**
	 * @var array
	 */
	protected $openDocs;

	/**
	 * @var array
	 */
	protected $recentDocs;

	/**
	 * @var string
	 */
	protected $EXTKEY = 'opendocs';

	/**
	 * Constructor, loads the documents from the user control
	 *
	 * @param \TYPO3\CMS\Backend\Controller\BackendController TYPO3 backend object reference
	 */
	public function __construct(\TYPO3\CMS\Backend\Controller\BackendController &$backendReference = NULL) {
		$GLOBALS['LANG']->includeLLFile('EXT:opendocs/locallang_opendocs.xlf');
		$this->backendReference = $backendReference;
		$this->loadDocsFromUserSession();
	}

	/**
	 * Checks whether the user has access to this toolbar item
	 *
	 * @return bool TRUE if user has access, FALSE if not
	 */
	public function checkAccess() {
		$conf = $GLOBALS['BE_USER']->getTSConfig('backendToolbarItem.tx_opendocs.disabled');
		return $conf['value'] != 1;
	}

	/**
	 * Loads the opened and recently opened documents from the user
	 *
	 * @return void
	 */
	public function loadDocsFromUserSession() {
		list($this->openDocs, ) = $GLOBALS['BE_USER']->getModuleData('alt_doc.php', 'ses');
		$this->recentDocs = $GLOBALS['BE_USER']->getModuleData('opendocs::recent');
	}

	/**
	 * Renders the toolbar item and the initial menu
	 *
	 * @return string The toolbar item including the initial menu content as HTML
	 */
	public function render() {
		$this->addJavascriptToBackend();
		$this->addCssToBackend();
		$numDocs = count($this->openDocs);
		$opendocsMenu = array();
		$title = $GLOBALS['LANG']->getLL('toolbaritem', TRUE);

		// Toolbar item icon
		$opendocsMenu[] = '<a href="#" class="dropdown-toggle" data-toggle="dropdown">';
		$opendocsMenu[] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('apps-toolbar-menu-opendocs', array('title' => $title));
		$opendocsMenu[] = '<span class="badge" id="tx-opendocs-counter">' . $numDocs . '</span>';
		$opendocsMenu[] = '</a>';

		// Toolbar item menu and initial content
		$opendocsMenu[] = '<ul class="dropdown-menu" role="menu">';
		$opendocsMenu[] = $this->renderMenu();
		$opendocsMenu[] = '</ul>';
		return implode(LF, $opendocsMenu);
	}

	/**
	 * renders the pure contents of the menu
	 *
	 * @return string The menu's content
	 */
	public function renderMenu() {
		$openDocuments = $this->openDocs;
		$recentDocuments = $this->recentDocs;
		$entries = array();
		$content = '';
		if (count($openDocuments)) {
			$entries[] = '<li class="dropdown-header">' . $GLOBALS['LANG']->getLL('open_docs', TRUE) . '</li>';
			$i = 0;
			foreach ($openDocuments as $md5sum => $openDocument) {
				$i++;
				$entries[] = $this->renderMenuEntry($openDocument, $md5sum, FALSE, $i == 1);
			}
		}
		// If there are "recent documents" in the list, add them
		if (count($recentDocuments)) {
			$entries[] = '<li class="dropdown-header">' . $GLOBALS['LANG']->getLL('recent_docs', TRUE) . '</li>';
			$i = 0;
			foreach ($recentDocuments as $md5sum => $recentDocument) {
				$i++;
				$entries[] = $this->renderMenuEntry($recentDocument, $md5sum, TRUE, $i == 1);
			}
		}
		if (count($entries)) {
			$content = implode('', $entries);
		} else {
			$content = '<li class="noOpenDocs">' . $GLOBALS['LANG']->getLL('no_docs', TRUE) . '</li>';
		}
		return $content;
	}

	/**
	 * Returns the recent documents list as an array
	 *
	 * @return array All recent documents as list-items
	 */
	public function renderMenuEntry($document, $md5sum, $isRecentDoc = FALSE, $isFirstDoc = FALSE) {
		$table = $document[3]['table'];
		$uid = $document[3]['uid'];
		$record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($table, $uid);
		if (!is_array($record)) {
			// Record seems to be deleted
			return '';
		}
		$label = htmlspecialchars(strip_tags(htmlspecialchars_decode($document[0])));
		$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($table, $record);
		$link = $GLOBALS['BACK_PATH'] . 'alt_doc.php?' . $document[2];
		$pageId = (int)$document[3]['uid'];
		if ($document[3]['table'] !== 'pages') {
			$pageId = (int)$document[3]['pid'];
		}
		$firstRow = '';
		if ($isFirstDoc) {
			$firstRow = ' first-row';
		}
		if (!$isRecentDoc) {
			$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc', TRUE);
			// Open document
			$closeIcon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-close');
			$entry = '
				<li class="opendoc' . $firstRow . '">
					<div class="linkWrap">
						<a href="#" class="opendocLink" onclick="jump(unescape(\'' . htmlspecialchars($link) . '\'), \'web_list\', \'web\', ' . $pageId . ');TYPO3.OpendocsMenu.toggleMenu(); return false;" target="content">' . $icon . $label . '</a>
						<a href="#" class="close" data-opendocsidentifier="' . $md5sum . '">' . $closeIcon . '</a>
					</div>
				</li>';
		} else {
			// Recently used document
			$entry = '
				<li class="recentdoc' . $firstRow . '">
					<a href="#" onclick="jump(unescape(\'' . htmlspecialchars($link) . '\'), \'web_list\', \'web\', ' . $pageId . '); TYPO3.OpendocsMenu.toggleMenu(); return false;" target="content">' . $icon . $label . '</a>
				</li>';
		}
		return $entry;
	}

	/**
	 * Returns additional attributes for the list item in the toolbar
	 *
	 * This should not contain the "class" or "id" attribute.
	 * Use the methods for setting these attributes
	 *
	 * @return string List item HTML attibutes
	 */
	public function getAdditionalAttributes() {
		return '';
	}

	/**
	 * Return attribute id name
	 *
	 * @return string The name of the ID attribute
	 */
	public function getIdAttribute() {
		return 'tx-opendocs-menu';
	}

	/**
	 * Returns extra classes
	 *
	 * @return array
	 */
	public function getExtraClasses() {
		return array();
	}

	/**
	 * This item has a drop down
	 *
	 * @return bool
	 */
	public function hasDropDown() {
		return TRUE;
	}

	/**
	 * Adds the necessary javascript to the backend
	 *
	 * @return void
	 */
	protected function addJavascriptToBackend() {
		$this->backendReference->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Opendocs/Toolbar/OpendocsMenu');
	}

	/**
	 * Adds the necessary CSS to the backend
	 *
	 * @return void
	 */
	protected function addCssToBackend() {
		$this->backendReference->addCssFile(
			'opendocs',
				\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($this->EXTKEY) . '/Resources/Public/Css/opendocs.css'
		);
	}

	/*******************
	 ***    HOOKS    ***
	 *******************/
	/**
	 * Called as a hook in \TYPO3\CMS\Backend\Utility\BackendUtility::setUpdateSignal, calls a JS function to change
	 * the number of opened documents
	 *
	 * @param array $params
	 * @param unknown_type $ref
	 * @return string list item HTML attributes
	 */
	public function updateNumberOfOpenDocsHook(&$params, $ref) {
		$params['JScode'] = '
			if (top && top.TYPO3.OpendocsMenu) {
				top.TYPO3.OpendocsMenu.updateMenu();
			}
		';
	}

	/******************
	 *** AJAX CALLS ***
	 ******************/
	/**
	 * Closes a document in the session and
	 *
	 * @param array $params Array of parameters from the AJAX interface, currently unused
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type AjaxRequestHandler
	 * @return string List item HTML attributes
	 */
	public function closeDocument($params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler &$ajaxObj = NULL) {
		$md5sum = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('md5sum');
		if ($md5sum && isset($this->openDocs[$md5sum])) {
			// Add the document to be closed to the recent documents
			$this->recentDocs = array_merge(array($md5sum => $this->openDocs[$md5sum]), $this->recentDocs);
			// Allow a maximum of 8 recent documents
			if (count($this->recentDocs) > 8) {
				$this->recentDocs = array_slice($this->recentDocs, 0, 8);
			}
			// Remove it from the list of the open documents, and store the status
			unset($this->openDocs[$md5sum]);
			list(, $docDat) = $GLOBALS['BE_USER']->getModuleData('alt_doc.php', 'ses');
			$GLOBALS['BE_USER']->pushModuleData('alt_doc.php', array($this->openDocs, $docDat));
			$GLOBALS['BE_USER']->pushModuleData('opendocs::recent', $this->recentDocs);
		}
		$this->renderAjax($params, $ajaxObj);
	}

	/**
	 * Renders the menu so that it can be returned as response to an AJAX call
	 *
	 * @param array $params Array of parameters from the AJAX interface, currently unused
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type AjaxRequestHandler
	 * @return void
	 */
	public function renderAjax($params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler &$ajaxObj = NULL) {
		$menuContent = $this->renderMenu();
		$ajaxObj->addContent('opendocsMenu', $menuContent);
	}

	/**
	 * Position relative to others
	 *
	 * @return int
	 */
	public function getIndex() {
		return 30;
	}

}
