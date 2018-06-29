<?php

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginPreludeLinktype extends CommonDropdown {

   // From CommonDBTM
   public $dohistory          = true;
   public $can_be_translated  = true;

   static $rightname          = 'ticket';

   /**
    * {@inheritDoc}
    */
   static function getTypeName($nb=0) {
      return _n('Link type', 'Link types', $nb);
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

      if (!$DB->tableExists($table)) {
         $migration->displayMessage("Installing $table");

         $query = "CREATE TABLE IF NOT EXISTS `$table` (
               `id`            INT(11) NOT NULL AUTO_INCREMENT,
               `name`          VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               `comment`       TEXT COLLATE utf8_unicode_ci,
               `date_mod`      DATETIME DEFAULT NULL,
               `date_creation` DATETIME DEFAULT NULL,
               PRIMARY KEY (`id`)
            )
            ENGINE = MyISAM
            DEFAULT CHARACTER SET = utf8
            COLLATE = utf8_unicode_ci;";
         $DB->queryOrDie($query, sprintf(__("Error when creating '%s' table", 'prelude'), $table).
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
