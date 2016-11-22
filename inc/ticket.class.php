<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginPreludeTicket extends CommonDBTM {
   static $rightname = 'ticket';


   /**
    * {@inheritDoc}
    */
   static function getTypeName($nb=0) {
      return __("Prelude", 'prelude');
   }

   /**
    * {@inheritDoc}
    */
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

   /**
    * {@inheritDoc}
    */
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

      if (!PluginPreludeAPIClient::globalStatus()) {
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
         echo "<h2>".__('Alerts', 'prelude')."</h2>";
         echo "<table class='tab_cadre_fixe'>";

         foreach ($found as $prelude_tickets_id => $current) {
            if ($params_api = json_decode($current['params_api'], true)) {
               $alerts = PluginPreludeAPIClient::getAlerts($params_api);
               $nb     = count($alerts);

               echo "<tr><th colspan='2'>";
               echo "<input type='checkbox' name='toggle'
                            class='toggle_alert' id='toggle_$prelude_tickets_id' />";
               echo "<label for='toggle_$prelude_tickets_id'>".$current['name'].
                    "&nbsp; <sup>$nb<sup></label>";
               echo Html::image(PRELUDE_ROOTDOC."/pics/link.png",
                                array('class' => 'pointer',
                                      'title' => __("View theses alerts in prelude", 'prelude'),
                                      'url'   => $current['condition_url']));
               echo Html::image(PRELUDE_ROOTDOC."/pics/delete.png",
                                array('class' => 'pointer prelude-delete-bloc',
                                      'title' => __("delete this group of alerts", 'prelude'),
                                      'url'   => $url."?delete_link&id=$prelude_tickets_id"));

               echo "<table class='tab_cadre_fixehov togglable'>";
               echo "<tr class='tab_bg_2'>";
               echo "<th>messageid</th>";
               echo "<th>".__("Classification", 'prelude')."</th>";
               echo "<th>".__("Source", 'prelude')."</th>";
               echo "<th>".__("Target", 'prelude')."</th>";
               echo "<th>".__("Analyzer", 'prelude')."</th>";
               echo "<th>".__("Date")."</th>";
               echo "<th></th>";
               echo "</tr>";

               foreach($alerts as $messageid => $alert) {
                  echo "<tr class='tab_bg_1'>";
                  echo "<td>".$alert['alert.messageid']."</td>";
                  echo "<td>".$alert['alert.classification.text']."</td>";
                  echo "<td>".$alert['alert.source(0).node.address(0).address']."</td>";
                  echo "<td>".$alert['alert.target(0).node.address(0).address']."</td>";
                  echo "<td>".$alert['alert.analyzer(-1).name']."</td>";
                  echo "<td>".$alert['alert.create_time']."</td>";
                  echo "<td><img title='".__("See alert detail", 'prelude')."' src='".
                       PRELUDE_ROOTDOC."/pics/eye.png' class='pointer'></td>";
                  echo "</tr>";
               }
               echo "</table>";
               echo "</th></tr>";
            }
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
               `params_api`    TEXT COLLATE utf8_unicode_ci,
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