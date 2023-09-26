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

use MemberData\Models\Member;
use MemberData\Models\Eva;
use MemberData\Lib\Display;

class Data extends Base
{
    public function index($data)
    {
        $this->authenticate();
        $offset = isset($data['model']['offset']) ? intval($data['model']['offset']) : 0;
        $pagesize = isset($data['model']['pagesize']) ? intval($data['model']['pagesize']) : 1;
        $filter = isset($data['model']['filter']) ? $data['model']['filter'] : null;
        $sorter = isset($data['model']['sorter']) ? $data['model']['sorter'] : null;
        $sortDirection = isset($data['model']['sortdir']) ? $data['model']['sortdir'] : null;

        $memberModel = new Member();
        $qb = $memberModel->select('id')->addFilter($filter);

        if (!empty($sorter)) {
            $dir = 'asc';
            if (in_array(['asc', 'desc'], $sortDirection)) {
                $dir = $sortDirection;
            }
            $qb->withEva()->where('eva.attribute', $sorter)->orderBy($sorter, $sortDirection);
        }
        $count = (clone $qb)->count();
        $results = $qb->offset($offset)->limit($pagesize)->get();

        $results = $memberModel->collectAttributes($results);

        return [
            'total' => $count,
            'list' => $results
        ];
    }

    public function delete($data)
    {
        $this->authenticate();

        $memberId = isset($data['model']['id']) ? $data['model']['id'] : 0;
        $memberModel = new Member($memberId);
        $memberModel->load();

        error_log("deleting model $memberId");
        if (!$memberModel->isNew()) {
            $memberModel->softDelete();
        }
        return true;
    }

    public function save($data)
    {
        error_log("saving attribute and model data");
        $this->authenticate();

        $memberId = isset($data['model']['id']) ? $data['model']['id'] : 0;
        $attribute = isset($data['model']['attribute']) ? $data['model']['attribute'] : '';
        $value = isset($data['model']['value']) ? $data['model']['value'] : '';
        $memberData = isset($data['model']['member']) ? $data['model']['member'] : null;

        if (empty($memberId) && !empty($memberData) && isset($memberData['id'])) {
            $memberId = $memberData['id'];
        }
        $memberModel = new Member($memberId);
        $memberModel->load();

        $config = $this->getConfig();
        $attributesByName = [];
        foreach ($config as $attr) {
            $attributesByName[$attr['name']] = $attr;
        }

        if (empty($memberData) && !empty($attribute)) {
            error_log("saving single attribute '$attribute' with '$value'");
            $attributes = [$attribute];
            $memberData = [$attribute => $value];
        }
        else if (!empty($memberData)) {
            error_log("saving whole member model " . json_encode($memberData));
            $attributes = array_keys($attributesByName);
        }

        if (!$memberModel->isNew()) {
            error_log("membermodel is not new");
            $messages = [];
            foreach ($attributes as $attribute) {
                error_log("testing attribute $attribute");
                if (isset($attributesByName[$attribute])) {
                    error_log("in allowed list");
                    $eva = $memberModel->getEVA($attribute);
                    $eva->value = $memberData[$attribute];
                    error_log("validating field '$eva->value' vs " . $attributesByName[$attribute]["rules"]);
                    if (($msg = $eva->validateField($attributesByName[$attribute]["rules"], $attribute)) == null) {
                        $eva->save();
                    }
                    else {
                        $messages[] = $msg;
                    }
                }
            }
            return ["messages" => $messages];
        }
        elseif (empty($attributes)) {
            error_log("saving new model");
            $memberModel->setKey(0);
            $memberModel->save();
            error_log("new id is " . $memberModel->getKey());
            return $memberModel->export();
        }
        return false;
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
}
