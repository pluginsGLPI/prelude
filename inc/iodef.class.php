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

      $iodef = self::getDefaultIodefDefinition($problem);

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

      // base paths
      $base_incident  = "Incident[0]";
      $base_contact   = $base_incident."[Contact][0]";
      $base_assesment = $base_incident."[Assessment][0]";
      $base_impact    = $base_assesment."[Impact][0]";
      $base_timpact   = $base_assesment."[TimeImpact][0]";
      $base_mimpact   = $base_assesment."[MonetaryImpact][0]";
      $base_method    = $base_incident."[Method][0]";
      $base_ref       = $base_method."[Reference][0]";

      // == Contact Information block ==
      echo "<h2>".__("Contact Information", 'prelude')."</h2>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Name")."</label>";
      $field = $base_contact."[ContactName][value]";
      echo Html::input($field, ['required' => 'required',
                                'value'    => self::getIodefValue($iodef, $field)]);
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Email")."</label>";
      $field = $base_contact."[Email][0][value]";
      echo "<input type='email' name='$field' required='required'
                   value='".self::getIodefValue($iodef, $field)."'>";
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Phone")."</label>";
      $field = $base_contact."[Telephone][0][value]";
      echo "<input type='tel' name='$field' required='required'
                   value='".self::getIodefValue($iodef, $field)."'>";
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Description")."</label>";
      $field = $base_contact."[Description][0][value]";
      echo "<textarea name='$field'>".self::getIodefValue($iodef, $field)."</textarea>";
      echo "</div>";

      echo "<div class='clearfix'></div>";

      // == Incident block ==
      echo "<h2>".__("Incident", 'prelude')."</h2>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Purpose", 'prelude')."</label>";
      $field = $base_incident."[_purpose]";
      Dropdown::showFromArray($field, [
         __("traceback", 'prelude') => 'traceback',
         __("mitigation", 'prelude')=> 'mitigation',
         __("reporting", 'prelude') => 'reporting',
         __("other", 'prelude')     => 'other',
      ], [
         'display_emptychoice' => true,
         'value'               => self::getIodefValue($iodef, $field)
      ]);
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Incident ID", 'prelude')."</label>";
      $field = $base_incident."[IncidentId][value]";
      echo Html::input($field, ['required' => 'required',
                                'value'    => self::getIodefValue($iodef, $field)]);
      echo "</div>";

      echo "<div class='iodef_field full_width'>";
      echo "<label>".__("Description")."</label>";
      $field = $base_incident."[Description][0][value]";
      echo "<textarea name='$field'>". self::getIodefValue($iodef, $field)."</textarea>";
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Start time", 'prelude')."</label>";
      $field = $base_incident."[StartTime][value]";
      Html::showDateTimeField($field, array('value' => self::getIodefValue($iodef, $field)));
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("End time", 'prelude')."</label>";
      $field = $base_incident."[EndTime][value]";
      Html::showDateTimeField($field, array('value' => self::getIodefValue($iodef, $field)));
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Detect time", 'prelude')."</label>";
      $field = $base_incident."[DetectTime][value]";
      Html::showDateTimeField($field, array('value' => self::getIodefValue($iodef, $field)));
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Report time", 'prelude')."</label>";
      $field = $base_incident."[ReportTime][value]";
      Html::showDateTimeField($field, array('value' => self::getIodefValue($iodef, $field)));
      echo "</div>";

      echo "<div class='clearfix'></div>";


      // == Assessment block ==
      echo "<h2>".__("Assessment", 'prelude')."</h2>";

      echo "<h3>".__("Impact", 'prelude')."</h3>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Severity", 'prelude')."</label>";
      $field = $base_impact."[_severity]";
      $value = self::getIodefValue($iodef, $field);
      self::displaySeverityField($field, self::getIodefValue($iodef, $field));
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Completion", 'prelude')."</label>";
      $field = $base_impact."[_completion]";
      $value = self::getIodefValue($iodef, $field);
      echo "<div class='radio_group'>";
      echo "<input type='radio' name='$field' ".($value == 'failed' ? "checked='checked'": "") ."
                   value='failed' id='completion_failed'>";
      echo "<label  class='first red' for='completion_failed'>".__("failed", 'prelude')."</label>";
      echo "<input type='radio' name='$field' ".($value == 'success' ? "checked='checked'": "") ."
                   value='success' id='completion_success'>";
      echo "<label class='last green' for='completion_success'>".__("success", 'prelude')."</label>";
      echo "</div>";
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Type", 'prelude')."</label>";
      $field = $base_impact."[_type]";
      Dropdown::showFromArray($field, [
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
         'value'               => self::getIodefValue($iodef, $field)
      ]);
      echo "</div>";

      echo "<div class='clearfix'></div>";

      echo "<h3>".__("Time impact", 'prelude')."</h3>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Severity", 'prelude')."</label>";
      $field = $base_timpact."[_severity]";
      self::displaySeverityField($field, self::getIodefValue($iodef, $field));
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Metric", 'prelude')."</label>";
      $field = $base_timpact."[_metric]";
      Dropdown::showFromArray($field, [
         __("labor", 'prelude')    => 'labor',
         __("elapsed", 'prelude')  => 'elapsed',
         __("downtime", 'prelude') => 'downtime',
      ], [
         'display_emptychoice' => true,
         'value'               => self::getIodefValue($iodef, $field)
      ]);
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Duration")."</label>";
      $field = $base_timpact."[value]";
      echo "<input class='main' type='number' name='$field'
                   value='".self::getIodefValue($iodef, $field)."'>";
      $field = $base_timpact."[_duration]";
      Dropdown::showFromArray($field, [
         __("second", 'prelude')  => 'second',
         __("minute", 'prelude')  => 'minute',
         __("hour", 'prelude')    => 'hour',
         __("day", 'prelude')     => 'day',
         __("month", 'prelude')   => 'month',
         __("quarter", 'prelude') => 'quarter',
         __("year", 'prelude')    => 'year',
      ], [
         'display_emptychoice' => true,
         'value'               => self::getIodefValue($iodef, $field)
      ]);
      echo "</div>";

      echo "<div class='clearfix'></div>";

      echo "<h3>".__("Monetary impact", 'prelude')."</h3>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Severity", 'prelude')."</label>";
      $field = $base_mimpact."[_severity]";
      self::displaySeverityField($field, self::getIodefValue($iodef, $field));
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Cost")."</label>";
      $field = $base_mimpact."[value]";
      echo "<input class='main' type='number' step='0.01'
                   name='$field' value='".self::getIodefValue($iodef, $field)."'>";
      $field = $base_mimpact."[_currency]";
      Dropdown::showFromArray($field, [
         __("euros", 'prelude')   => 'euros',
         __("dollars", 'prelude') => 'dollars',
         __("roubles", 'prelude') => 'roubles',
         __("pesos", 'prelude')   => 'pesos',
      ], [
         'value' => self::getIodefValue($iodef, $field)
      ]);
      echo "</div>";

      echo "<div class='clearfix'></div>";


      // == Method block ==
      echo "<h2>".__("Method used", 'prelude')."</h2>";

      echo "<div class='iodef_field full_width'>";
      echo "<label>".__("Description")."</label>";
      $field = $base_method."[Description][0]";
      echo "<textarea name='$field'>".self::getIodefValue($iodef, $field)."</textarea>";
      echo "</div>";

      echo "<div class='clearfix'></div>";

      echo "<h3>".__("Reference", 'prelude')."</h3>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Name")."</label>";
      $field = $base_ref."[ReferenceName]['value']";
      echo Html::input($field, ['value' => self::getIodefValue($iodef, $field)]);
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("URL")."</label>";
      $field = $base_ref."[URL]['value']";
      echo "<input type='url' name='$field' value='".self::getIodefValue($iodef, $field)."'>";
      echo "</div>";

      echo "<div class='iodef_field full_width'>";
      echo "<label>".__("Description")."</label>";
      $field = $base_ref."[Description]['value']";
      echo "<textarea name='$field'>".self::getIodefValue($iodef, $field)."</textarea>";
      echo "</div>";


      echo "<div class='clearfix'></div>";


      // == EventData block ==
      echo "<h2>".__("EventData", 'prelude')."</h2>";

      echo Html::submit(__('add'), array('name' => 'add'));
      Html::closeForm();
      echo "</div>"; // .togglable
      echo "</div>"; // #add_iodef
   }

   static function displaySeverityField($field, $value) {
      echo "<div class='radio_group'>";
      echo "<input type='radio' name='$field'
                   ".($value == 'low' ? "checked='checked'": "") ."
                   value='low' id='mseverity_low'>";
      echo "<label class='first blue' for='mseverity_low'>".__("low", 'prelude')."</label>";
      echo "<input type='radio' name='$field'
                   ".($value == 'medium' ? "checked='checked'": "") ."
                   value='medium' id='mseverity_medium'>";
      echo "<label class='orange' for='mseverity_medium'>".__("medium", 'prelude')."</label>";
      echo "<input type='radio' name='$field'
                   ".($value == 'high' ? "checked='checked'": "") ."
                   value='high' id='mseverity_high'>";
      echo "<label class='last red' for='mseverity_high'>".__("high", 'prelude')."</label>";
      echo "</div>";
   }

   static function getDefaultIodefDefinition(Problem $problem) {
      $user = new User;
      $user->getFromDB($_SESSION['glpiID']);

      $iodef = [
         'Incident'       => [[
            '_purpose'    => '',
            'IncidentId'  => [
               '_name' => '',
               'value' => ''
            ],
            'Description' => [['value' => $problem->getField('content')]],
            'StartTime'   => ['value' => ''],
            'EndTime'     => ['value' => ''],
            'DetectTime'  => ['value' => ''],
            'ReportTime'  => ['value' => $problem->getField('date_creation')],
            'Contact'     => [[
               'ContactName' => ['value'  => $_SESSION['glpirealname']." ".
                                             $_SESSION['glpifirstname']],
               'Email'       => [['value' => $user->getDefaultEmail()]],
               'Telephone'   => [['value' => $user->getField('phone')]],
               'Description' => [['value' => $user->getField('comments')]],
            ]],
            'Assessment' => [[
               'Impact' => [[
                  '_severity'   => '',
                  '_completion' => '',
                  '_type'       => '',
               ]],
               'TimeImpact' => [[
                  '_severity'   => '',
                  '_metric'     => '',
                  '_duration'   => '',
                  'value'       => '',
               ]],
               'MonetaryImpact' => [[
                   '_severity'  => '',
                   '_currency'  => '',
                   'value'      => '',
               ]]
            ]],
            'Method' => [[
               'Description' => ['value' => ''],
               'Reference' => [[
                  'ReferenceName' => ['value' => ''],
                  'URL'           => ['value' => ''],
                  'Description'   => ['value' => ''],
               ]]
            ]]
         ]]
      ];

      return $iodef;
   }

   static function getIodefValue($iodef, $path) {
      $path = rtrim($path, ']');
      $exploded_path = preg_split("/(\]\[|\[|\])/", $path);
      $temp = &$iodef;
      foreach($exploded_path as $key) {
         if (!isset($temp[$key])) {
            return '';
         }
         $temp = &$temp[$key];
      }

      return $temp;
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