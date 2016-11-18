<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://teclib.com/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of prelude.

 prelude is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 prelude is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with prelude. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */
global $CFG_GLPI;
define('PLUGIN_PRELUDE_VERSION', '0.0.3');
define('PRELUDE_ROOTDOC', $CFG_GLPI['root_doc']."/plugins/prelude");
define('PRELUDE_CONFIG_URL', $CFG_GLPI['url_base'].
                             '/front/config.form.php?forcetab=PluginPreludeConfig$1');

// include composer autoload
require_once(__DIR__ . '/vendor/autoload.php');

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_prelude() {
   global $PLUGIN_HOOKS, $CFG_GLPI;

   $PLUGIN_HOOKS['csrf_compliant']['prelude'] = true;

   // include composer autoload
   require_once(__DIR__ . '/vendor/autoload.php');

   $plugin = new Plugin();
   if (isset($_SESSION['glpiID'])
       && $plugin->isActivated('prelude')) {

      $PLUGIN_HOOKS['add_javascript']['prelude'][] = "js/tabs.js";
      $PLUGIN_HOOKS['add_css']['prelude'][] = "css/common.css";

      // get the plugin config
      $prelude_config = PluginPreludeConfig::getConfig();

      // Add a link in the main menu plugins for technician and admin panel
      Plugin::registerClass('PluginPreludeConfig', array('addtabon' => 'Config'));
      $PLUGIN_HOOKS['config_page']['prelude'] = 'front/config.form.php';

      // add a new tab to tickets who replace item_ticket
      if ($prelude_config['replace_items_tickets']) {
         $PLUGIN_HOOKS['add_javascript']['prelude'][] = "js/hide_items_tickets.js.php";

         $PLUGIN_HOOKS['item_add']['prelude'] = array('Item_Ticket' =>
                                                       array('PluginPreludeItem_Ticket',
                                                             'item_Ticket_AfterAdd'));
         $PLUGIN_HOOKS['item_update']['prelude'] = array('Item_Ticket' =>
                                                          array('PluginPreludeItem_Ticket',
                                                                'item_Ticket_AfterUpdate'));
         $PLUGIN_HOOKS['item_delete']['prelude'] = array('Item_Ticket' =>
                                                          array('PluginPreludeItem_Ticket',
                                                                'item_Ticket_AfterDelete'));
         $PLUGIN_HOOKS['item_purge']['prelude'] = array('Item_Ticket' =>
                                                         array('PluginPreludeItem_Ticket',
                                                               'item_Ticket_AfterPurge'));



         Plugin::registerClass('PluginPreludeItem_Ticket', array('addtabon' => 'Ticket'));
         foreach(Ticket::getAllTypesForHelpdesk() as $itemtype => $label) {
            Plugin::registerClass('PluginPreludeItem_Ticket', array('addtabon' => $itemtype));
         }
      }

      // add a new tab to tickets to perform actions relative to prelude
      if ($prelude_config['replace_items_tickets']) {
         Plugin::registerClass('PluginPreludeTicket', array('addtabon' => 'Ticket'));
      }
   }
}


/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_prelude() {
   return [
      'name'           => 'Prelude Siem',
      'version'        => PLUGIN_PRELUDE_VERSION,
      'author'         => '<a href="http://www.teclib.com">Teclib\'</a>',
      'license'        => 'GPL2',
      'homepage'       => '',
      'minGlpiVersion' => '9.1'
   ];
}

/**
 * Check pre-requisites before install
 * OPTIONNAL, but recommanded
 *
 * @return boolean
 */
function plugin_prelude_check_prerequisites() {
   // Strict version check (could be less strict, or could allow various version)
   if (version_compare(GLPI_VERSION,'9.1','lt')) {
      echo "This plugin requires GLPI >= 9.1";
      return false;
   }
   return true;
}

/**
 * Check configuration process
 *
 * @param boolean $verbose Whether to display message on failure. Defaults to false
 *
 * @return boolean
 */
function plugin_prelude_check_config($verbose=false) {
   if (true) { // Your configuration check
      return true;
   }

   if ($verbose) {
      _e('Installed / not configured', 'prelude');
   }
   return false;
}
