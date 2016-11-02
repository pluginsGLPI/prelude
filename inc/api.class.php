<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}


class PluginPreludeAPI extends CommonGLPI {
   static function status() {
      return true;
   }
}