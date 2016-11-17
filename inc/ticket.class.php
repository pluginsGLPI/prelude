<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginPreludeTicket extends CommonDBTM {
   static $rightname = 'ticket';


   static function getTypeName($nb=0) {
      return __("Prelude", 'prelude');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($withtemplate) {
         return '';
      }
      if ($item->getType() == 'Ticket') {
         $nb = countElementsInTable(self::getTable(),
                                    "`tickets_id` = '".$item->getID()."'");
         return self::createTabEntry(self::getTypeName($nb), $nb);
      }

      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      if ($item->getType() == 'Ticket') {
         self::showForTicket($item);
      }
      return true;
   }

   /**
    * Print the HTML array for Items linked to a ticket
    *
    * @param $ticket Ticket object
    *
    * @return Nothing (display)
   **/
   static function showForTicket(Ticket $ticket) {
      echo "<a class='vsubmit' href='".Toolbox::getItemTypeFormURL('Problem').
                                    "?tickets_id=".$ticket->getID()."'>";
         _e('Create a problem from this ticket');
         echo "</a>";
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
               `id`            INT(11) NOT NULL AUTO_INCREMENT,
               `tickets_id`    INT(11) NOT NULL DEFAULT '0',
               `condition_url` TEXT COLLATE utf8_unicode_ci,
               PRIMARY KEY (`id`),
               KEY `tickets_id` (`tickets_id`)
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