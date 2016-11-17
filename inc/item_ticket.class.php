<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Item_Ticket Class
 *
 *  Relation between Tickets and Items
**/
class PluginPreludeItem_Ticket extends Item_Ticket{


   // From CommonDBRelation
   static public $itemtype_1          = 'Ticket';
   static public $items_id_1          = 'tickets_id';

   static public $itemtype_2          = 'itemtype';
   static public $items_id_2          = 'items_id';
   static public $checkItem_2_Rights  = self::HAVE_VIEW_RIGHT_ON_ITEM;


   static function getTypeName($nb=0) {
      return _n('Item (prelude)', 'Items (prelude)', $nb);
   }


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
    * Print the HTML array for Items linked to a ticket
    *
    * @param $ticket Ticket object
    *
    * @return Nothing (display)
   **/
   static function showForTicket(Ticket $ticket) {
      global $DB, $CFG_GLPI;

      $instID = $ticket->fields['id'];
      $table  = self::getTable();

      if (!$ticket->can($instID, READ)) {
         return false;
      }

      $canedit = ($ticket->canAddItem($instID)
                  && isset($_SESSION["glpiactiveprofile"])
                  && $_SESSION["glpiactiveprofile"]["interface"] == "central");
      $rand    = mt_rand();

      $query = "SELECT DISTINCT `itemtype`
                FROM `$table`
                WHERE `$table`.`tickets_id` = '$instID'
                ORDER BY `itemtype`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);


      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='ticketitem_form$rand' id='ticketitem_form$rand' method='post'
                action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Add an item')."</th></tr>";

         echo "<tr class='tab_bg_1'><td>";
         // Select hardware on creation or if have update right
         $class        = new $ticket->userlinkclass();
         $tickets_user = $class->getActors($instID);
         $dev_user_id = 0;
         if (isset($tickets_user[CommonITILActor::REQUESTER])
                 && (count($tickets_user[CommonITILActor::REQUESTER]) == 1)) {
            foreach ($tickets_user[CommonITILActor::REQUESTER] as $user_id_single) {
               $dev_user_id = $user_id_single['users_id'];
            }
         }

         if ($dev_user_id > 0) {
            echo "<label>".__("My devices")."</label><br>";
            self::dropdownMyDevices($dev_user_id, $ticket->fields["entities_id"], null, 0, array('tickets_id' => $instID));
         }

         $data =  array_keys(getAllDatasFromTable($table));
         $used = array();
         if (!empty($data)) {
            foreach ($data as $val) {
               $used[$val['itemtype']] = $val['id'];
            }
         }

         self::dropdownAllDevices("itemtype", null, 0, 1, $dev_user_id, $ticket->fields["entities_id"], array('tickets_id' => $instID));
         echo "<span id='item_ticket_selection_information'></span>";


         echo "<label>".__("Type of link", 'prelude')."</label><br>";
         PluginPreludeLinktype::dropdown();

