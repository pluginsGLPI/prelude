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

      $url      = Toolbox::getItemTypeFormURL(__CLASS__);
      $document = new Document;
      $iodef    = self::getDefaultIodefDefinition($problem);
      $found    = self::getForProblem($problem);

      if (count($found) <= 0) {
         _e("No iodef found for this problem", 'prelude');
      } else {

         // find last generated iodef
         $last_iodef = end($found);
         $iodef['iodef'] = array_replace_recursive($iodef['iodef'],
                                                   json_decode($last_iodef['json_iodef'], true));


         // display list of iodef
         echo "<h2>";
         _e("Previously genereted IODEF", 'prelude');
         echo "</h2>";

         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr class='tab_bg_2'>";
         echo "<th>".__("Creation date")."</th>";
         echo "<th>".__("Document")."</th>";
         echo "<th>".__("Incident ID", 'prelude')."</th>";
         echo "<th></th>";
         echo "</tr>";

         foreach ($found as $id => $current) {

            $current_iodef = json_decode($current['json_iodef'], true);

            echo "<tr class='tab_bg_1'>";
            echo "<td>".$current['date_creation']."</td>";
            echo "<td>";
            if ($current['documents_id']) {
               $document->getFromDB($current['documents_id']);
               echo $document->getDownloadLink();
            }
            echo "</td>";
            echo "<td>".$current_iodef['Incident'][0]['IncidentId']['value']."</td>";
            echo "<td>";
            echo "<a href='$url?email&id=$id'>";
            echo Html::image(PRELUDE_ROOTDOC."/pics/email.png",
                             ['class' => 'pointer',
                              'title' => __("Send IODEF By Email", 'prelude')]);
            echo "</a>";
            echo "<a href='$url?purge&id=$id'>";
            echo Html::image(PRELUDE_ROOTDOC."/pics/delete.png",
                             ['class' => 'pointer',
                              'title' => __("Delete IODEF", 'prelude')]);
            echo "</a>";
            echo "</td>";
            echo "</tr>";
         }

         echo "</table>";
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
      $base_incident  = "iodef[Incident][0]";
      $base_contact   = $base_incident  ."[Contact][0]";
      $base_assesment = $base_incident  ."[Assessment][0]";
      $base_impact    = $base_assesment ."[Impact][0]";
      $base_timpact   = $base_assesment ."[TimeImpact][0]";
      $base_mimpact   = $base_assesment ."[MonetaryImpact][0]";
      $base_method    = $base_incident  ."[Method][0]";
      $base_ref       = $base_method    ."[Reference][0]";

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

      echo "<div class='iodef_field full_width'>";
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

      echo "<div class='iodef_field'>";
      echo "<label>".__("Incident name", 'prelude')."</label>";
      $field = $base_incident."[IncidentId][_name]";
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
      $field = $base_method."[Description][value]";
      echo "<textarea name='$field'>".self::getIodefValue($iodef, $field)."</textarea>";
      echo "</div>";

      echo "<div class='clearfix'></div>";

      echo "<h3>".__("Reference", 'prelude')."</h3>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("Name")."</label>";
      $field = $base_ref."[ReferenceName][value]";
      echo Html::input($field, ['value' => self::getIodefValue($iodef, $field)]);
      echo "</div>";

      echo "<div class='iodef_field'>";
      echo "<label>".__("URL")."</label>";
      $field = $base_ref."[URL][value]";
      echo "<input type='url' name='$field'
                   pattern='^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?'
                   value='".self::getIodefValue($iodef, $field)."'>";
      echo "</div>";

      echo "<div class='iodef_field full_width'>";
      echo "<label>".__("Description")."</label>";
      $field = $base_ref."[Description][value]";
      echo "<textarea name='$field'>".self::getIodefValue($iodef, $field)."</textarea>";
      echo "</div>";

      echo "<div class='clearfix'></div>";


      // == EventData block ==
      echo "<h2>".__("EventData", 'prelude')."</h2>";

      PluginPreludeAlert::showForItem($problem, ['show_form' => false,
                                                 'toggled'   => true,]);

      echo Html::hidden(self::$items_id, array('value' => $problem->getID()));
      echo Html::submit(__('add'), array('name' => 'add'));
      Html::closeForm();
      echo "</div>"; // .togglable
      echo "</div>"; // #add_iodef
   }

   static function displaySeverityField($field, $value) {
      $rand = mt_rand();

      echo "<div class='radio_group'>";
      echo "<input type='radio' name='$field'
                   ".($value == 'low' ? "checked='checked'": "") ."
                   value='low' id='mseverity_low$rand'>";
      echo "<label class='first blue' for='mseverity_low$rand'>".__("low", 'prelude')."</label>";
      echo "<input type='radio' name='$field'
                   ".($value == 'medium' ? "checked='checked'": "") ."
                   value='medium' id='mseverity_medium$rand'>";
      echo "<label class='orange' for='mseverity_medium$rand'>".__("medium", 'prelude')."</label>";
      echo "<input type='radio' name='$field'
                   ".($value == 'high' ? "checked='checked'": "") ."
                   value='high' id='mseverity_high$rand'>";
      echo "<label class='last red' for='mseverity_high$rand'>".__("high", 'prelude')."</label>";
      echo "</div>";
   }

   function showEmailForm($params) {
      $url = Toolbox::getItemTypeFormURL(__CLASS__);

      echo "<div class='center' id='send_iodef_email'>";
      echo "<form method='POST' action='$url'>";

      echo "<h2>".__("Send an IODEF by email", 'prelude')."</h2>";

      echo "<div class='iodef_field full_width'>";
      echo "<label>".__("Document")."</label>";
      $document = new Document;
      $document->getFromDB($this->getField('documents_id'));
      echo $document->getDownloadLink();
      echo "</div>";

      echo "<div class='iodef_field full_width'>";
      echo "<label>".__("To", 'prelude')."</label>";
      echo Html::input('to', ['required' => 'required']);
      echo "</div>";

      echo "<div class='iodef_field full_width'>";
      echo "<label>".__("Cc", 'prelude')."</label>";
      echo Html::input('cc');
      echo "</div>";

      echo "<div class='iodef_field full_width'>";
      echo "<label>".__("Subject", 'prelude')."</label>";
      echo Html::input('subject');
      echo "</div>";

      echo "<div class='iodef_field full_width'>";
      echo "<label>".__("Content", 'prelude')."</label>";
      echo "<textarea name='content'></textarea>";
      echo "</div>";

      echo Html::hidden('id', ['value' => $this->getID()]);
      echo Html::submit(__("Send email", 'prelude'), array('name' => 'send_email'));
      echo "</form>";
      echo "</div>";
   }

   function sendEmail($params) {
      $mail = new GLPIMailer;
      $user = new User;
      $doc  = new Document;

      $user->getFromDB($_SESSION['glpiID']);
      $doc->getFromDB($this->getField('documents_id'));

      $mail->setFrom($user->getDefaultEmail());
      $mail->addAddress(filter_var($params['to'], FILTER_SANITIZE_EMAIL));
      $mail->addCC(filter_var($params['cc'], FILTER_SANITIZE_EMAIL));

      $mail->addAttachment(GLPI_DOC_DIR.$doc->getField('filepath'));

      $mail->Subject = filter_var($params['subject'], FILTER_SANITIZE_STRING);
      $mail->Body    = filter_var($params['content'], FILTER_SANITIZE_STRING);

      if ($mail->send()) {
         Session::addMessageAfterRedirect(__("mail sent", 'prelude'));
      } else {
         Session::addMessageAfterRedirect(__("Fail to send mail", 'prelude'));
      }
   }

   static function getDefaultIodefDefinition(Problem $problem) {
      $user = new User;
      $user->getFromDB($_SESSION['glpiID']);

      $iodef = ['iodef' => [
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
               '_role'       => 'creator',
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
      ]];

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
      return $iodef->find("`".self::$items_id."` = ".$problem->getID(), 'id ASC');
   }

   /**
    * {@inheritDoc}
    */
   function prepareInputForAdd($input) {
      global $DB;

      $input['iodef'] = Toolbox::unclean_cross_side_scripting_deep($input['iodef']);
      $input['iodef'] = stripcslashes_deep($input['iodef']);

      $input['json_iodef'] = $DB->escape(json_encode($input['iodef']));

      return $input;
   }

   /**
    * {@inheritDoc}
    */
   function post_purgeItem() {
      $document = new Document;
      $document->delete(array('id' => $this->getField('documents_id')), true);
   }

   /**
    * {@inheritDoc}
    */
   function post_addItem() {
      if ($documents_id = $this->generateDocument()) {
         $docItem = new Document_Item();
         $docItemId = $docItem->add(array(
               'documents_id' => $documents_id,
               'itemtype'     => 'Problem',
               'items_id'     => $this->getField('problems_id'),
         ));

         $this->update(['id'           => $this->getID(),
                        'documents_id' => $documents_id]);
      }
   }

   /**
    * Generate a IODEF XML document into GLPI from the submitted iodef form
    * @return integer the document id
    */
   function generateDocument() {
      if ($this->isNewItem()) {
         return false;
      }

      // retrieve problem
      $problem = new Problem;
      $problem->getFromDB($this->getField('problems_id'));

      // construct shortcut
      $iodef       = array_replace_recursive(self::getDefaultIodefDefinition($problem)['iodef'],
                                             $this->input['iodef']);
      $_incident   = $iodef      ['Incident'][0];
      $_incidentid = $_incident  ['IncidentId'];
      $_contact    = $_incident  ['Contact'][0];
      $_assesment  = $_incident  ['Assessment'][0];
      $_impact     = $_assesment ['Impact'][0];
      $_timpact    = $_assesment ['TimeImpact'][0];
      $_mimpact    = $_assesment ['MonetaryImpact'][0];
      $_method     = $_incident  ['Method'][0];
      $_reference  = $_method    ['Reference'][0];
      $_referencen = $_reference ['ReferenceName'];

      // declare IODEF Classes
      $Document       = new Marknl\Iodef\Elements\IODEFDocument();
      $Incident       = new Marknl\Iodef\Elements\Incident();
      $IncidentID     = new Marknl\Iodef\Elements\IncidentID();
      $StartTime      = new Marknl\Iodef\Elements\StartTime();
      $EndTime        = new Marknl\Iodef\Elements\EndTime();
      $DetectTime     = new Marknl\Iodef\Elements\DetectTime();
      $ReportTime     = new Marknl\Iodef\Elements\ReportTime();
      $Contact        = new Marknl\Iodef\Elements\Contact();
      $ContactName    = new Marknl\Iodef\Elements\ContactName();
      $Email          = new Marknl\Iodef\Elements\Email();
      $Telephone      = new Marknl\Iodef\Elements\Telephone();
      $Assessment     = new Marknl\Iodef\Elements\Assessment();
      $Impact         = new Marknl\Iodef\Elements\Impact();
      $TimeImpact     = new Marknl\Iodef\Elements\TimeImpact();
      $MonetaryImpact = new Marknl\Iodef\Elements\MonetaryImpact();
      $Reference      = new Marknl\Iodef\Elements\Reference();
      $ReferenceName  = new Marknl\Iodef\Elements\ReferenceName();
      $URL            = new Marknl\Iodef\Elements\URL();
      $Method         = new Marknl\Iodef\Elements\Method();

      // fill Incident
      $Incident->setAttributes(['purpose' => $_incident['_purpose']]);

      // add IncidentID to Incident
      if (isset($_incidentid['_name'])) {
         $IncidentID->setAttributes(['name' => $_incidentid['_name']]);
      }
      $IncidentID->value($_incidentid['value']);
      $Incident->addChild($IncidentID);

      // add *Times to Incident
      if (!empty($_incident['StartTime']['value'])) {
         $StartTime->value(date('c', strtotime($_incident['StartTime']['value'])));
         $Incident->addChild($StartTime);
      }
      if (!empty($_incident['EndTime']['value'])) {
         $EndTime->value(date('c', strtotime($_incident['EndTime']['value'])));
         $Incident->addChild($EndTime);
      }
      if (!empty($_incident['DetectTime']['value'])) {
         $DetectTime->value(date('c', strtotime($_incident['DetectTime']['value'])));
         $Incident->addChild($DetectTime);
      }
      if (!empty($_incident['ReportTime']['value'])) {
         $ReportTime->value(date('c', strtotime($_incident['ReportTime']['value'])));
         $Incident->addChild($ReportTime);
      }

      // add Description to Incident
      if (!empty($_incident['Description'][0]['value'])) {
         $Description = new Marknl\Iodef\Elements\Description();
         $Description->value($_incident['Description'][0]['value']);
         $Incident->addChild($Description);
      }

      // add Contact to Incident
      $Contact->setAttributes(['type' => 'person']);
      $Contact->setAttributes(['role' => $_contact['_role']]);
      $ContactName->value($_contact['ContactName']['value']);
      $Contact->addChild($ContactName);
      $Email->value($_contact['Email'][0]['value']);
      $Contact->addChild($Email);
      $Telephone->value($_contact['Telephone'][0]['value']);
      $Contact->addChild($Telephone);
      if (!empty($_contact['Description'][0]['value'])) {
         $Description = new Marknl\Iodef\Elements\Description();
         $Description->value($_contact['Description'][0]['value']);
         $Contact->addChild($Description);
      }
      $Incident->addChild($Contact);

      // add Assesment to Incident
      $Impact->setAttributes(['type'       => $_impact['_type'],
                              'severity'   => $_impact['_severity'],
                              'completion' => $_impact['_completion']]);
      $Assessment->addChild($Impact);
      $TimeImpact->setAttributes(['severity' => $_timpact['_severity'],
                                  'metric'   => $_timpact['_metric'],
                                  'duration' => $_timpact['_duration']]);
      $TimeImpact->value($_timpact['value']);
      $Assessment->addChild($TimeImpact);
      $MonetaryImpact->setAttributes(['severity' => $_mimpact['_severity'],
                                      'currency' => $_mimpact['_currency']]);
      $MonetaryImpact->value($_mimpact['value']);
      $Assessment->addChild($MonetaryImpact);
      $Incident->addChild($Assessment);

      // Add Method to Incident
      $ReferenceName->value($_referencen['value']);
      $Reference->addChild($ReferenceName);
      $URL->value($_reference['URL']['value']);
      $Reference->addChild($URL);
      if (!empty($_reference['Description']['value'])) {
         $Description = new Marknl\Iodef\Elements\Description();
         $Description->value($_reference['Description']['value']);
         $Reference->addChild($Description);
      }
      $Method->addChild($Reference);
      if (!empty($_method['Description']['value'])) {
         $Description = new Marknl\Iodef\Elements\Description();
         $Description->value($_method['Description']['value']);
         $Method->addChild($Description);
      }
      $Incident->addChild($Method);

      // add EventData (alerts) to Incident
      $alerts_params = PluginPreludeAlert::getForItem($problem);
      foreach($alerts_params as $current_alert_param) {
         $current_alert_param = json_decode($current_alert_param['params_api'], true);
         $alerts = PluginPreludeAPIClient::getAlerts($current_alert_param, true);

         foreach($alerts as $messageid => $alert) {
            $EventData = new Marknl\Iodef\Elements\EventData();
            $Flow      = new Marknl\Iodef\Elements\Flow();
            $alert     = $alert['alert'];

            // add source addresses nodes
            if (count($alert['source'])) {
               $System = new \Marknl\Iodef\Elements\System();
               $nb_nodes = 0;
               foreach($alert['source'] as $source) {
                  $Node = new \Marknl\Iodef\Elements\Node();
                  $nb_addresses = 0;
                  foreach($source['node']['address'] as $address) {
                     if (!empty($address['address'])) {
                        $Address = new Marknl\Iodef\Elements\Address();
                        $Address->setAttributes(['category' => $address['category']]);
                        $Address->value($address['address']);
                        $Node->addChild($Address);
                        $nb_addresses++;
                     }
                  }
                  if ($nb_addresses) {
                     $System->addChild($Node);
                     $nb_nodes++;
                  }
               }
               $System->setAttributes(['category' => 'source']);
            }
            if ($nb_nodes) {
               $Flow->addChild($System);
            }


            // add target addresses nodes
            if (count($alert['target'])) {
               $System = new \Marknl\Iodef\Elements\System();
               $nb_nodes = 0;
               foreach($alert['target'] as $target) {
                  $Node = new \Marknl\Iodef\Elements\Node();
                  $nb_addresses = 0;
                  foreach($target['node']['address'] as $address) {
                     if (!empty($address['address'])) {
                        $Address = new Marknl\Iodef\Elements\Address();
                        $Address->setAttributes(['category' => $address['category']]);
                        $Address->value($address['address']);
                        $Node->addChild($Address);
                        $nb_addresses++;
                     }
                  }
                  if ($nb_addresses) {
                     $System->addChild($Node);
                     $nb_nodes++;
                  }
               }
               $System->setAttributes(['category' => 'target']);
            }
            if ($nb_nodes) {
               $Flow->addChild($System);
            }


            // add analyser nodes
            if (count($alert['analyzer'])) {
               foreach($alert['analyzer'] as $analyzer) {
                  $System = new \Marknl\Iodef\Elements\System();
                  $System->setAttributes(['category'     => 'ext-value',
                                          'ext-category' => 'analyzer']);

                  $OperatingSystem = new \Marknl\Iodef\Elements\OperatingSystem;
                  $OperatingSystem->setAttributes(['configid' => $analyzer['analyzerid'],
                                                   'version'  => $analyzer['version'],
                                                   'vendor'   => $analyzer['manufacturer'],
                                                   'family'   => $analyzer['class']]);

                  $System->addChild($OperatingSystem);

                  $Node = new \Marknl\Iodef\Elements\Node();
                  $System->addChild($Node);

                  $Flow->addChild($System);
               }
            }

            $EventData->addChild($Flow);
            $Incident->addChild($EventData);
         }
      }

      // Add Incident to Document
      $Document->addChild($Incident);

      // write iodef xml
      $writer = new Marknl\Iodef\Writer();
      $writer->write([['name'      => 'IODEF-Document',
                      'attributes' => $Document->getAttributes(),
                      'value'      => $Document,
                    ]]);
      $xml_content = $writer->outputMemory();
      $xml_name    = "iodef_".$_incidentid['value'].".xml";
      $xml_file    = GLPI_DOC_DIR."/_uploads/".$xml_name;
      $fd          = fopen($xml_file, 'a');
      fwrite ($fd, $xml_content);
      fclose($fd);

      // add xml to Glpi Documents
      $gdoc    = new Document;
      $problem = new Problem;
      $problem->getFromDB($this->getField('problems_id'));
      $input   = ['name'                  => 'iodef_'.$_incidentid['value'],
                  'upload_file'           => $xml_name,
                  'add'                   => __('Add'),
                  'entities_id'           => $problem->getEntityID(),
                  'is_recursive'          => $problem->isRecursive(),
                  'link'                  => "",
                  'documentcategories_id' => 0];
      $documents_id = $gdoc->add($input);

      return $documents_id;
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
               `json_iodef`   TEXT COLLATE utf8_unicode_ci,
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