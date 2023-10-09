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

class Member extends Base
{
    public $table = "memberdata_member";
    public $fields = ["id", "sheet_id", "modifier", "modified", "deletor", "softdeleted"];
    public $pk = "id";

    public function addFilter(QueryBuilder $qb, $filter)
    {
        if (!empty($filter)) {
            if (!isset($filter['trashed'])) {
                $qb->where($this->tableName() . '.softdeleted', null);
            }
        }
        else {
            $qb->where($this->tableName() . '.softdeleted', null);
        }
        return $qb;
    }

    public function getEVA($attribute)
    {
        $evaModel = new EVA();
        $data = $evaModel->select('*')->where('member_id', $this->getKey())->where('attribute', $attribute)->first();
        if (!empty($data)) {
            return new EVA($data);
        }
        $evaModel->member_id = $this->getKey();
        $evaModel->attribute = $attribute;
        return $evaModel;
    }

    public function withEva(QueryBuilder $builder, $attribute, $joinname = 'eva')
    {
        $eva = new EVA();
        $subquery = $builder->sub()->from($eva->table)->select(['member_id', 'value'])->where('attribute', $attribute)->get();
        return $builder->leftJoin($subquery, $joinname, $joinname . '.member_id = ' . $this->tableName() . '.id');
    }

    public function withSheet(QueryBuilder $builder)
    {
        $sheet = new Sheet();
        return $builder->leftJoin($sheet->tableName(), 'sheet', 'sheet.id = ' . $this->tableName() . '.sheet_id');
    }

    public function collectAttributes(array $results)
    {
        $resultsById = [];
        foreach ($results as $row) {
            $member = new static($row);
            $resultsById[$member->getKey()] = $member->export();
        }
        $ids = array_keys($resultsById);

        $evaModel = new EVA();
        $alldata = $evaModel->select('*')->whereIn('member_id', $ids)->get();
        foreach ($alldata as $eva) {
            if (isset($resultsById[$eva->member_id])) {
                $resultsById[$eva->member_id][$eva->attribute] = $eva->value;
            }
        }
        // this should retain the sorting order of the original results array
        return array_values($resultsById);
    }

    public function distinctValues($sheetId, $attribute, $restrictCount = false)
    {
        // select only on the valid entries
        $qb = $this->select(['eva.value', 'count(*) as cnt'])
            ->withEva($attribute)
            ->where('softdeleted', null)
            ->where('sheet_id', $sheetId)
            ->groupBy('eva.value')
            ->orderBy('cnt', 'desc')
            ->orderBy('eva.value');

        if ($restrictCount) {
            $qb->having('cnt > 1');
        }

        $values = $qb->get();
        return array_column($values, 'value');
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
        $this->modified = strftime('%F %T');
        return parent::save();
    }

    public function softDelete()
    {
        $this->softdeleted = strftime('%F %T');
        $user = wp_get_current_user();
        if ($user !== null) {
            $this->deletor = $user->ID;
        }
        else  {
            $this->deletor = -1;
        }
        return parent::save();
    }
}
