<?php

/**
 * Wp-ELO Configuration Controller
 * 
 * @package             wp-elo
 * @author              Michiel Uitdehaag
 * @copyright           2020 Michiel Uitdehaag for muis IT
 * @licenses            GPL-3.0-or-later
 *
 * This file is part of wp-elo.
 *
 * wp-elo is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * wp-elo is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with wp-elo.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace WPElo\Controllers;

class Player extends Base
{
    public function index($data)
    {
        $this->authenticate();
        $player = new \WPElo\Models\Player();
        $sort = "gRni";
        if (isset($data['model']['sort'])) {
            $sort = $data['model']['sort'];
        }
        $filter = [];
        if (isset($data['model']['filter'])) {
            $sort = $data['model']['filter'];
        }

        $allplayers = $player->selectAll(0, 0, $filter, $sort);
        $data = [];
        foreach ($allplayers as $player) {
            $data[] = $player->export();
        }
        return $data;
    }

    public function save($data)
    {
        $this->authenticate();
        $model = new \WPElo\Models\Player();
        $modelData = $data['model'];
        $data = $model->select('*')->where('id', $modelData['id'] ?? 0)->first();
        if (empty($data)) {
            $data = (object) [
                "id" => 0,
                "rank" => 1000
            ];
        }

        $player = new \WPElo\Models\Player($data);
        $player->name = $modelData['name'] ?? 'New Player';
        $player->groupname = $modelData['groupname'] ?? 'Group 1';
        $player->state = $modelData['state'] ?? null;
        $player->remark = $modelData['remark'] ?? null;

        if ($player->validate()) {
            $player->save();
            return $player->export();
        }
        else {
            return [
                "error" => true,
                "messages" => $player->errors
            ];
        }
    }

    public function remove($data)
    {
        $this->authenticate();
        $model = new \WPElo\Models\Player();
        $modelData = $data['model'];
        $player = $model->select('*')->where('id', $modelData['id'] ?? 0)->get();
        if (!empty($player)) {
            $player->delete();
            return true;
        }
        return false;
    }
}
