<?php
include ('../../../inc/includes.php');

Session ::checkLoginUser();

$item = new PluginPreludeTicket();

if (isset($_REQUEST["delete_link"])) {
   $item->delete($_REQUEST);
   Html::back();

}
Html::displayErrorAndDie("lost");