         echo "</td><td class='center' width='30%'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "<input type='hidden' name='tickets_id' value='$instID'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $number) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = array('container' => 'mass'.__CLASS__.$rand);
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";
      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';
      if ($canedit && $number) {
         $header_top    .= "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_top    .= "</th>";
         $header_bottom .= "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_bottom .= "</th>";
      }
      $header_end .= "<th>".__('Type')."</th>";
      $header_end .= "<th>".__("Type of link", 'prelude')."</th>";
      $header_end .= "<th>".__('Entity')."</th>";
      $header_end .= "<th>".__('Name')."</th>";
      $header_end .= "<th>".__('Serial number')."</th>";
      $header_end .= "<th>".__('Inventory number')."</th>";
      if ($canedit && $number) {
         $header_end .= "<th width='10'>".__('Update the item')."</th>";
      }
      echo "<tr>";
      echo $header_begin.$header_top.$header_end;

      $totalnb = 0;
      for ($i=0 ; $i<$number ; $i++) {
         $itemtype = $DB->result($result, $i, "itemtype");
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }

         if (in_array($itemtype, $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])) {
            $itemtable = getTableForItemType($itemtype);
            $query = "SELECT `$itemtable`.*,
                             `$table`.`id` AS IDD,
                             `$table`.`plugin_prelude_linktypes_id`,
                             `glpi_entities`.`id` AS entity
                      FROM `$table`,
                           `$itemtable`";

            if ($itemtype != 'Entity') {
               $query .= " LEFT JOIN `glpi_entities`
                                 ON (`$itemtable`.`entities_id`=`glpi_entities`.`id`) ";
            }

            $query .= " WHERE `$itemtable`.`id` = `$table`.`items_id`
                              AND `$table`.`itemtype` = '$itemtype'
                              AND `$table`.`tickets_id` = '$instID'";

            if ($item->maybeTemplate()) {
               $query .= " AND `$itemtable`.`is_template` = '0'";
            }

            $query .= getEntitiesRestrictRequest(" AND", $itemtable, '', '',
                                                 $item->maybeRecursive())."
                      ORDER BY `glpi_entities`.`completename`, `$itemtable`.`name`";

            $result_linked = $DB->query($query);
            $nb            = $DB->numrows($result_linked);

            for ($prem=true ; $data=$DB->fetch_assoc($result_linked) ; $prem=false) {
               $name = $data["name"];
               if ($_SESSION["glpiis_ids_visible"]
                   || empty($data["name"])) {
                  $name = sprintf(__('%1$s (%2$s)'), $name, $data["id"]);
               }
               if($_SESSION['glpiactiveprofile']['interface'] != 'helpdesk') {
                  $link     = $itemtype::getFormURLWithID($data['id']);
                  $namelink = "<a href=\"".$link."\">".$name."</a>";
               } else {
                  $namelink = $name;
               }

               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td width='10'>";
                  Html::showMassiveActionCheckBox(__CLASS__, $data["IDD"]);
                  echo "</td>";
               }
               if ($prem) {
                  $typename = $item->getTypeName($nb);
                  echo "<td class='center top' rowspan='$nb'>".
                         (($nb > 1) ? sprintf(__('%1$s: %2$s'), $typename, $nb) : $typename)."</td>";
               }
               echo "<td class='center'>";
               echo Dropdown::getDropdownName("glpi_plugin_prelude_linktypes",
                                               $data['plugin_prelude_linktypes_id'])."</td>";
               echo "<td class='center'>";
               echo Dropdown::getDropdownName("glpi_entities", $data['entity'])."</td>";
               echo "<td class='center".
                        (isset($data['is_deleted']) && $data['is_deleted'] ? " tab_bg_2_2'" : "'");
               echo ">".$namelink."</td>";
               echo "<td class='center'>".(isset($data["serial"])? "".$data["serial"]."" :"-").
                    "</td>";
               echo "<td class='center'>".
                      (isset($data["otherserial"])? "".$data["otherserial"]."" :"-")."</td>";
               if ($canedit) {
                  echo "<td width='10'>";
                  Html::showMassiveActionCheckBox($itemtype, $data["id"]);
                  echo "</td>";
               }

               echo "</tr>";
            }
            $totalnb += $nb;
         }
      }

      if ($number) {
         echo $header_begin.$header_bottom.$header_end;
      }

      echo "</table>";
      if ($canedit && $number) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }

   static function showForAsset(CommonDBTM $item) {
      return Ticket::showListForItem($item);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($withtemplate) {
         return '';
      }
      $nb = 0;
      if ($item->getType() == 'Ticket') {
         if (($_SESSION["glpiactiveprofile"]["helpdesk_hardware"] != 0)
             && (count($_SESSION["glpiactiveprofile"]["helpdesk_item_type"]) > 0)) {
            $nb = countElementsInTable(self::getTable(),
                                       "`tickets_id` = '".$item->getID()."'");
            return self::createTabEntry(self::getTypeName($nb), $nb);
         }

      } else if (in_array($item->getType(), Ticket::getAllTypesForHelpdesk())) {
         $nb = countElementsInTable(self::getTable(),
                                    "`items_id` = '".$item->getID()."'
                                     AND `itemtype` = '".$item->getType()."'");
         return self::createTabEntry(_n("Prelude ticket", "Prelude tickets", $nb), $nb);
      }

      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      if ($item->getType() == 'Ticket') {
         self::showForTicket($item);
      } else if (in_array($item->getType(), Ticket::getAllTypesForHelpdesk())) {
         self::showForAsset($item);
      }
      return true;
   }

   function post_addItem() {
      $item_ticket = new item_Ticket;
      $fields = $this->fields;
      unset($fields['id']);
      $item_ticket->add($fields);
   }

   function post_updateItem($history=1) {
      $item_ticket = new item_Ticket;
      $old_fields  = array_merge($this->fields, $this->oldvalues);
      $found       = $item_ticket->find("`itemtype`       = '".$old_fields['itemtype']."'
                                         AND `items_id`   = '".$old_fields['items_id']."'
                                         AND `tickets_id` = '".$old_fields['tickets_id']."'");

      foreach($found as $current) {
         $fields = $item_ticket->fields;
         $fields['id'] = $current;
         $item_ticket->update($fields);
      }
   }

   function post_deleteItem() {
      $item_ticket = new item_Ticket;
      $found       = $item_ticket->find("`itemtype`       = '".$this->fields['itemtype']."'
                                         AND `items_id`   = '".$this->fields['items_id']."'
                                         AND `tickets_id` = '".$this->fields['tickets_id']."'");

      foreach($found as $current) {
         $item_ticket->delete($current);
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