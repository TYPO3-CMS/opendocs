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

import * as $ from'jquery';
import Icons = require('TYPO3/CMS/Backend/Icons');
import Viewport = require('TYPO3/CMS/Backend/Viewport');

enum Selectors {
  containerSelector = '#typo3-cms-opendocs-backend-toolbaritems-opendocstoolbaritem',
  closeSelector = '.t3js-topbar-opendocs-close',
  menuContainerSelector = '.dropdown-menu',
  toolbarIconSelector = '.toolbar-item-icon .t3js-icon',
  openDocumentsItemsSelector = '.t3js-topbar-opendocs-item',
  counterSelector = '#tx-opendocs-counter',
  entrySelector = '.t3js-open-doc',
}

/**
 * Module: TYPO3/CMS/Opendocs/OpendocsMenu
 * main JS part taking care of
 *  - navigating to the documents
 *  - updating the menu
 */
class OpendocsMenu {
  private readonly hashDataAttributeName: string = 'opendocsidentifier';

  /**
   * Updates the number of open documents in the toolbar according to the
   * number of items in the menu bar.
   */
  private static updateNumberOfDocs(): void {
    const num: number = $(Selectors.containerSelector).find(Selectors.openDocumentsItemsSelector).length;
    $(Selectors.counterSelector).text(num).toggle(num > 0);
  }

  constructor() {
    Viewport.Topbar.Toolbar.registerEvent((): void => {
      this.initializeEvents();
      this.updateMenu();
    });
  }

  /**
   * Displays the menu and does the AJAX call to the TYPO3 backend
   */
  public updateMenu(): void {
    let $toolbarItemIcon = $(Selectors.toolbarIconSelector, Selectors.containerSelector);
    let $existingIcon = $toolbarItemIcon.clone();

    Icons.getIcon('spinner-circle-light', Icons.sizes.small).done((spinner: string): void => {
      $toolbarItemIcon.replaceWith(spinner);
    });

    $.ajax({
      url: TYPO3.settings.ajaxUrls.opendocs_menu,
      type: 'post',
      cache: false,
      success: (data: string) => {
        $(Selectors.containerSelector).find(Selectors.menuContainerSelector).html(data);
        OpendocsMenu.updateNumberOfDocs();
        $(Selectors.toolbarIconSelector, Selectors.containerSelector).replaceWith($existingIcon);
      }
    });
  }

  private initializeEvents(): void {
    // send a request when removing an opendoc
    $(Selectors.containerSelector).on('click', Selectors.closeSelector, (evt: JQueryEventObject): void => {
      evt.preventDefault();
      const md5 = $(evt.currentTarget).data(this.hashDataAttributeName);
      if (md5) {
        this.closeDocument(md5);
      }
    }).on('click', Selectors.entrySelector, (evt: JQueryEventObject): void => {
      evt.preventDefault();

      const $entry = $(evt.currentTarget);
      this.toggleMenu();

      window.jump($entry.attr('href'), 'web_list', 'web', $entry.data('pid'));
    });
  }

  /**
   * Closes an open document
   */
  private closeDocument(md5sum: string): void {
    $.ajax({
      url: TYPO3.settings.ajaxUrls.opendocs_closedoc,
      type: 'post',
      cache: false,
      data: {
        md5sum: md5sum
      },
      success: (data: string): void => {
        $(Selectors.menuContainerSelector, Selectors.containerSelector).html(data);
        OpendocsMenu.updateNumberOfDocs();
        // Re-open the menu after closing a document
        $(Selectors.containerSelector).toggleClass('open');
      }
    });
  }

  /**
   * closes the menu (e.g. when clicked on an item)
   */
  private toggleMenu = (): void => {
    $('.scaffold').removeClass('scaffold-toolbar-expanded');
    $(Selectors.containerSelector).toggleClass('open');
  }
}

let opendocsMenuObject: OpendocsMenu;
opendocsMenuObject = new OpendocsMenu();

if (typeof TYPO3 !== 'undefined') {
  TYPO3.OpendocsMenu = opendocsMenuObject;
}

export = opendocsMenuObject;
