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

$JS = <<<JAVASCRIPT
$(function() {
   var item_tab = $('li[role=tab]:has(a[href*=\\\\=Item_Ticket\\\\$1])');

   //$('.ui-tabs').tabs('disable', item_tab.index());
   item_tab.remove();

   $('.ui-tabs').tabs( "refresh" );
   if ($split_view) {
      $('.ui-tabs').scrollabletabs();
   }
});
JAVASCRIPT;
echo $JS;
