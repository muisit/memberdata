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

class Configuration extends Base
{
    public function index($data)
    {
        $this->authenticate();
        $eloconfig = json_decode(get_option("wp_elo_values"));
        if (empty($eloconfig)) {
            $eloconfig = (object)[
                'k_value' => 32,
                'c_value' => 400,
                'l_value' => 32,
                's_value' => 16
            ];
            add_option('wp_elo_values', json_encode($eloconfig));
        }

        $data = [
            "k_value" => $eloconfig->k_value,
            "c_value" => $eloconfig->c_value,
            "l_value" => $eloconfig->l_value,
            "s_value" => $eloconfig->s_value
        ];
        return $data;
    }

    public function save($data)
    {
        $this->authenticate();
        $eloconfig = json_decode(get_option("wp_elo_values"));
        if (empty($eloconfig)) {
            $eloconfig = (object)[
                'k_value' => 32,
                'c_value' => 400,
                'l_value' => 32,
                's_value' => 16
            ];
        }
        else {
            delete_option('wp_elo_values');
        }
        $eloconfig->k_value = intval($data["model"]["k_value"]);
        $eloconfig->c_value  = intval($data["model"]["c_value"]);
        error_log('storing option ' . json_encode($eloconfig));
        add_option('wp_elo_values', json_encode($eloconfig));
        return true;
    }
}
