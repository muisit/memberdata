<?php

/**
 * MemberData Configuration Controller
 * 
 * @package             memberdata
 * @author              Michiel Uitdehaag
 * @copyright           2020 Michiel Uitdehaag for muis IT
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

namespace MemberData\Controllers;

use MemberData\Lib\Display;

class Configuration extends Base
{
    public function index($data)
    {
        $this->authenticate();
        $config = $this->getConfig();
        return [
            "types" => \apply_filters(Display::PACKAGENAME . '_attribute_types', []),
            "attributes" => $config
        ];
    }

    public function save($data)
    {
        error_log("saving data " . json_encode($data));
        $this->authenticate();
        $config = $this->getConfig();

        if (isset($data['model'])) {
            $config = $data['model'];
        }
        else {
            $config = [];
        }
        $config = $this->sanitizeConfiguration($config);

        update_option(Display::PACKAGENAME . '_configuration', json_encode($config));
        return true;
    }

    private function getConfig()
    {
        $config = json_decode(get_option(Display::PACKAGENAME . "_configuration"), true);
        if (empty($config)) {
            $config = [];
            add_option(Display::PACKAGENAME . '_configuration', json_encode($config));
        }
        return $config;
    }

    private function sanitizeConfiguration($config)
    {
        error_log("sanitizing configuration");
        $types = \apply_filters(Display::PACKAGENAME . '_attribute_types', []);

        $retval = [];
        foreach ($config as $attribute) {
            $a = [];
            if (isset($attribute['name']) && isset($attribute['type']) && in_array($attribute["type"], array_keys($types))) {
                $a["type"] = $attribute["type"];
                $a["name"] = $this->sanitizeName(isset($attribute["name"]) ? $attribute["name"] : $attribute["type"], $attribute["type"]);
                $a["rules"] = $attribute["rules"];
                if (isset($attribute["options"])) {
                    $a["options"] = $attribute["options"];
                }
                if (isset($attribute["filter"])) {
                    $a["filter"] = $attribute["filter"];
                }
                $retval[] = $a;
            }
        }
        return $retval;
    }

    private function sanitizeName($value, $fallback)
    {
        if (!mb_check_encoding($value, 'utf-8')) {
            return $fallback;
        }
        // trim
        $value = preg_replace("/(^\s+)|(\s+$)/u", "", $value);
        // replace unwanted characters (allow dash, underscore and alpha-numeric)
        $value = preg_replace("/[^-_\p{L}\p{N}]/us", "", $value);
        return $value;
    }
}
