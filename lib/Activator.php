<?php

/**
 * MemberData activation routines
 *
 * @package             memberdata
 * @author              Michiel Uitdehaag
 * @copyright           2020 - 2023 Michiel Uitdehaag for muis IT
 * @licenses            GPL-3.0-or-later
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

namespace MemberData\Lib;

use MemberData\Models\Migration;

class Activator
{
    private const CONFIG = MEMBERDATA_PACKAGENAME . "_version";

    public static function register($plugin)
    {
        register_activation_hook($plugin, fn() => self::activate());
        register_deactivation_hook($plugin, fn() => self::deactivate());
        register_uninstall_hook($plugin, fn() => self::uninstall());
        add_action('upgrader_process_complete', fn() => self::upgrade, 10, 2);
        add_action('plugins_loaded', fn() => self::update());
    }

    private static function activate()
    {
        update_option(self::CONFIG, 'new');
        self::update();

        $role = get_role('administrator');
        $role->add_cap('manage_' . Display::PACKAGENAME, true);
    }

    private static function deactivate()
    {
    }

    private static function uninstall()
    {
        $model = new Migration();
        $model->uninstall();
    }

    private static function upgrade($upgrader_object, $options)
    {
        $current_plugin_path_name = plugin_basename(__FILE__);

        if ($options['action'] == 'update' && $options['type'] == 'plugin') {
            foreach ($options['plugins'] as $each_plugin) {
                if ($each_plugin == $current_plugin_path_name) {
                    update_option(self::CONFIG, 'new');
                }
            }
        }
    }

    private static function update()
    {
        if (get_option(self::CONFIG) == "new") {
            // this loads all database migrations from file and executes
            // all those that are not yet marked as migrated
            $model = new Migration();
            $model->activate();
            update_option(self::CONFIG, strftime('%F %T'));
        }
    }
}
