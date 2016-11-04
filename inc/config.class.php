<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use League\OAuth2\Client\Token\AccessToken;

class PluginPreludeConfig extends CommonDBTM {
   static private $_instance = NULL;
   static $rightname         = 'config';

   static function getTypeName($nb=0) {
      return __('Setup');
   }

   function getName($with_comment=0) {
      return __('Prelude configuration', 'prelude');
   }

   /**
    * Singleton for the unique config record
    */
   static function getInstance() {

      if (!isset(self::$_instance)) {
         self::$_instance = new self();
         if (!self::$_instance->getFromDB(1)) {
            self::$_instance->getEmpty();
         }
      }
      return self::$_instance;
   }

   static function getConfig() {
      $instance = self::getInstance();
      return $instance->fields;
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

      $config = self::getInstance();
      $options = ['colspan' => 1,
                  'candel'  => false];
      $config->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td style='width: 15%'>".__("Prelude URL", 'prelude')."</td>";
      echo "<td>";
      echo Html::input('prelude_url', array('value'       => $config->fields['prelude_url'],
                                            'placeholder' => "http://path/to/prelude",
                                            'style'       => 'width: 90%'));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td style='width: 15%'>".__("API Client ID", 'prelude')."</td>";
      echo "<td>";
      echo Html::input('api_client_id', array('value' => $config->fields['api_client_id'],
                                              'style' => 'width: 90%'));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td style='width: 15%'>".__("API Client Secret", 'prelude')."</td>";
      echo "<td>";
      echo Html::input('api_client_secret', array('value' => $config->fields['api_client_secret'],
                                                  'style' => 'width: 90%'));
      echo "</td>";
      echo "</tr>";


      if (!empty($config->fields['prelude_url'])) {

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

         if (empty($config->fields['api_refresh_token'])) {
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2'>";
            echo Html::submit(__("Connect to Prelude API", 'prelude'),
                              array('name' => 'connect_api'));
            echo "</td>";
            echo "</tr>";
         }
      }

      $config->showFormButtons($options);
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
      global $DB;

      $table = self::getTable();

      if (!TableExists($table)) {
         $migration->displayMessage("Installing $table");

         $query = "CREATE TABLE IF NOT EXISTS `$table` (
               `id`                INT(11) NOT NULL,
               `prelude_url`       TEXT COLLATE utf8_unicode_ci,
               `api_client_id`     VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               `api_client_secret` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               `api_access_token`  TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
               `date_mod`          DATETIME default NULL,
               PRIMARY KEY (`id`)
            )
            ENGINE = MyISAM
            DEFAULT CHARACTER SET = utf8
            COLLATE = utf8_unicode_ci;";
         $DB->queryOrDie($query, __('Error in creating glpi_plugin_prelude_configs', 'prelude').
                                "<br>".$DB->error());

         $query = "INSERT INTO `$table`
                     (id, date_mod)
                   VALUES
                     (1, NOW())";
         $DB->queryOrDie($query, __('Error when filling glpi_plugin_prelude_configs', 'prelude').
                                "<br>".$DB->error());
      }
   }

   /**
    * Database table uninstallation for the item type
    *
    * @return boolean True on success
    */
   static function uninstall() {
      global $DB;

      $obj = new self();
      $DB->query('DROP TABLE IF EXISTS `'.$obj->getTable().'`');

      // Delete logs of the plugin
      $DB->query("DELETE FROM `glpi_logs` WHERE itemtype = '".__CLASS__."'");

      return true;
   }
}