<?php
include ('../../../inc/includes.php');

Session::checkLoginUser();

$item = new PluginPreludeIODEF();

if (isset($_REQUEST["add"])) {
   $item->add($_REQUEST);
   Html::back();

} elseif (isset($_REQUEST["purge"])) {
   $item->delete($_REQUEST, true);
   Html::back();

} elseif (isset($_REQUEST["email"])) {
   Html::header(__("Send an IODEF by email", 'prelude'));
   $item->getFromDB((int) $_REQUEST['id']);
   $item->showEmailForm($_REQUEST);
   Html::footer();
   exit;

} elseif (isset($_REQUEST["send_email"])) {
   $item->getFromDB((int) $_REQUEST['id']);
   $item->sendEmail($_REQUEST);
   $problem = new Problem;
   $problem->getFromDB($item->getField('problems_id'));
   Html::redirect($problem->getFormURL()."?id=".$problem->getId());
}

Html::displayErrorAndDie("lost");
