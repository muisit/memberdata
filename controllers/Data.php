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
        $settings = [
            "offset" => $data['model']['offset'] ?? 0,
            "pagesize" => $data["model"]['pagesize'] ?? 25,
            "filter" => $data['model']['filter'] ?? null,
            "sorter" => $data['model']['sorter'] ?? null,
            "sortDirection" => $data['model']['sortDirection'] ?? 'asc',
            "cutoff" => $data["model"]['cutoff'] ?? 100
        ];

        $result = \apply_filters(Display::PACKAGENAME . '_find_members', $settings);

        return [
            'total' => $result['count'],
            'list' => $result['list'],
            'filters' => $this->determineFilters()
        ];
    }

    public function export($data)
    {
        $this->authenticate();
        $settings = [
            "filter" => $data['model']['filter'] ?? null,
            "sorter" => $data['model']['sorter'] ?? null,
            "sortDirection" => $data['model']['sortDirection'] ?? 'asc'
        ];

        $result = \apply_filters(Display::PACKAGENAME . '_find_members', $settings);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $config = $this->getConfig();
        $i = 1;
        $j = 1;
        foreach ($config as $attribute) {
            $sheet->setCellValueByColumnAndRow($i++, $j, $attribute['name']);
        }

        $j += 1;
        foreach ($result['list'] as $result) {
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

    private function determineFilters()
    {
        $filters = array();
        $config = $this->getConfig();
        $memberModel = new Member();
        foreach ($config as $attribute) {
            if (isset($attribute['filter']) && $attribute['filter'] == 'Y') {
                $filters[$attribute['name']] = $memberModel->distinctValues($attribute['name']);
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

        if (empty($memberData) && !empty($attribute)) {
            // save a single attribute
            $memberData = [$attribute => $value];
        }
        else if (!empty($memberData)) {
            // save a whole model of attributes
            // copy all supported attributes, drop the rest
            // do not overwrite attributes that we support but
            // have no value.
            $newData = [];
            foreach ($config as $attribute) {
                $attrname = $attribute['name'] ?? '';
                // special test on null value, allow it
                if (isset($memberData[$attrname]) || $memberData[$attrname] === null) {
                    $newData[$attrname] = $memberData[$attrname];
                }
            }
            $memberData = $newData;
        }

        if (!$memberModel->isNew() && !empty($memberData)) {
            $settings = [
                'member' => $memberModel,
                'attributes' => $memberData,
                'messages' => [],
                'config' => $config
            ];
            $settings = \apply_filters(Display::PACKAGENAME . '_save_attributes', $settings);
            return ["messages" => $settings['messages']];
        }
        elseif ($memberModel->isNew() && empty($memberData)) {
            $memberModel = \apply_filters(Display::PACKAGENAME . '_save_member', $memberModel);
            return $memberModel->export();
        }
        return false;
    }

    private function getConfig()
    {
        return \apply_filters(Display::PACKAGENAME . '_configuration', []);
    }
}
