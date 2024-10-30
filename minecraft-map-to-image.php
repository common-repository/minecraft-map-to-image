<?php
/*
Plugin Name: Minecraft Map to Image
Version: 1.0
Plugin URI: http://minecraftgooglemaps.net/?utm_source=wp-admin&utm_medium=plugin&utm_campaign=minecraft-map-to-image
Description: Upload and save your Minecraft map (.dat file) as an image to the Media Library. 
Author: De Eindbaas
Author URI: http://eindbaas.nl/
License: GPL v3

Minecraft Map To Image
Copyright (C) 2014, Mathijs van Wingerden

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( ! defined( 'MCM2I_FILE' ) ) {
	define( 'MCM2I_FILE', __FILE__ );
}

if ( ! defined( 'MCM2I_PATH' ) ) {
	define( 'MCM2I_PATH', plugin_dir_path( MCM2I_FILE ) );
}

if ( ! defined( 'MCM2I_URL' ) ) {
	define( 'MCM2I_URL', plugin_dir_url( MCM2I_FILE ) );
}

if ( ! defined( 'MCM2I_BASENAME' ) ) {
	define( 'MCM2I_BASENAME', plugin_basename( MCM2I_FILE ) );
}

define( 'MCM2I_VERSION', '1.0' );

require_once MCM2I_PATH . '/admin/Admin.class.php';
require_once MCM2I_PATH . '/libs/McMap.class.php';
require_once MCM2I_PATH . '/libs/nbt.class.php';

$admin = new MCM2I_Admin();