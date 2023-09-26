<?php

/**
 * Basic item Model
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

namespace MemberData\Models;

class EVA extends Base
{
    public $table = "memberdata_eva";
    public $fields = ["id", "member_id", "attribute", "value", "modifier", "modified"];
    public $pk = "id";

    public function addFilter(QueryBuilder $qb, $filter)
    {
        if (!empty($filter)) {
            if (!isset($filter['trashed'])) {
                $qb->where('softdeleted', null);
            }
        }
    }

    public function pageOnAttribute($name, $offset, $pagesize, $sortingOrder = 'asc')
    {
        if (!in_array($sortingOrder, ['asc', 'desc'])) {
            $sortingOrder = 'asc';
        }
        return $this->select('member_id')
            ->where('attribute', $name)
            ->orderBy('value ' . $sortingOrder)
            ->offset($offset)->pagesize($pagesize);
    }

    public function validateField(string $rules, string $label)
    {
        $validator = new Validator($this);

        $rules = ["rules" => $rules, "label" => $label];
        if (!$validator->validateField('value', $rules, $this->value)) {
            return $validator->errors;
        }
        return null;
    }

    public function save()
    {
        $user = wp_get_current_user();
        if ($user !== null) {
            $this->modifier = $user->ID;
        }
        else  {
            $this->modifier = -1;
        }
        return parent::save();
    }
}
