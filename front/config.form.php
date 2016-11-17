<?php

include ("../../../inc/includes.php");

$config = new PluginPreludeConfig();
if (isset($_REQUEST["connect_api"])) {
   PluginPreludeAPI::connect($_REQUEST);
}

Html::redirect($CFG_GLPI["root_doc"].'/front/config.form.php?forcetab=PluginPreludeConfig$1');
