<?php

/**
 * MemberData Plugin hooks
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

use MemberData\Lib\Services\MemberRetrieveService;
use MemberData\Models\Member;
use MemberData\Models\EVA;
use MemberData\Models\Sheet;

class Plugin
{
    private static function registerMemberCalls()
    {
        add_filter(Display::PACKAGENAME . '_find_members', fn ($settings) => MemberRetrieveService::retrieveMembers($settings), 500, 1);

        add_filter(Display::PACKAGENAME . '_values', function ($values) {
            $sheet = $values['sheet'] ?? null;
            $field = $values['field'] ?? null;
            if (!empty($field)) {
                $model = new Member();
                $values['values'] = array_merge($model->distinctValues($sheet, $field), $values['values'] ?? []);
            }
            return $values;
        }, 500, 1);

        add_filter(Display::PACKAGENAME . '_save_member', function (Member $member) {
            $member->setKey(0);
            if ($member->validate()) {
                $member->save();
            }
            return $member;
        }, 500, 1);

        add_filter(Display::PACKAGENAME . '_save_attributes', function ($settings) {
            $member = $settings['member'] ?? null;
            $attributes = $settings['attributes'] ?? [];
            $messages = $settings['messages'] ?? [];
            $config = $settings['configuration'] ?? null;
            if (empty($config)) {
                $config = \apply_filters(Display::PACKAGENAME . '_configuration', ['sheet' => $member->sheet_id, 'configuration' => []]);
            }
            // strip down to only the list of attributes
            $config = $config['configuration'] ?? [];
            $attributesByName = [];
            foreach ($config as $attr) {
                if (isset($attr['name'])) {
                    $attributesByName[$attr['name']] = $attr;
                }
            }

            if (is_object($member) && get_class($member) == Member::class && !$member->isNew()) {
                foreach ($attributes as $attribute => $value) {
                    if (isset($attributesByName[$attribute])) {
                        $eva = $member->getEVA($attribute);
                        $eva->value = $value;
                        if (($msg = $eva->validateField($attributesByName[$attribute]["rules"] ?? 'skip', $attribute)) == null) {
                            $eva->save();
                        }
                        else {
                            $messages[] = $msg;
                        }
                    }
                }
                $settings['messages'] = $messages;
            }
            return $settings;
        }, 500, 2);
    }

    private static function registerTypeCalls()
    {
        add_filter(Display::PACKAGENAME . '_attribute_types', function ($config) {
            $config["text"] = ["label" => "Text", "rules" => "", "options" => null];
            $config["int"] = ["label" => "Integer", "rules" => "min=0"];
            $config["number"] = ["label" => "Number", "rules" => "min=0"];
            $config["email"] = ["label" => "E-mail", "rules" => "email"];
            $config["money"] = ["label" => "Money", "rules" => "min=0", "options" => "text", "optdefault" => "%.2f"];
            $config["date"] = ["label" => "Date", "rules" => "date", "options" => "text", "optdefault" => 'Y-m-d'];
            $config["datetime"] = ["label" => "Date + Time", "rules" => "datetime", "options" => "text", "optdefault" => 'Y-m-d H:i:s'];
            $config["enum"] = ["label" => "Enumeration", "rules" => "enum", "options" => "text", "optdefault" => "opt1|opt2"];
            return $config;
        });

        add_filter(Display::PACKAGENAME . '_configuration', function ($configuration) {
            $config = json_decode(get_option(Display::PACKAGENAME . "_configuration"), true);
            if (empty($config)) {
                $config = [];
                add_option(Display::PACKAGENAME . '_configuration', json_encode($config));
            }
            if (isset($configuration['sheet']) && isset($config['sheet-' . $configuration['sheet']])) {
                $configuration['configuration'] = array_merge($configuration['configuration'] ?? [], $config['sheet-' . $configuration['sheet']]);
            }
            return $configuration;
        }, 500, 1);

        add_filter(Display::PACKAGENAME . '_save_configuration', function ($configuration) {
            // if there are 'originalName' entries that differ from the 'name' value, update all EVA attributes
            // to the new name
            $newConfiguration = [];
            foreach ($configuration as $sheetno => $attributes) {
                $sheet = new Sheet(substr($sheetno, 6));
                $sheet->load();
                if (!$sheet->isNew()) {
                    $newConfiguration[$sheetno] = array_map(fn ($attr) => self::updateAttributeName($attr, $sheet), $attributes);
                }
            }

            update_option(Display::PACKAGENAME . '_configuration', json_encode($newConfiguration));
            // return the original configuration to allow other plugins to detect any name changes as well
            return $configuration;
        }, 500, 1);
    }

    private static function updateAttributeName($attribute, Sheet $sheet)
    {
        if (isset($attribute['name']) && isset($attribute['originalName']) && $attribute['name'] != $attribute['originalName']) {
            $evamodel = new EVA();
            $evamodel->query()
                ->withMember('member')
                ->set('attribute', $attribute['name'])
                ->where('attribute', $attribute['originalName'])
                ->where('member.sheet_id', $sheet->getKey())
                ->update();
        }
        return array_filter((array)$attribute, fn($key) => $key != 'originalName', ARRAY_FILTER_USE_KEY);
    }

    private static function registerSheetCalls()
    {
        add_filter(Display::PACKAGENAME . '_find_sheets', function ($sheets) {
            $model = new Sheet();
            $sheets = array_merge($sheets, $model->select()->orderBy('name')->get());
            return $sheets;
        }, 500, 1);

        add_filter(Display::PACKAGENAME . '_save_sheet', function ($settings) {
            $model = new Sheet($settings['sheet']['id'] ?? 0);
            $attributes = $settings['sheet'] ?? [];
            foreach ($model->fields as $field) {
                if (in_array($field, array_keys($attributes))) {
                    $model->{$field} = $attributes[$field];
                }
            }

            if ($model->validate()) {
                $model->save();
            }
            else {
                $settings['messages'] = array_merge($settings['messages'] ?? [], $model->errors);
            }
            return $settings;
        }, 500, 1);
    }

    public static function register($plugin)
    {
        self::registerMemberCalls();
        self::registerTypeCalls();
        self::registerSheetCalls();
    }

    private function getConfig()
    {
        // only return our own configuration, not that of plugins
        $config = json_decode(get_option(Display::PACKAGENAME . "_configuration"), true);
        if (empty($config)) {
            $config = [];
        }
        return $config;
    }
}
