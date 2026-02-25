<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Opendocs\EventListener;

use TYPO3\CMS\Backend\Controller\Event\AfterBackendPageRenderEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * Loads the open document store module and translations in the backend.
 *
 * The open document store provides state management for open documents
 * and needs to be loaded early to listen for update events.
 *
 * @internal
 */
final readonly class AfterBackendPageRenderEventListener
{
    public function __construct(
        private PageRenderer $pageRenderer,
    ) {}

    #[AsEventListener(event: AfterBackendPageRenderEvent::class)]
    public function __invoke(): void
    {
        $this->pageRenderer->loadJavaScriptModule('@typo3/opendocs/open-document-store.js');
    }
}
