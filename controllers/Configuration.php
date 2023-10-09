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
use MemberData\Models\Sheet;

class Configuration extends Base
{
    public function index($data)
    {
        // return all configured attributes for this sheet
        $this->authenticate();
        $config = self::getConfig($data['model']['sheet'] ?? null);
        error_log("generic configuration for this sheet: " . json_encode($config));
        return [
            "types" => \apply_filters(Display::PACKAGENAME . '_attribute_types', []),
            "attributes" => $config
        ];
    }

    public function basic($data)
    {
        // return only our basic configuration, required for the settings page
        $this->authenticate();
        $config = $this->getBasicConfig();
        return [
            "types" => \apply_filters(Display::PACKAGENAME . '_attribute_types', []),
            "attributes" => $config["sheet-" . ($data['model']['sheet'] ?? null)] ?? []
        ];
    }

    public function save($data)
    {
        $this->authenticate();
        $config = $this->getBasicConfig();

        $sheet = null;
        if (isset($data['model'])) {
            $configSheet = $data['model']['settings'] ?? [];
            $sheet = new Sheet($data['model']['sheet'] ?? null);
        }
        else {
            $configSheet = [];
            $sheetId = $data['model']['sheet'] ?? null;
        }
        $configSheet = $this->sanitizeConfiguration($configSheet);
        if (!empty($sheet) && !$sheet->isNew()) {
            $config['sheet-' . $sheet->getKey()] = $configSheet;
        }

        update_option(Display::PACKAGENAME . '_configuration', json_encode($config));
        return true;
    }

    private function getBasicConfig()
    {
        // only return our own configuration, not that of plugins
        $config = json_decode(get_option(Display::PACKAGENAME . "_configuration"), true);
        if (empty($config)) {
            $config = [];
            add_option(Display::PACKAGENAME . '_configuration', json_encode($config));
        }
        return $config;
    }

    private function sanitizeConfiguration($config)
    {
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
