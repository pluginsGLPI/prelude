var refresh_tabs = function(split_view = false) {
   $('.ui-tabs').tabs('refresh');

   // for split view, reinit srolltabs lib
   if (split_view) {
      $('.ui-tabs').scrollabletabs();
   }
}

var remove_tab = function(tabname, split_view = false) {
   $('li[role=tab]:has(a[href*=\\='+tabname+'\\$1])')
      .remove();
   refresh_tabs(split_view);
}
