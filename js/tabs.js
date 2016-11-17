var refresh_tabs = function(split_view = false) {
   $('div:not(.debug) > .ui-tabs').tabs('refresh');

   // for split view, reinit srolltabs lib
   if (split_view) {
      // remove parts of previous scollable tabs
      $.when($('div:not(.debug) > .ui-tabs .listTab').remove(),
             $('div:not(.debug) > .ui-tabs .stNavMain').remove())
      .done(function () {
         // reinit scrollable tabs
         $('div:not(.debug) > .ui-tabs').scrollabletabs();
      });
   }
}

var remove_tab = function(tabname) {
   get_tab(tabname)
      .remove();
}

var get_tab = function(tabname) {
   return $('div:not(.debug) > .ui-tabs li[role=tab]:has(a[href*=\\='+tabname+'\\$1])').first();
}
