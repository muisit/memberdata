<?php

/**
 * MemberData Sheets Controller
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

class Sheets extends Base
{
    public function index($data)
    {
        $this->authenticate();
        $data = [
            "list" => \apply_filters(Display::PACKAGENAME . '_find_sheets', []),
            "count" => 0
        ];
        $data['count'] = count($data['list']);
        return $data;
    }

    public function save($data)
    {
        $this->authenticate();

        if (isset($data['model'])) {
            $sheet = (array)$data['model'];
        }
        $retval = \apply_filters(Display::PACKAGENAME . '_save_sheet', ['sheet' => $sheet, 'messages' => []]);
        if (count($retval['messages']) == 0) {
            return true;
        }
        return ['error' => true, 'messages' => $retval['messages']];
    }
}
