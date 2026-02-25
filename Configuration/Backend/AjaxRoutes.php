<?php

/**
 * Definitions for routes provided by EXT:opendocs
 */
return [
    // List all recent documents as JSON
    'opendocs_list' => [
        'path' => '/opendocs/list',
        'target' => \TYPO3\CMS\Opendocs\Controller\OpenDocumentController::class . '::list',
    ],
];
