<?php

include ("../../../inc/includes.php");

//change mimetype
header("Content-type: application/javascript");
ini_set('display_errors', 'Off');

if (!$plugin->isActivated("prelude")) {
   exit;
}

$split_view       = CommonGLPI::isLayoutWithMain()
                    && !CommonGLPI::isLayoutExcludedPage()
                        ? "true"
                        : "false";
$url_base         = $CFG_GLPI['url_base'];
$url_ticket_types = array();
foreach(Ticket::getAllTypesForHelpdesk() as $itemtype => $label) {
   $url_ticket_types[] = Toolbox::getItemTypeFormURL($itemtype, false);
}
$url_ticket_types = json_encode($url_ticket_types);

$JS = <<<JAVASCRIPT
$(function() {
   var current_page = document.location.href
                        .replace('$url_base', '')
                        .replace(document.location.search, '');

   // remove item tab in ticket form
   if (current_page == 'front/ticket.form.php') {
      remove_tab('Item_Ticket');

      // move prelude tab after validation tab
      get_tab('PluginPreludeItem_Ticket')
         .insertAfter(get_tab('TicketValidation').first());

      // refresh jquery-ui tabs
      refresh_tabs($split_view);
   }

   // for assets forms, we remove the ticket tab
   if ($url_ticket_types.indexOf('/'+current_page) !== -1) {
      remove_tab('Ticket');

      // move prelude tab after antivirus tab
      get_tab('PluginPreludeItem_Ticket')
         .insertAfter(get_tab('ComputerAntivirus').first());

      // refresh jquery-ui tabs
      refresh_tabs($split_view);
   }
});
JAVASCRIPT;
echo $JS;
