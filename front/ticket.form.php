<?php
include ('../../../inc/includes.php');

Session ::checkLoginUser();

$item = new PluginPreludeTicket();

if (isset($_REQUEST["delete_link"])) {
   $item->delete($_REQUEST);
   Html::back();

} else if (isset($_REQUEST["import_alerts"])) {
   $item->importAlerts($_REQUEST);
   Html::back();

}
Html::displayErrorAndDie("lost");
