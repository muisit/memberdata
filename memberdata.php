<?php

/**
 * Memberdata
 *
 * @package             memberdata
 * @author              Michiel Uitdehaag
 * @copyright           2020 - 2023 Michiel Uitdehaag for muis IT
 * @licenses            GPL-3.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:         memberdata
 * Plugin URI:          https://github.com/muisit/wp-elo
 * Description:         Basic registration of membership data without creating WP accounts
 * Version:             1.1.3
 * Requires at least:   6.1
 * Requires PHP:        8.0
 * Author:              Michiel Uitdehaag
 * Author URI:          https://www.muisit.nl
 * License:             GNU GPLv3
 * License URI:         https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:         memberdata
 * Domain Path:         /languages
 *
 * This file is part of memberdata.
 *
 * memberdata is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * memberdata is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with memberdata.  If not, see <https://www.gnu.org/licenses/>.
 */

define('MEMBERDATA_VERSION', "1.1.3");
define('MEMBERDATA_PACKAGENAME', 'memberdata');
define('MEMBERDATA_DEBUG', true);

function memberdata_autoloader($name)
{
    if (!strncmp($name, 'MemberData\\', 11)) {
        $elements = explode('\\', $name);
        // require at least MemberData\<sub>\<name>, so 3 elements
        if (sizeof($elements) > 2 && $elements[0] == "MemberData") {
            $fname = $elements[sizeof($elements) - 1] . ".php";
            $dir = implode("/", array_splice($elements, 1, -1)); // remove the base part and the file itself
            if (file_exists(__DIR__ . "/" . strtolower($dir) . "/" . $fname)) {
                include(__DIR__ . "/" . strtolower($dir) . "/" . $fname);
            }
        }
    }
}

spl_autoload_register('memberdata_autoloader');
require_once('vendor/autoload.php');

if (defined('ABSPATH')) {
    \MemberData\Lib\Activator::register(__FILE__);

    add_action('plugins_loaded', function () {
        \MemberData\Lib\Display::register(__FILE__);
        \MemberData\Lib\API::register(__FILE__);
        \MemberData\Lib\Plugin::register(__FILE__);

        do_action('memberdata_loaded');
    });
}

function memberdata_log($txt)
{
    if (MEMBERDATA_DEBUG) {
        error_log($txt);
    }
}
