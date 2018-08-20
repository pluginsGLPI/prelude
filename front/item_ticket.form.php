<?php
include ('../../../inc/includes.php');

Session ::checkLoginUser();

$item = new PluginPreludeItem_Ticket();

if (isset($_POST["add"])) {
   if (isset($_POST['my_items']) && !empty($_POST['my_items'])) {
      list($_POST['itemtype'], $_POST['items_id']) = explode('_', $_POST['my_items']);
   }

   if (isset($_POST['add_items_id'])) {
      $_POST['items_id'] = $_POST['add_items_id'];
   }

   if (!isset($_POST['items_id']) || empty($_POST['items_id'])) {
      $message = sprintf(__('Mandatory fields are not filled. Please correct: %s'),
                         _n('Associated element', 'Associated elements', 1));
      Session::addMessageAfterRedirect($message, false, ERROR);
      Html::back();
   }

   $item->check(-1, CREATE, $_POST);

   if ($item->add($_POST)) {
      Event::log($_POST["tickets_id"], "ticket", 4, "tracking",
                  //TRANS: %s is the user login
                  sprintf(__('%s adds a link with an item'), $_SESSION["glpiname"]));
   }
   Html::back();

} else if (isset($_POST["delete"])) {
   $item_ticket = new Item_Ticket();
   $deleted = $item_ticket->deleteByCriteria(['tickets_id' => $_POST['tickets_id'],
                                              'items_id'   => $_POST['items_id'],
                                              'itemtype'   => $_POST['itemtype']]);
   Html::back();
}

Html::displayErrorAndDie("lost");
