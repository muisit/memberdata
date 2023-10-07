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
use MemberData\Models\Sheet;
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
            "sheet" => $data['model']['sheet'] ?? 0,
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
            'filters' => $this->determineFilters($settings['sheet'])
        ];
    }

    public function export($data)
    {
        $this->authenticate();
        $settings = [
            "sheet" => $data['model']['sheet'] ?? 0,
            "filter" => $data['model']['filter'] ?? null,
            "sorter" => $data['model']['sorter'] ?? null,
            "sortDirection" => $data['model']['sortDirection'] ?? 'asc'
        ];

        $result = \apply_filters(Display::PACKAGENAME . '_find_members', $settings);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $config = $this->getConfig($settings['sheet']);
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

    private function determineFilters($sheet)
    {
        $filters = array();
        $config = $this->getConfig($sheet);
        $memberModel = new Member();
        foreach ($config as $attribute) {
            if (isset($attribute['filter']) && $attribute['filter'] == 'Y') {
                $values = \apply_filters(
                    Display::PACKAGENAME . '_values',
                    [
                        'values' => [],
                        'sheet' => intval($sheet),
                        'field' => $attribute['name']
                    ]
                );
                $filters[$attribute['name']] = $values['values'];
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

    public function saveMember($data)
    {
        $this->authenticate();
        $memberData = isset($data['model']['member']) ? $data['model']['member'] : null;
        $memberModel = new Member($memberData['id'] ?? null);
        $memberModel->load();

        $sheetId = $memberModel->sheet_id;
        $config = $this->getConfig($sheetId);

        if (!memberModel->isNew() && !empty($memberData)) {
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

            $settings = [
                'member' => $memberModel,
                'attributes' => $memberData,
                'messages' => [],
                'config' => $config
            ];
            $settings = \apply_filters(Display::PACKAGENAME . '_save_attributes', $settings);
            return ["messages" => $settings['messages']];
        }
        return false;
    }

    public function addMember($data)
    {
        $this->authenticate();
        $sheetId = $data['model']['sheet'] ?? null;
        $sheet = new Sheet($sheetId);
        if (!$sheet->isNew()) {
            $config = $this->getConfig($sheetId);

            $memberModel = new Member();
            $memberModel->sheet_id = $sheet->getKey();
            $memberModel = \apply_filters(Display::PACKAGENAME . '_save_member', $memberModel);
            return $memberModel->export();
        }
        return false;
    }
}
