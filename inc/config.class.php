<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use League\OAuth2\Client\Token\AccessToken;

class PluginPreludeConfig extends CommonDBTM {
   static $rightname         = 'config';
   static protected $notable = true;

   static function getTypeName($nb=0) {
      return __('Setup');
   }

   function getName($with_comment=0) {
      return __('Prelude configuration', 'prelude');
   }

   static function getConfig() {
      return Config::getConfigurationValues('plugin:Prelude');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($item->getType()=='Config') {
            return self::getName();
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType()=='Config') {
         self::showConfigForm($item);
      }
      return true;
   }

   static function showConfigForm($item) {
      global $CFG_GLPI;

      $plugin = new Plugin();
      if (!$plugin->isInstalled('prelude')) {
         echo __("Please enable the prelude plugin", 'prelude');
         return false;
      }

      $current_config = self::getConfig();

      echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL('Config')."\" method='post'>";
      echo "<input type='hidden' name='config_class' value='".__CLASS__."'>";
      echo "<input type='hidden' name='config_context' value='plugin:Prelude'>";
      echo "<div class='center' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='2'>".__("Plugin's features", 'prelude')."</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td style='width: 15%'>".__("Replace ticket's items association", 'prelude')."</td>";
      echo "<td>";
      Html::showCheckbox(array('name'    => 'replace_items_tickets',
                               'value'   => true,
                               'checked' => $current_config['replace_items_tickets']));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='2'>".__("API Access", 'prelude')."</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td style='width: 15%'>".__("Prelude URL", 'prelude')."</td>";
      echo "<td>";
      echo Html::input('prelude_url', array('value'       => $current_config['prelude_url'],
                                            'placeholder' => "http://path/to/prelude",
                                            'style'       => 'width: 90%'));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td style='width: 15%'>".__("API Client ID", 'prelude')."</td>";
      echo "<td>";
      echo Html::input('api_client_id', array('value' => $current_config['api_client_id'],
                                              'style' => 'width: 90%'));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td style='width: 15%'>".__("API Client Secret", 'prelude')."</td>";
      echo "<td>";
      echo Html::input('api_client_secret', array('value' => $current_config['api_client_secret'],
                                                  'style' => 'width: 90%'));
      echo "</td>";
      echo "</tr>";


      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4' class='center'>";
      echo "<input type='submit' name='update' class='submit' value=\""._sx('button','Save')."\">";
      echo "</td></tr>";

      if (!empty($current_config['prelude_url'])) {

         echo "<tr class='headerRow'>";
         echo "<th colspan='2'>".__('API Status')."</th>";
         echo "</tr>";


         foreach(PluginPreludeAPI::status() as $status_label => $status) {
            echo "<tr class='tab_bg_1'>";
            echo "<td style='width: 15%'>$status_label</td>";
            echo "<td>";
            $color_png = "redbutton.png";
            if ($status) {
               $color_png = "greenbutton.png";
            }
            echo Html::image($CFG_GLPI['url_base']."/pics/$color_png");
            echo "</td>";
            echo "</tr>";
         }

         if (empty($current_config['api_refresh_token'])) {
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2'>";
            echo Html::submit(__("Connect to Prelude API", 'prelude'),
                              array('name' => 'connect_api'));
            echo "</td>";
            echo "</tr>";
         }
      }

      echo "</table></div>";
      Html::closeForm();
   }

   static function storeAccessToken(AccessToken $access_token) {
      $config = new self;
      return $config->update(array('id'               => 1,
                                   'api_access_token' => $access_token->jsonSerialize()));
   }

   static function retrieveAccessToken() {
      $prelude_config = self::getConfig();
      if ($access_token_array = json_decode($prelude_config['api_access_token'], true)) {
         return new AccessToken($access_token_array);
      }

      return false;
   }

   static function getCurrentAccessToken() {
     if ($access_token = self::retrieveAccessToken()) {
         return $access_token->__toString();
      }

      return false;
   }

   /**
    * Database table installation for the item type
    *
    * @param Migration $migration
    * @return boolean True on success
    */
   static function install(Migration $migration) {
      global $CFG_GLPI;

      $current_config = self::getConfig();
      $config         = new Config();

      // api access
      !isset($current_config['prelude_url'])
         ? $config->setConfigurationValues('plugin:Prelude', array('prelude_url' => '')) : '';
      !isset($current_config['api_client_id'])
         ? $config->setConfigurationValues('plugin:Prelude', array('api_client_id' => '')) : '';
      !isset($current_config['api_client_secret'])
         ? $config->setConfigurationValues('plugin:Prelude', array('api_client_secret' => '')) : '';
      !isset($current_config['api_access_token'])
         ? $config->setConfigurationValues('plugin:Prelude', array('api_access_token' => '')) : '';

      // plugin features
      !isset($current_config['replace_items_tickets'])
         ? $config->setConfigurationValues('plugin:Prelude', array('replace_items_tickets' => true))
         : '';
   }

   /**
    * Database table uninstallation for the item type
    *
    * @return boolean True on success
    */
   static function uninstall() {
      global $DB;

      $config = new Config();
      $config->deleteByCriteria("`context` = 'plugin:Prelude'");

      return true;
   }
}