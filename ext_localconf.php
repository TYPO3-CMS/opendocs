<?php

declare(strict_types=1);

use TYPO3\CMS\Opendocs\Backend\OpenDocumentUpdateSignal;
use TYPO3\CMS\Opendocs\EventListener\TrackOpenDocumentsEventListener;

defined('TYPO3') or die();

// Register update signal to update the number of open documents
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['updateSignalHook']['opendocs:updateRequested'] = OpenDocumentUpdateSignal::class . '->updateNumber';

// Register DataHandler hooks for tracking record updates and deletions
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['opendocs'] = TrackOpenDocumentsEventListener::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['opendocs'] = TrackOpenDocumentsEventListener::class;
