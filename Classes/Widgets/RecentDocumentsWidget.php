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

namespace TYPO3\CMS\Opendocs\Widgets;

use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Dashboard\Widgets\JavaScriptInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetContext;
use TYPO3\CMS\Dashboard\Widgets\WidgetRendererInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetResult;

/**
 * Widget to show recently opened documents in the backend.
 *
 * The widget renders a custom element that reads document data
 * from the central OpenDocumentStore in the top frame.
 */
final readonly class RecentDocumentsWidget implements WidgetRendererInterface, JavaScriptInterface
{
    public function __construct(
        private WidgetConfigurationInterface $configuration,
        private BackendViewFactory $backendViewFactory,
    ) {}

    public function getSettingsDefinitions(): array
    {
        return [];
    }

    public function renderWidget(WidgetContext $context): WidgetResult
    {
        $view = $this->backendViewFactory->create($context->request, ['typo3/cms-dashboard', 'typo3/cms-opendocs']);
        $view->assignMultiple([
            'settings' => $context->settings,
            'configuration' => $this->configuration,
        ]);

        return new WidgetResult(
            content: $view->render('Widget/RecentDocuments'),
        );
    }

    public function getJavaScriptModuleInstructions(): array
    {
        return [
            JavaScriptModuleInstruction::create('@typo3/opendocs/widget/recent-documents-widget-element.js'),
        ];
    }
}
