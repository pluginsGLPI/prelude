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
      global $CFG_GLPI;

      $url = Toolbox::getItemTypeFormURL(__CLASS__);

      $found = self::getForProblem($problem);
      if (count($found) <= 0) {
         _e("No iodef found for this problem", 'prelude');
      } else {
         echo "<h2>";
         _e("Previously genereted IODEF", 'prelude');
         echo "</h2>";

         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr class='tab_bg_2'>";
         echo "<th>".__("Creation date")."</th>";
         echo "<th>".__("Document")."</th>";
         echo "<th>".__("Titre")."</th>";
         echo "<th></th>";
         echo "</tr>";

         foreach ($found as $id => $current) {

            echo "<tr class='tab_bg_1'>";
            echo "<td>".$current['creation_date']."</td>";
            echo "<td>".$current['documents_id']."</td>";
            echo "<td></td>";
            echo "<td></td>";
            echo "</tr>";
         }
      }

      echo "<div id='add_iodef'>";

      echo "<input type='checkbox' name='toggle'
                   class='toggle_prelude' id='hide_iodef_form' checked='checked' />";
      echo "<label for='hide_iodef_form'>";
      echo "<span class='vsubmit'>";
      _e("Add a new IODEF", 'prelude');
      echo "</span>";
      echo "</label>";

      echo "<div class='togglable'>";
      echo "<form class='add_iodef_form' method='POST' action='$url'>";


      // == Contact Information block ==
      echo "<h2>".__("Contact Information", 'prelude')."</h2>";
      $base_contact = "incident[0][contact][0]";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Name")."</label>";
      echo Html::input($base_contact."[contact_name]", ['required' => 'required']);
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Email")."</label>";
      echo "<input type='email' name='".$base_contact."[email][0][email]'
                   value='' required='required'>";
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Phone")."</label>";
      echo "<input type='tel' name='".$base_contact."[telephone][0][telephone]'
                   value='' required='required'>";
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Description")."</label>";
      echo "<textarea name='".$base_contact."[description][0]'></textarea>";
      echo "</div>";

      echo "<div class='clearfix'></div>";

      // == Incident block ==
      echo "<h2>".__("Incident", 'prelude')."</h2>";
      $base_incident = "incident[0]";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Purpose", 'prelude')."</label>";
      Dropdown::showFromArray($base_incident."[purpose]", [
         __("traceback", 'prelude') => 'traceback',
         __("mitigation", 'prelude')=> 'mitigation',
         __("reporting", 'prelude') => 'reporting',
         __("other", 'prelude')     => 'other',
      ], [
         'display_emptychoice' => true,
      ]);
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Incident ID", 'prelude')."</label>";
      echo Html::input($base_incident."[incident_id][incident_id]", ['required' => 'required']);
      echo "</div>";

      echo "<div class='iodef_field full_width'>";
      echo "<label>".__("Description")."</label>";
      echo "<textarea name='".$base_incident."[description][0]'></textarea>";
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Start time", 'prelude')."</label>";
      Html::showDateTimeField($base_incident."[start_time]");
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("End time", 'prelude')."</label>";
      Html::showDateTimeField($base_incident."[end_time]");
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Detect time", 'prelude')."</label>";
      Html::showDateTimeField($base_incident."[detect_time]");
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Report time", 'prelude')."</label>";
      Html::showDateTimeField($base_incident."[report_time]");
      echo "</div>";

      echo "<div class='clearfix'></div>";


      // == Assessment block ==
      echo "<h2>".__("Assessment", 'prelude')."</h2>";
      $base_assesment = "incident[0][assesment][0]";

      echo "<h3>".__("Impact", 'prelude')."</h3>";
      $base_impact = $base_assesment."[impact][0]";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Severity", 'prelude')."</label>";
      echo "<div class='radio_group'>";
      echo "<input type='radio' name='".$base_impact."[severity]'
                   value='low' id='severity_low'>";
      echo "<label class='first blue' for='severity_low'>".__("low", 'prelude')."</label>";
      echo "<input type='radio' name='".$base_impact."[severity]'
                   value='medium' id='severity_medium'>";
      echo "<label class='orange' for='severity_medium'>".__("medium", 'prelude')."</label>";
      echo "<input type='radio' name='".$base_impact."[severity]'
                   value='high' id='severity_high'>";
      echo "<label class='last red' for='severity_high'>".__("high", 'prelude')."</label>";
      echo "</div>";
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Completion", 'prelude')."</label>";
      echo "<div class='radio_group'>";
      echo "<input type='radio' name='".$base_impact."[completion]'
                   value='failed' id='completion_failed'>";
      echo "<label  class='first red' for='completion_failed'>".__("failed", 'prelude')."</label>";
      echo "<input type='radio' name='".$base_impact."[completion]'
                   value='success' id='completion_success'>";
      echo "<label class='last green' for='completion_success'>".__("success", 'prelude')."</label>";
      echo "</div>";
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Type", 'prelude')."</label>";
      Dropdown::showFromArray($base_impact."[type]", [
         __("dos", 'prelude')                => 'dos',
         __("file", 'prelude')               => 'file',
         __("info-leak", 'prelude')          => 'info-leak',
         __("misconfiguration", 'prelude')   => 'misconfiguration',
         __("policy", 'prelude')             => 'policy',
         __("recon", 'prelude')              => 'recon',
         __("social-engineering", 'prelude') => 'social-engineering',
         __("user", 'prelude')               => 'user',
         __("unknown", 'prelude')            => 'unknown',
         __("other", 'prelude')              => 'other',
      ], [
         'display_emptychoice' => true,
      ]);
      echo "</div>";

      echo "<div class='clearfix'></div>";

      echo "<h3>".__("Time impact", 'prelude')."</h3>";
      $base_timpact = $base_assesment."[time_impact][0]";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Severity", 'prelude')."</label>";
      echo "<div class='radio_group'>";
      echo "<input type='radio' name='".$base_timpact."[severity]'
                   value='low' id='tseverity_low'>";
      echo "<label class='first blue' for='tseverity_low'>".__("low", 'prelude')."</label>";
      echo "<input type='radio' name='".$base_timpact."[severity]'
                   value='medium' id='tseverity_medium'>";
      echo "<label class='orange' for='tseverity_medium'>".__("medium", 'prelude')."</label>";
      echo "<input type='radio' name='".$base_timpact."[severity]'
                   value='high' id='tseverity_high'>";
      echo "<label class='last red' for='tseverity_high'>".__("high", 'prelude')."</label>";
      echo "</div>";
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Metric", 'prelude')."</label>";
      Dropdown::showFromArray($base_timpact."[metric]", [
         __("labor", 'prelude')    => 'labor',
         __("elapsed", 'prelude')  => 'elapsed',
         __("downtime", 'prelude') => 'downtime',
      ], [
         'display_emptychoice' => true,
      ]);
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Duration")."</label>";
      echo "<input class='main' type='number' name='".$base_timpact."[timeimpact][value]' value=''>";
      Dropdown::showFromArray($base_timpact."[timeimpact][duration]", [
         __("second", 'prelude')  => 'second',
         __("minute", 'prelude')  => 'minute',
         __("hour", 'prelude')    => 'hour',
         __("day", 'prelude')     => 'day',
         __("month", 'prelude')   => 'month',
         __("quarter", 'prelude') => 'quarter',
         __("year", 'prelude')    => 'year',
      ], [
         'display_emptychoice' => true,
      ]);
      echo "</div>";

      echo "<div class='clearfix'></div>";

      echo "<h3>".__("Monetary impact", 'prelude')."</h3>";
      $base_mimpact = $base_assesment."[monetary_impact][0]";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Severity", 'prelude')."</label>";
      echo "<div class='radio_group'>";
      echo "<input type='radio' name='".$base_mimpact."[severity]'
                   value='low' id='mseverity_low'>";
      echo "<label class='first blue' for='mseverity_low'>".__("low", 'prelude')."</label>";
      echo "<input type='radio' name='".$base_mimpact."[severity]'
                   value='medium' id='mseverity_medium'>";
      echo "<label class='orange' for='mseverity_medium'>".__("medium", 'prelude')."</label>";
      echo "<input type='radio' name='".$base_mimpact."[severity]'
                   value='high' id='mseverity_high'>";
      echo "<label class='last red' for='mseverity_high'>".__("high", 'prelude')."</label>";
      echo "</div>";
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Cost")."</label>";
      echo "<input class='main' type='number' step='0.01'
                   name='".$base_mimpact."[value]' value=''>";
      Dropdown::showFromArray($base_timpact."[currency]", [
         __("euros", 'prelude')   => 'euros',
         __("dollars", 'prelude') => 'dollars',
         __("roubles", 'prelude') => 'roubles',
         __("pesos", 'prelude')   => 'pesos',
      ]);
      echo "</div>";

      echo "<div class='clearfix'></div>";


      // == Method block ==
      echo "<h2>".__("Method used", 'prelude')."</h2>";
      $base_method = "incident[0][method][0]";

      echo "<div class='iodef_field full_width'>";
      echo "<label>".__("Description")."</label>";
      echo "<textarea name='".$base_method."[description][0]'></textarea>";
      echo "</div>";

      echo "<div class='clearfix'></div>";

      echo "<h3>".__("Reference", 'prelude')."</h3>";
      $base_ref = $base_method."[reference][0]";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Name")."</label>";
      echo Html::input($base_ref."[reference_name]");
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("URL")."</label>";
      echo "<input type='url' name='".$base_ref."[url][0]' value=''>";
      echo "</div>";

      echo "<div class='iodef_field full_width'>";
      echo "<label>".__("Description")."</label>";
      echo "<textarea name='".$base_ref."[description][0]'></textarea>";
      echo "</div>";


      echo "<div class='clearfix'></div>";


      // == EventData block ==
      echo "<h2>".__("EventData", 'prelude')."</h2>";

      echo Html::submit(__('add'), array('name' => 'add'));
      Html::closeForm();
      echo "</div>"; // .togglable
      echo "</div>"; // #add_iodef
   }

   static function getDefaultIodefDefinition(Problem $problem) {
      $user = new User;
      $user->getFromDB($_SESSION['glpiID']);

      return [
         'incident'       => [[
            'purpose'     => '',
            'incident_id' => '',
            'description' => $problem->getField('content'),
            'start_time'  => '',
            'end_time'    => '',
            'detect_time' => '',
            'report_time' => '',
            'contact' => [[
               'contact_name' => $_SESSION['glpirealname']." ".$_SESSION['glpifirstname'],
               'email'        => [['email'     => $user->getDefaultEmail()]],
               'telephone'    => [['telephone' => $user->getField('phone')]],
               'description'  => [[$user->getField('comments')]],
            ]],
         ]]
      ];
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
               `id`            INT(11) NOT NULL AUTO_INCREMENT,
               `problems_id`   INT(11) NOT NULL DEFAULT '0',
               `documents_id`  INT(11) NOT NULL DEFAULT '0',
               `date_creation` datetime DEFAULT NULL,
               `json_content`  TEXT COLLATE utf8_unicode_ci,
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