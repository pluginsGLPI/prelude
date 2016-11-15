<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Item_Ticket Class
 *
 *  Relation between Tickets and Items
**/
class pluginPreludeItem_Ticket extends Item_Ticket{


   // From CommonDBRelation
   static public $itemtype_1          = 'Ticket';
   static public $items_id_1          = 'tickets_id';

   static public $itemtype_2          = 'itemtype';
   static public $items_id_2          = 'items_id';
   static public $checkItem_2_Rights  = self::HAVE_VIEW_RIGHT_ON_ITEM;


   /**
    * @param $item   CommonDBTM object
   **/
   static function countForItem(CommonDBTM $item) {

      $table = self::getTable();

      $restrict = "`$table`.`tickets_id` = `glpi_tickets`.`id`
                   AND `$table`.`items_id` = '".$item->getField('id')."'
                   AND `$table`.`itemtype` = '".$item->getType()."'".
                   getEntitiesRestrictRequest(" AND ", "glpi_tickets", '', '', true);

      return countElementsInTable(array($table, 'glpi_tickets'), $restrict);
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
               `id`         INT(11) NOT NULL AUTO_INCREMENT,
               `itemtype`   VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               `items_id`   INT(11) NOT NULL DEFAULT '0',
               `tickets_id` INT(11) NOT NULL DEFAULT '0',
               `plugin_prelude_linktypes_id` INT(11) NOT NULL DEFAULT '0',
               PRIMARY KEY (`id`),
               UNIQUE KEY `unicity` (`itemtype`,`items_id`,`tickets_id`),
               KEY `tickets_id` (`tickets_id`),
               KEY `plugin_prelude_linktypes_id` (`plugin_prelude_linktypes_id`)
            )
            ENGINE = MyISAM
            DEFAULT CHARACTER SET = utf8
            COLLATE = utf8_unicode_ci;";
         $DB->queryOrDie($query, sprintf(__("Error when creating '%s' table", 'prelude'), $table).
                                "<br>".$DB->error());

         $query = "INSERT INTO `$table`
                     (itemtype, items_id, tickets_id)
                     SELECT itemtype, items_id, tickets_id FROM glpi_items_tickets ";
         $DB->queryOrDie($query, sprintf(__("Error when filling '%s' table", 'prelude'), $table).
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