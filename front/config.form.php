<?php

include ("../../../inc/includes.php");

$config = new PluginPreludeConfig();
if (isset($_POST["update"])) {
   $config->check($_POST['id'], UPDATE);
   $config->update($_POST);
   Html::back();
}

Html::redirect($CFG_GLPI["root_doc"].'/front/config.form.php?forcetab=PluginPreludeConfig$1');
