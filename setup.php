<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://teclib.com/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of prelude.

 prelude is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 prelude is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with prelude. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

define('PLUGIN_PRELUDE_VERSION', '0.0.1');

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_prelude() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['prelude'] = true;
}


/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_prelude() {
   return [
      'name'           => 'prelude',
      'version'        => PLUGIN_PRELUDE_VERSION,
      'author'         => '<a href="http://www.teclib.com">Teclib\'</a>',
      'license'        => '',
      'homepage'       => '',
      'minGlpiVersion' => '9.1'
   ];
}

/**
 * Check pre-requisites before install
 * OPTIONNAL, but recommanded
 *
 * @return boolean
 */
function plugin_prelude_check_prerequisites() {
   // Strict version check (could be less strict, or could allow various version)
   if (version_compare(GLPI_VERSION,'9.1','lt')) {
      echo "This plugin requires GLPI >= 9.1";
      return false;
   }
   return true;
}

/**
 * Check configuration process
 *
 * @param boolean $verbose Whether to display message on failure. Defaults to false
 *
 * @return boolean
 */
function plugin_prelude_check_config($verbose=false) {
   if (true) { // Your configuration check
      return true;
   }

   if ($verbose) {
      _e('Installed / not configured', 'prelude');
   }
   return false;
}
