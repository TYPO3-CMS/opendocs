<?php
namespace TYPO3\CMS\Opendocs\Backend\ToolbarItems;

/*
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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Main functionality to render a list of all open documents in the top bar of the TYPO3 Backend
 *
 * This class also contains hooks and AJAX calls related to the toolbar item dynamic updating processing
 */
class OpendocsToolbarItem implements ToolbarItemInterface
{
    /**
     * @var array
     */
    protected $openDocs = [];

    /**
     * @var array
     */
    protected $recentDocs = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->loadDocsFromUserSession();
    }

    /**
     * Checks whether the user has access to this toolbar item
     *
     * @return bool TRUE if user has access, FALSE if not
     */
    public function checkAccess()
    {
        $conf = $this->getBackendUser()->getTSConfig('backendToolbarItem.tx_opendocs.disabled');
        return (int)$conf['value'] !== 1;
    }

    /**
     * Loads the opened and recently opened documents from the user
     */
    public function loadDocsFromUserSession()
    {
        $backendUser = $this->getBackendUser();
        $openDocs = $backendUser->getModuleData('FormEngine', 'ses');
        if ($openDocs !== null) {
            list($this->openDocs, ) = $openDocs;
        }
        $this->recentDocs = $backendUser->getModuleData('opendocs::recent') ?: [];
    }

    /**
     * Render toolbar icon via Fluid
     *
     * @return string HTML
     */
    public function getItem()
    {
        $view = $this->getFluidTemplateObject('ToolbarItem.html');
        $view->assign('numDocs', count($this->openDocs));
        return $view->render();
    }

    /**
     * Render drop down via Fluid
     *
     * @return string HTML
     */
    public function getDropDown()
    {
        $view = $this->getFluidTemplateObject('DropDown.html');
        $view->assignMultiple([
            'openDocuments' => $this->getMenuEntries($this->openDocs),
            // If there are "recent documents" in the list, add them
            'recentDocuments' => $this->getMenuEntries($this->recentDocs)
        ]);
        return $view->render();
    }

    /**
     * Get menu entries for all eligible records
     *
     * @param array $documents
     * @return array
     */
    protected function getMenuEntries(array $documents): array
    {
        $entries = [];
        foreach ($documents as $md5sum => $document) {
            $menuEntry = $this->getMenuEntry($document, $md5sum);
            if (is_array($menuEntry)) {
                $entries[] = $menuEntry;
            }
        }
        return $entries;
    }

    /**
     * Returns the data for a recent or open document
     *
     * @param array $document
     * @param string $md5sum
     * @return array The data of a recent or closed document, or null if no record was found (e.g. deleted)
     */
    protected function getMenuEntry($document, $md5sum)
    {
        $table = $document[3]['table'];
        $uid = $document[3]['uid'];
        $record = BackendUtility::getRecordWSOL($table, $uid);
        if (!is_array($record)) {
            // Record seems to be deleted
            return null;
        }
        $result = [];
        $result['table'] = $table;
        $result['record'] = $record;
        $label = htmlspecialchars(strip_tags(htmlspecialchars_decode($document[0])));
        $result['label'] = $label;
        $link = BackendUtility::getModuleUrl('record_edit') . '&' . $document[2];
        $pageId = (int)$document[3]['uid'];
        if ($document[3]['table'] !== 'pages') {
            $pageId = (int)$document[3]['pid'];
        }
        $onClickCode = 'jump(' . GeneralUtility::quoteJSvalue($link) . ', \'web_list\', \'web\', ' . $pageId . '); TYPO3.OpendocsMenu.toggleMenu(); return false;';
        $result['onClickCode'] = $onClickCode;
        $result['md5sum'] = $md5sum;
        return $result;
    }

    /**
     * No additional attributes
     *
     * @return string List item HTML attibutes
     */
    public function getAdditionalAttributes()
    {
        return [];
    }

    /**
     * This item has a drop down
     *
     * @return bool
     */
    public function hasDropDown()
    {
        return true;
    }

    /*******************
     ***    HOOKS    ***
     *******************/
    /**
     * Called as a hook in \TYPO3\CMS\Backend\Utility\BackendUtility::getUpdateSignalCode, calls a JS function to change
     * the number of opened documents
     *
     * @param array $params
     */
    public function updateNumberOfOpenDocsHook(&$params)
    {
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
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function closeDocument(ServerRequestInterface $request, ResponseInterface $response)
    {
        $md5sum = isset($request->getParsedBody()['md5sum']) ? $request->getParsedBody()['md5sum'] : $request->getQueryParams()['md5sum'];
        if ($md5sum && isset($this->openDocs[$md5sum])) {
            $backendUser = $this->getBackendUser();
            // Add the document to be closed to the recent documents
            $this->recentDocs = array_merge([$md5sum => $this->openDocs[$md5sum]], $this->recentDocs);
            // Allow a maximum of 8 recent documents
            if (count($this->recentDocs) > 8) {
                $this->recentDocs = array_slice($this->recentDocs, 0, 8);
            }
            // Remove it from the list of the open documents, and store the status
            unset($this->openDocs[$md5sum]);
            list(, $docDat) = $backendUser->getModuleData('FormEngine', 'ses');
            $backendUser->pushModuleData('FormEngine', [$this->openDocs, $docDat]);
            $backendUser->pushModuleData('opendocs::recent', $this->recentDocs);
        }
        return $this->renderMenu($request, $response);
    }

    /**
     * Renders the menu so that it can be returned as response to an AJAX call
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function renderMenu(ServerRequestInterface $request, ResponseInterface $response)
    {
        $response->getBody()->write($this->getDropDown());
        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Position relative to others
     *
     * @return int
     */
    public function getIndex()
    {
        return 30;
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns a new standalone view, shorthand function
     *
     * @param string $filename Which templateFile should be used.
     * @return StandaloneView
     */
    protected function getFluidTemplateObject(string $filename): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths(['EXT:opendocs/Resources/Private/Layouts']);
        $view->setPartialRootPaths([
            'EXT:backend/Resources/Private/Partials/ToolbarItems',
            'EXT:opendocs/Resources/Private/Partials/ToolbarItems'
        ]);
        $view->setTemplateRootPaths(['EXT:opendocs/Resources/Private/Templates/ToolbarItems']);

        $view->setTemplate($filename);

        $view->getRequest()->setControllerExtensionName('Opendocs');
        return $view;
    }
}
