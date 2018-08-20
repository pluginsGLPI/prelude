<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginPreludeTicket extends CommonDBTM {
   static $rightname = 'ticket';


   /**
    * {@inheritDoc}
    */
   static function getTypeName($nb = 0) {
      return __("Prelude", 'prelude');
   }

   /**
    * {@inheritDoc}
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

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
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
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
    * @return null
   **/
   static function showForTicket(Ticket $ticket) {
      global $CFG_GLPI;

      $url = Toolbox::getItemTypeFormURL(__CLASS__);

      if (!PluginPreludeAPIClient::globalStatus()) {
         $message = __("Prelude API is not connected, click to display configuration");
         echo "<a href='".PRELUDE_CONFIG_URL."'>";
         Html::displayTitle($CFG_GLPI['root_doc']."/pics/warning.png", $message, $message);
         echo "</a>";
      }

      echo "<a class='vsubmit' href='".Toolbox::getItemTypeFormURL('Problem').
                                    "?tickets_id=".$ticket->getID()."'>";
      echo __('Create a problem from this ticket');
      echo "</a>";
      echo "<br><br>";

      $found = self::getForticket($ticket);
      if (count($found) <= 0) {
         echo __("No alerts found  for this ticket", 'prelude');
         echo "&nbsp;";
         self::importAlertsForm($ticket->getID());
      } else {
         echo "<h2>";
         echo _n('Alert', 'Alerts', 2, 'prelude');
         echo "&nbsp;";
         self::importAlertsForm($ticket->getID());
         echo "</h2>";

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
               if (!empty($current['url'])) {
                  echo Html::image(PRELUDE_ROOTDOC."/pics/link.png",
                                   ['class' => 'pointer',
                                    'title' => __("View theses alerts in prelude", 'prelude'),
                                    'url'   => $current['url']]);
               }
               echo Html::image(PRELUDE_ROOTDOC."/pics/delete.png",
                                ['class' => 'pointer prelude-delete-bloc',
                                 'title' => __("delete this group of alerts", 'prelude'),
                                 'url'   => $url."?delete_link&id=$prelude_tickets_id"]);

               echo "<div class='togglable'>";
               echo "<div class='prelude_criteria'>";
               foreach ($params_api['criteria'] as $criterion) {
                  echo "<span class='prelude_criterion'>$criterion</span>";
               }
               echo "<div>"; // .prelude_criteria
               if (count($alerts)) {
                  echo "<table class='tab_cadre_fixehov togglable'>";
                  echo "<tr class='tab_bg_2'>";
                  echo "<th>".__("Classification", 'prelude')."</th>";
                  echo "<th>".__("Source", 'prelude')."</th>";
                  echo "<th>".__("Target", 'prelude')."</th>";
                  echo "<th>".__("Analyzer", 'prelude')."</th>";
                  echo "<th>".__("Date")."</th>";
                  // echo "<th></th>";
                  echo "</tr>";

                  foreach ($alerts as $messageid => $alert) {
                     $create_time = Html::convDateTime(date("Y-m-d H:i",
                                                            strtotime($alert['alert.create_time'])));

                     echo "<tr class='tab_bg_1'>";
                     echo "<td>".$alert['alert.classification.text']."</td>";
                     echo "<td>".$alert['alert.source(0).node.address(0).address']."</td>";
                     echo "<td>".$alert['alert.target(0).node.address(0).address']."</td>";
                     echo "<td>".$alert['alert.analyzer(-1).name']."</td>";
                     echo "<td>".$create_time."</td>";
                     /*echo "<td><img title='".__("See alert detail", 'prelude')."' src='".
                          PRELUDE_ROOTDOC."/pics/eye.png' class='pointer'></td>";*/
                     echo "</tr>";
                  }
                  echo "</table>";
               } else {
                  echo "<div class='togglable'>";
                  echo __("No alerts found  for theses criteria", 'prelude');
                  echo "</div>";
               }
               echo "<div>"; // .togglable
               echo "</th></tr>";
            }
         }
         echo "</table>";
      }
   }

   /**
    * Print a dialog to import alerts for a ticket
    * @param  integer $tickets_id id of the ticket to link
    */
   static function importAlertsForm($tickets_id = 0) {
      echo Html::image(PRELUDE_ROOTDOC."/pics/import.png",
                          ['class'   => 'pointer',
                           'title'   => __("Import alerts from prelude", 'prelude'),
                           'onclick' => "$('#add_alerts').dialog('open');"]);

      echo "<div id='add_alerts' class='invisible'>";
      echo "<form method='post' action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

      echo "<div class='field'>";
      echo "<label>".__("Name").":</label>";
      echo Html::input('name', ['required' => 'required']);
      echo "</div>";

      echo "<div class='field'>";
      echo "<label>".__("Url").":</label>";
      echo Html::input('url');
      echo "</div>";

      echo "<div class='field'>";
      echo "<label>".__("Prelude criteria", 'prelude').":</label>";
      echo Html::input('params_api[criteria][]', ['required' => 'required',
                                                  'class'    => 'criterion',
                                                  'placeholder' => "alert.create_time > 'xxxx-xx-xx'"]);
      echo Html::image(PRELUDE_ROOTDOC."/pics/add.png",
                       ['class'   => 'pointer add_criterion',
                        'title'   => __("add prelude criterion", 'prelude'),
                        'onclick' => "add_criterion();"]);
      echo "</div>";

      echo Html::hidden('tickets_id', ['value' => $tickets_id]);
      echo Html::submit("Import alerts", ['name' => 'import_alerts']);

      Html::closeForm();
      echo "</div>";

      // init menu in jquery dialog
      echo Html::scriptBlock("
         $(document).ready(function() {
            $('#add_alerts').dialog({
               height: 'auto',
               width: 'auto',
               modal: true,
               autoOpen: false
            });
         });
      ");
   }

   /**
    * Add the input send by self::importAlertsForm
    * @param  array $params with theses keys:
    *                       - tickets_id
    *                       - params_api
    *                       - name
    *                       - url
    * @return boolean
    */
   function importAlerts($params = []) {
      // unsanitize (we'll json_encode this key)
      $params_api = Toolbox::stripslashes_deep(
                       Toolbox::unclean_cross_side_scripting_deep($params['params_api']));

      // remove empty criteria
      $params_api['criteria'] = array_filter($params_api['criteria']);

      // filter input
      $params = ['tickets_id' => intval($params['tickets_id']),
                 'params_api' => addslashes(json_encode($params_api)),
                 'name'       => Toolbox::addslashes_deep($params['name']),
                 'url'        => filter_var($params['url'], FILTER_VALIDATE_URL),
                 ];

      return $this->add($params);
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
               `tickets_id`    INT(11) NOT NULL DEFAULT '0',
               `name`          VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               `url`           TEXT COLLATE utf8_unicode_ci,
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
