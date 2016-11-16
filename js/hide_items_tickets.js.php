<?php

include ("../../../inc/includes.php");

//change mimetype
header("Content-type: application/javascript");

if (!$plugin->isInstalled("prelude")
   || !$plugin->isActivated("prelude")) {
   exit;
}

$split_view = CommonGLPI::isLayoutWithMain()
              && !CommonGLPI::isLayoutExcludedPage()
                  ? "true"
                  : "false";
$url_base   = $CFG_GLPI['url_base'];

$JS = <<<JAVASCRIPT
$(function() {
   var current_page = document.location.href
                        .replace('$url_base', '')
                        .replace(document.location.search, '');

   // remove item tab in ticket form
   if (current_page == 'front/ticket.form.php') {
      var item_tab = $('li[role=tab]:has(a[href*=\\\\=Item_Ticket\\\\$1])');
      item_tab.remove();
      $('.ui-tabs').tabs( "refresh" );

      // for split view, reinit srolltabs lib
      if ($split_view) {
         $('.ui-tabs').scrollabletabs();
      }
   }
});
JAVASCRIPT;
echo $JS;
