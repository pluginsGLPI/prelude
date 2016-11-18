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
         $nb = count(self::getForticket($item));
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

   static function getForTicket(Ticket $ticket) {
      $prelude_ticket = new self;
      return $prelude_ticket->find("`tickets_id` = ".$ticket->getID());
   }

   /**
    * Print the HTML array for Items linked to a ticket
    *
    * @param $ticket Ticket object
    *
    * @return Nothing (display)
   **/
   static function showForTicket(Ticket $ticket) {
      global $CFG_GLPI;

      $rand           = mt_rand();
      $url            = Toolbox::getItemTypeFormURL(__CLASS__);

      if (!PluginPreludeAPI::globalStatus()) {
         $message = __("Prelude API is not connected, click to display configuration");
         echo "<a href='".PRELUDE_CONFIG_URL."'>";
         Html::displayTitle($CFG_GLPI['root_doc']."/pics/warning.png", $message, $message);
         echo "</a>";
         return false;
      }

      echo "<a class='vsubmit' href='".Toolbox::getItemTypeFormURL('Problem').
                                    "?tickets_id=".$ticket->getID()."'>";
      _e('Create a problem from this ticket');
      echo "</a>";
      echo "<br><br>";

      echo "<form name='ticket_form$rand' id='ticket_form$rand' method='post'
             action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      $found = self::getForticket($ticket);
      if (count($found) <= 0) {
         _e("No alerts found  for this ticket", 'prelude');
      } else {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Alerts', 'prelude')."</th></tr>";

         foreach ($found as $prelude_tickets_id => $current) {
            echo "<tr class='tab_bg_2'><th colspan='2'>";
            echo $current['name']."&nbsp;";
            echo Html::image(PRELUDE_ROOTDOC."/pics/link.png",
                             array('class' => 'pointer',
                                   'title' => __("View theses alerts in prelude", 'prelude'),
                                   'url'   => $current['condition_url']));
            echo Html::image(PRELUDE_ROOTDOC."/pics/delete.png",
                             array('class' => 'pointer prelude-delete-bloc',
                                   'title' => __("delete this link", 'prelude'),
                                   'url'   => $url."?delete_link&id=$prelude_tickets_id"));
            echo "</th></tr>";
         }
         echo "</table>";
      }
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
               `name`          VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               `condition_url` TEXT COLLATE utf8_unicode_ci,
               `condition_api` TEXT COLLATE utf8_unicode_ci,
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