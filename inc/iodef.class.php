<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginPreludeIODEF extends CommonDBChild {
   static public $itemtype = 'Problem';
   static public $items_id = 'problems_id';

   /**
    * {@inheritDoc}
    */
   static function getTypeName($nb=0) {
      return __("IODEF", 'prelude');
   }

   /**
    * {@inheritDoc}
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($withtemplate) {
         return '';
      }
      if ($item instanceof Problem) {
         $nb = count(self::getForProblem($item));
         return self::createTabEntry(self::getTypeName($nb), $nb);
      }

      return '';
   }

   /**
    * {@inheritDoc}
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      if ($item instanceof Problem) {
         self::showForProblem($item);
      }
      return true;
   }

   /**
    * Show form to declare a iodef in problems
    *
    * @param $problem Problem object
    *
    * @return null
   **/
   static function showForProblem(Problem $problem) {

   }

   static function getForProblem(Problem $problem) {
      $iodef = new self;
      return $iodef->find("`".self::$items_id."` = ".$problem->getID());
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
               `id`           INT(11) NOT NULL AUTO_INCREMENT,
               `problems_id`  INT(11) NOT NULL DEFAULT '0',
               `documents_id` INT(11) NOT NULL DEFAULT '0',
               `json_content` TEXT COLLATE utf8_unicode_ci,
               PRIMARY KEY (`id`),
               KEY `problems_id` (`problems_id`),
               KEY `documents_id` (`documents_id`)
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