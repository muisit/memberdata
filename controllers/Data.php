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
use MemberData\Models\QueryBuilder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Data extends Base
{
    private $joinAliases = [];

    public function index($data)
    {
        $this->authenticate();
        $offset = isset($data['model']['offset']) ? intval($data['model']['offset']) : 0;
        $pagesize = isset($data['model']['pagesize']) ? intval($data['model']['pagesize']) : 1;
        $filter = isset($data['model']['filter']) ? $data['model']['filter'] : null;
        $sorter = isset($data['model']['sorter']) ? $data['model']['sorter'] : null;
        $sortDirection = isset($data['model']['sortDirection']) ? $data['model']['sortDirection'] : null;
        $cutoff = isset($data['model']['cutoff']) ? intval($data['model']['cutoff']) : 100;

        $memberModel = new Member();
        $qb = $memberModel->select($memberModel->tableName() . '.id');
        $qb = $this->combineWithEva($qb, $filter, $sorter);
        $count = $this->addFilter($qb, $filter)->count();

        $this->joinAliases = [];
        $qb = $memberModel->select($memberModel->tableName() . '.id');
        $qb = $this->combineWithEva($qb, $filter, $sorter);
        $qb = $this->addSorter($qb, $memberModel, $sorter, $sortDirection);
        $qb = $this->addFilter($qb, $filter);

        // use cutoff to determine if we can return the whole set, or just a page
        if ($count > $cutoff && $pagesize > 0 && $offset >= 0) {
            $qb->offset($offset)->limit($pagesize);
        }

        $results = $memberModel->collectAttributes($qb->get());

        return [
            'total' => $count,
            'list' => $results,
            'filters' => $this->determineFilters()
        ];
    }

    public function export($data)
    {
        $this->authenticate();
        $filter = isset($data['model']['filter']) ? $data['model']['filter'] : null;
        $sorter = isset($data['model']['sorter']) ? $data['model']['sorter'] : null;
        $sortDirection = isset($data['model']['sortDirection']) ? $data['model']['sortDirection'] : null;

        $memberModel = new Member();
        $qb = $memberModel->select($memberModel->tableName() . '.id');
        $qb = $this->combineWithEva($qb, $filter, $sorter);
        $qb = $this->addSorter($qb, $memberModel, $sorter, $sortDirection);
        $qb = $this->addFilter($qb, $filter);

        $results = $memberModel->collectAttributes($qb->get());

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $config = $this->getConfig();
        $i = 1;
        $j = 1;
        foreach ($config as $attribute) {
            $sheet->setCellValueByColumnAndRow($i++, $j, $attribute['name']);
        }

        $j += 1;
        foreach ($results as $result) {
            $i = 1;
            $sheet->setCellValueByColumnAndRow($i++, $j, $result['id']);
            foreach ($config as $attribute) {
                $v = isset($result[$attribute['name']]) ? $result[$attribute['name']] : '';
                $sheet->setCellValueByColumnAndRow($i++, $j, $v);
            }
            $j++;
        }

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="export.xlsx"');
        $writer->save('php://output');
        exit(0);
    }

    private function addFilter(QueryBuilder $qb, array $filter)
    {
        $config = $this->getConfig();
        foreach ($config as $attribute) {
            $aname = $attribute['name'];
            if (isset($filter[$aname])) {
                $search = $filter[$aname]["search"];
                if ($search == null) {
                    $search = '';
                }
                $search = strtolower(trim($search));

                if (strlen($search) || count($filter[$aname]["values"]) > 0) {
                    $alias = $this->joinAliases[$aname];
                    $sub = $qb->sub();

                    if (strlen($search)) {
                        $sub->orWhere('LOWER(' . $alias . '.value)', 'like', '%' . $search . '%');
                    }

                    foreach ($filter[$aname]["values"] as $filtervar) {
                        if ($filtervar === null) {
                            $sub->orWhere($alias . '.value', null);
                        }
                        else {
                            $sub->orWhere($alias . '.value', $filtervar);
                        }
                    }
                    $qb->where($sub->get());
                }
            }
        }
        if (!isset($filter['withTrashed'])) {
            $qb->where('softdeleted', null);
        }
        return $qb;
    }

    private function addSorter(QueryBuilder $qb, $memberModel, $sorter, $sortDirection)
    {
        if (!empty($sorter)) {
            $dir = 'asc';
            if (in_array($sortDirection, ['asc', 'desc'])) {
                $dir = $sortDirection;
            }
            if ($sorter != 'id') {
                $qb->orderBy('eva.value IS NULL', 'asc')->orderBy('eva.value', $dir);
            }
            else {
                $qb->orderBy($memberModel->tableName() . '.id', $dir);
            }
        }
        return $qb;
    }

    private function combineWithEva(QueryBuilder $qb, $filter, $sorter)
    {
        if (!empty($sorter) && $sorter != 'id') {
            $qb->withEva($sorter, 'eva');
        }

        if (!empty($filter)) {
            $config = $this->getConfig();
            foreach ($config as $attribute) {
                $aname = $attribute['name'];
                if (isset($filter[$aname]) && count($filter[$aname])) {
                    $alias = "al" . count($this->joinAliases);
                    if ($aname == $sorter) {
                        $alias = 'eva';
                    }
                    else {
                        $qb->withEva($aname, strtolower($alias));
                    }
                    $this->joinAliases[$aname] = $alias;
                }
            }
        }
        return $qb;
    }

    private function determineFilters()
    {
        $filters = array();
        $config = $this->getConfig();
        $memberModel = new Member();
        foreach ($config as $attribute) {
            if (isset($attribute['filter']) && $attribute['filter'] == 'Y') {
                $values = $memberModel->select(['eva.value', 'count(*) as cnt'])
                    ->withEva($attribute['name'])
                    ->groupBy('eva.value')
                    ->having('cnt > 1')
                    ->orderBy('cnt', 'desc')
                    ->orderBy('eva.value')
                    ->get();
                $filters[$attribute['name']] = array_map(fn($a) => $a->value, $values);
            }
        }
        return $filters;
    }

    public function delete($data)
    {
        $this->authenticate();

        $memberId = isset($data['model']['id']) ? $data['model']['id'] : 0;
        $memberModel = new Member($memberId);
        $memberModel->load();

        if (!$memberModel->isNew()) {
            $memberModel->softDelete();
        }
        return true;
    }

    public function save($data)
    {
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

    private $_config = null;
    private function getConfig()
    {
        if (empty($this->_config)) {
            $this->_config = json_decode(get_option(Display::PACKAGENAME . "_configuration"), true);
            if (empty($this->_config)) {
                $this->_config = [];
            }
        }
        return $this->_config;
    }
}
