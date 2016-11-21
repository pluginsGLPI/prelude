<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginPreludeConfig extends CommonDBTM {
   static $rightname         = 'config';
   static protected $notable = true;

   /**
    * {@inheritDoc}
    */
   static function getTypeName($nb=0) {
      return __('Prelude configuration', 'prelude');
   }

   /**
    * Return the current config of the plugin store in the glpi config table
    * @return array config with keys => values
    */
   static function getConfig() {
      return Config::getConfigurationValues('plugin:Prelude');
   }

   /**
    * {@inheritDoc}
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($item->getType()=='Config') {
            return self::getTypeName();
      }
      return '';
   }

   /**
    * {@inheritDoc}
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType()=='Config') {
         self::showConfigForm();
      }
      return true;
   }

   /**
    * Display Html form to configurate the plugin parameters.
    */
   static function showConfigForm() {
      global $CFG_GLPI;

      $plugin = new Plugin();
      if (!$plugin->isInstalled('prelude')) {
         echo __("Please enable the prelude plugin", 'prelude');
         return false;
      }

      $current_config = self::getConfig();

      echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL('Config')."\" method='post'>";
      echo "<input type='hidden' name='config_class' value='".__CLASS__."'>";
      echo "<input type='hidden' name='config_context' value='plugin:Prelude'>";
      echo "<div class='center' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='4'>".__("Plugin's features", 'prelude')."</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td style='width: 25%'>".__("Replace ticket's items association", 'prelude')."</td>";
      echo "<td>";
      Html::showCheckbox(array('name'    => 'replace_items_tickets',
                               'value'   => true,
                               'checked' => $current_config['replace_items_tickets']));
      echo "</td>";
      echo "<td style='width: 25%'>".__("Show prelude alerts in tickets", 'prelude')."</td>";
      echo "<td>";
      Html::showCheckbox(array('name'    => 'ticket_alerts',
                               'value'   => true,
                               'checked' => $current_config['ticket_alerts']));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='4'>".__("API Access", 'prelude')."</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__("Prelude URL", 'prelude')."</td>";
      echo "<td>";
      echo Html::input('prelude_url', array('value'       => $current_config['prelude_url'],
                                            'placeholder' => "http://path/to/prelude",
                                            'style'       => 'width: 80%'));
      if ($current_config['prelude_url']) {
         $color_png = "redbutton.png";
         $status = PluginPreludeAPI::preludeStatus();
         if ($status) {
            $color_png = "greenbutton.png";
         }
         echo "&nbsp;".Html::image($CFG_GLPI['url_base']."/pics/$color_png");

         if ($status) {
            echo Html::image(PRELUDE_ROOTDOC."/pics/link.png",
                                      array('class' => 'pointer',
                                            'title' => __("Go to prelude", 'prelude'),
                                            'url'   => $current_config['prelude_url']));
         }
      }

      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__("API Client ID", 'prelude')."</td>";
      echo "<td>";
      echo Html::input('api_client_id', array('value' => $current_config['api_client_id'],
                                              'style' => 'width: 90%'));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__("API Client Secret", 'prelude')."</td>";
      echo "<td>";
      echo Html::input('api_client_secret', array('value' => $current_config['api_client_secret'],
                                                  'style' => 'width: 90%'));
      echo "</td>";
      echo "</tr>";

      // token informations
      if ($token = PluginPreludeAPI::retrieveToken()) {
         echo "<tr class='tab_bg_1'>";
         echo "<td style='width: 15%'>".__("API Access token", 'prelude')."</td>";
         echo "<td>".$token->getToken()."</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td style='width: 15%'>".__("API Refresh token", 'prelude')."</td>";
         echo "<td>".$token->getRefreshToken();
         echo Html::image(PRELUDE_ROOTDOC."/pics/delete.png",
                          array('url' => PRELUDE_ROOTDOC."/front/config.form.php?delete_token"));
         echo "</td>";
         echo "</tr>";
      }


      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4' class='center'>";
      echo "<input type='submit' name='update' class='submit' value=\""._sx('button','Save')."\">";
      echo "<br><br><br>";
      echo "</td></tr>";

      if (!empty($current_config['prelude_url'])) {

         echo "<tr class='headerRow'>";
         echo "<th colspan='4'>".__('API Status')."</th>";
         echo "</tr>";


         foreach(PluginPreludeAPI::status() as $status_label => $status) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>$status_label</td>";
            echo "<td>";
            $color_png = "redbutton.png";
            if ($status) {
               $color_png = "greenbutton.png";
            }
            echo Html::image($CFG_GLPI['url_base']."/pics/$color_png");
            echo "</td>";
            echo "</tr>";
         }

         if (!$token
             || !$token->getRefreshToken()) {
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='4'>";
            echo "<a href='".Toolbox::getItemTypeFormURL(__CLASS__)."?connect_api' class='vsubmit'>".
                 __("Connect to Prelude API", 'prelude')."</a>";
            echo "</td>";
            echo "</tr>";
         }
      }

      echo "</table></div>";
      Html::closeForm();
   }

   /**
    * Database table installation for the item type
    *
    * @param Migration $migration
    * @return boolean True on success
    */
   static function install(Migration $migration) {
      global $CFG_GLPI;

      $current_config = self::getConfig();
      // api access
      if (!isset($current_config['prelude_url']))
         Config::setConfigurationValues('plugin:Prelude', array('prelude_url' => ''));
      if (!isset($current_config['api_client_id']))
         Config::setConfigurationValues('plugin:Prelude', array('api_client_id' => ''));
      if (!isset($current_config['api_client_secret']))
         Config::setConfigurationValues('plugin:Prelude', array('api_client_secret' => ''));
      if (!isset($current_config['api_token']))
         Config::setConfigurationValues('plugin:Prelude', array('api_token' => ''));

      // plugin features
      if (!isset($current_config['replace_items_tickets']))
         Config::setConfigurationValues('plugin:Prelude', array('replace_items_tickets' => true));
      if (!isset($current_config['ticket_alerts']))
         Config::setConfigurationValues('plugin:Prelude', array('ticket_alerts' => true));
   }

   /**
    * Database table uninstallation for the item type
    *
    * @return boolean True on success
    */
   static function uninstall() {
      global $DB;

      $config = new Config();
      $config->deleteByCriteria("`context` = 'plugin:Prelude'");

      return true;
   }
}