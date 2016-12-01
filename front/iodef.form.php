<?php
include ('../../../inc/includes.php');

Session ::checkLoginUser();

$item = new PluginPreludeIODEF();

if (isset($_REQUEST["add"])) {
   $item->add($_REQUEST);
   Html::back();

}
Html::displayErrorAndDie("lost");
