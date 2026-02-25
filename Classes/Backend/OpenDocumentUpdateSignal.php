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

namespace TYPO3\CMS\Opendocs\Backend;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Domain\Model\Element\ImmediateActionElement;

/**
 * Hook for dispatching the open document store update event.
 *
 * Called as a hook in \TYPO3\CMS\Backend\Utility\BackendUtility::getUpdateSignalDetails
 * to trigger a refresh of the open documents store in JavaScript.
 *
 * @internal This class is a specific hook implementation and is not part of the TYPO3's Core API.
 */
#[Autoconfigure(public: true)]
final class OpenDocumentUpdateSignal
{
    public function updateNumber(array &$params): void
    {
        $params['html'] = ImmediateActionElement::dispatchCustomEvent(
            'typo3:opendocs:updateRequested',
            null,
            true
        );
    }
}
