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

define('PLUGIN_PRELUDE_VERSION', '0.2.0');

// Minimal GLPI version, inclusive
define('PLUGIN_PRELUDE_MIN_GLPI', '9.2');
// Maximum GLPI version, exclusive
define('PLUGIN_PRELUDE_MAX_GLPI', '9.4');

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

      $PLUGIN_HOOKS['add_javascript']['prelude'][] = "js/common.js";
      $PLUGIN_HOOKS['add_javascript']['prelude'][] = "js/tabs.js";
      $PLUGIN_HOOKS['add_css']['prelude'][] = "css/common.css";

      // get the plugin config
      $prelude_config = PluginPreludeConfig::getConfig();

      // Add a link in the main menu plugins for technician and admin panel
      Plugin::registerClass('PluginPreludeConfig', ['addtabon' => 'Config']);
      $PLUGIN_HOOKS['config_page']['prelude'] = 'front/config.form.php';

      // add a new tab to tickets who replace item_ticket
      if ($prelude_config['replace_items_tickets']) {
         $PLUGIN_HOOKS['add_javascript']['prelude'][] = "js/hide_items_tickets.js.php";

         $PLUGIN_HOOKS['item_add']['prelude'] = [
            'Item_Ticket' => ['PluginPreludeItem_Ticket', 'item_Ticket_AfterAdd']
         ];
         $PLUGIN_HOOKS['item_update']['prelude'] = [
            'Item_Ticket' => ['PluginPreludeItem_Ticket', 'item_Ticket_AfterUpdate']
         ];
         $PLUGIN_HOOKS['item_delete']['prelude'] = [
            'Item_Ticket' => ['PluginPreludeItem_Ticket', 'item_Ticket_AfterDelete']
         ];
         $PLUGIN_HOOKS['item_purge']['prelude'] = [
            'Item_Ticket' => ['PluginPreludeItem_Ticket', 'item_Ticket_AfterPurge']
         ];

         Plugin::registerClass('PluginPreludeItem_Ticket', ['addtabon' => 'Ticket']);
         foreach (Ticket::getAllTypesForHelpdesk() as $itemtype => $label) {
            Plugin::registerClass('PluginPreludeItem_Ticket', ['addtabon' => $itemtype]);
         }
      }

      // add a new tab to tickets to perform actions relative to prelude
      if ($prelude_config['replace_items_tickets']) {
         Plugin::registerClass('PluginPreludeTicket', ['addtabon' => 'Ticket']);
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
      'requirements'   => [
         'glpi' => [
            'min' => PLUGIN_PRELUDE_MIN_GLPI,
            'max' => PLUGIN_PRELUDE_MAX_GLPI,
         ],
         'php' => [
            'exts' => [
               'curl' => [
                  'required' => true,
               ]
            ],
            'params' => [
               'allow_url_fopen',
            ],
         ]
      ]
   ];
}

/**
 * Check pre-requisites before install
 * OPTIONNAL, but recommanded
 *
 * @return boolean
 */
function plugin_prelude_check_prerequisites() {

   //Requirements check is not done by core in GLPI < 9.2 but has to be delegated to core in GLPI >= 9.2.
   if (!method_exists('Plugin', 'checkGlpiVersion')) {
      $version = preg_replace('/^((\d+\.?)+).*$/', '$1', GLPI_VERSION);
      $matchMinGlpiReq = version_compare($version, PLUGIN_PRELUDE_MIN_GLPI, '>=');
      $matchMaxGlpiReq = version_compare($version, PLUGIN_PRELUDE_MAX_GLPI, '<');

      if (!$matchMinGlpiReq || !$matchMaxGlpiReq) {
         echo vsprintf(
            'This plugin requires GLPI >= %1$s and < %2$s.',
            [
               PLUGIN_PRELUDE_MIN_GLPI,
               PLUGIN_PRELUDE_MAX_GLPI,
            ]
         );
         return false;
      }

      if (!extension_loaded('curl')) {
         echo "PHP-Curl extension is required";
         return false;
      }

      if (ini_get('allow_url_fopen') != 1) {
         echo "allow_url_fopen=1 is required in your php.ini";
         return false;
      }
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
function plugin_prelude_check_config($verbose = false) {

   return true;
}
