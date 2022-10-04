<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 CMS Open Docs',
    'description' => 'Shows opened documents for the TYPO3 backend user.',
    'category' => 'module',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'author' => 'TYPO3 Core Team',
    'author_email' => 'typo3cms@typo3.org',
    'author_company' => '',
    'version' => '12.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.1.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
