<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}


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
      echo "<td style='width: 15%'>".__("API Token", 'prelude')."</td>";
      echo "<td>";
      echo Html::input('api_token', array('value' => $config->fields['api_token'],
                                          'style' => 'width: 90%'));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td style='width: 15%'>".__("API Refresh yoken", 'prelude')."</td>";
      echo "<td>";
      echo Html::input('api_refresh_token', array('value' => $config->fields['api_refresh_token'],
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
      }

      $config->showFormButtons($options);
   }

   /**
    * Database table installation for the item type
    *
    * @param Migration $migration
    * @return boolean True on success
    */
   public static function install(Migration $migration) {
      global $DB;

      $table = self::getTable();

      if (!TableExists($table)) {
         $migration->displayMessage("Installing $table");

         // Create Forms table
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
               `id`            INT(11) NOT NULL,
               `prelude_url`   TEXT COLLATE utf8_unicode_ci,
               `token`         VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               `refresh_token` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               `date_mod`      DATETIME default NULL,
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
   public static function uninstall() {
      global $DB;

      $obj = new self();
      $DB->query('DROP TABLE IF EXISTS `'.$obj->getTable().'`');

      // Delete logs of the plugin
      $DB->query("DELETE FROM `glpi_logs` WHERE itemtype = '".__CLASS__."'");

      return true;
   }
}