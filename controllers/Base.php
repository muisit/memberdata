<?php

/**
 * Wp-ELO Base Controller
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

namespace MemberData\Controllers;

use MemberData\Lib\Display;

class Base
{
    protected $method = 'GET';
    protected $nonce = '';
    protected $data = [];

    public function dispatch(string $method, string $action, array $data)
    {
        $this->method = $method;
        $this->nonce = isset($data['nonce']) ? $data['nonce'] : '';
        $this->data = $data;
        if (!method_exists($this, $action)) {
            $action = 'index';
        }
        $reflection = new \ReflectionMethod($this, $action);
        if ($reflection->isPublic()) {
            return $this->{$action}($data);
        }
        die(403);
        return ['error' => true];
    }

    public static function createNonceText()
    {
        $user = wp_get_current_user();
        if (!empty($user)) {
            return Display::PACKAGENAME . $user->ID;
        }
        return Display::PACKAGENAME . "0";
    }

    protected function fromGet($var, $def = null)
    {
        if (isset($_GET[$var])) {
            return $_GET[$var];
        }
        return $def;
    }

    protected function fromPost($var, $def = null)
    {
        if (isset($_POST[$var])) {
            return $_POST[$var];
        }
        return $def;
    }

    protected function checkNonce()
    {
        $result = wp_verify_nonce($this->nonce, self::createNonceText());
        if (!($result === 1 || $result === 2)) {
            memberdata_log('die because nonce does not match');
            die(403);
        }
    }

    protected function authenticate()
    {
        $this->checkNonce();
        if (!current_user_can('manage_' . Display::PACKAGENAME)) {
            memberdata_log("unauthenticated");
            die(403);
        }
    }

    protected function canAuthenticate()
    {
        return current_user_can('manage_' . Display::PACKAGENAME);
    }

    protected function outputCSV($name, $fileData)
    {
        header('Content-Disposition: attachment; filename="' . $name . '";');
        header('Content-Type: application/csv; charset=UTF-8');

        $f = fopen('php://output', 'w');
        foreach ($fileData as $line) {
            fputcsv($f, $line, "\t");
        }
        fpassthru($f);
        fclose($f);
        ob_flush();
        exit();
    }

    public static function getConfig($sheet)
    {
        return \apply_filters(Display::PACKAGENAME . '_configuration', ['sheet' => $sheet, 'configuration' => []])['configuration'] ?? [];
    }
}
