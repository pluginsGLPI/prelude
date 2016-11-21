<?php

include ("../../../inc/includes.php");

$config = new PluginPreludeConfig();

if (isset($_REQUEST["connect_api"])) {
   PluginPreludeAPI::connect($_REQUEST);

} else if (isset($_REQUEST["delete_token"])) {
   PluginPreludeAPI::deleteToken();
   Html::back();

}

Html::redirect($CFG_GLPI["root_doc"].'/front/config.form.php?forcetab=PluginPreludeConfig$1');
