<?php

declare(strict_types=1);

namespace TYPO3\CMS\Opendocs;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Dashboard\WidgetRegistry;
use TYPO3\CMS\Opendocs\Widgets\RecentDocumentsWidget;

return function (ContainerConfigurator $configurator, ContainerBuilder $containerBuilder) {
    $services = $configurator->services();

    /**
     * Check if WidgetRegistry is defined, which means that EXT:dashboard is available.
     * Registration directly in Services.yaml will break without EXT:dashboard installed!
     */
    if ($containerBuilder->hasDefinition(WidgetRegistry::class)) {
        $services->set('dashboard.widget.recentDocuments')
            ->class(RecentDocumentsWidget::class)
            ->autowire()
            ->tag('dashboard.widget', [
                'identifier' => 'recentDocuments',
                'groupNames' => 'content',
                'title' => 'opendocs.widget_recentdocuments:widget.title',
                'description' => 'opendocs.widget_recentdocuments:widget.description',
                'iconIdentifier' => 'content-widget-list',
                'height' => 'medium',
                'width' => 'medium',
            ]);
    }
};
