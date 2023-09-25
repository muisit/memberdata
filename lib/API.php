<?php

/**
 * MemberData API Interface
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

namespace MemberData\Lib;

class API
{
    private $routes = [
//        'configuration.post' => [Configuration::class, 'index'],
    ];

    public function resolve()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (empty($data) || !isset($data['path'])) {
            // see if we have the proper GET requests for a download
            if (!empty($__GET["action"]) && !empty($__GET['nonce'])) {
                $retval = $this->handleGet($__GET['action'], $__GET['nonce']);
            }
        }
        else {
            $retval = $this->handlePost($data);
        }

        if (!isset($retval["error"]) || !$retval['error']) {
            wp_send_json_success($retval);
        } else {
            wp_send_json_error($retval);
        }
        wp_die();
    }

    private function handleGet($action, $nonce)
    {
        return $this->route('GET', [$action], ['nonce' => $nonce]);
    }

    private function handlePost($data)
    {
        $path = isset($data['path']) ? $data['path'] : null;
        if (empty($path)) {
            $path = "index";
        }
        $path = explode('/', trim($path, '/'));
        if (!is_array($path) || sizeof($path) == 0) {
            $path = array("index");
        }
        return $this->route('POST', $path, $data);
    }

    private function route($method, $path, $data)
    {
        $routerpath = implode('.', $path) . '.' . strtolower($method);
        $retval = ['error' => true, 'message' => 'file not found'];
        if (isset($this->routes[$routerpath])) {
            $clsName = $this->routes[$routerpath][0];
            $controller = new $clsName();
            $retval = $controller->dispatch('POST', $this->routes[$routerpath][1], $data);

            if (is_bool($retval)) {
                $retval = ["error" => !$retval];
            }
        }
        else {
            die(403);
        }
        return $retval;
    }

    public static function register($plugin)
    {
        add_action('wp_ajax_' . Display::PACKAGENAME, fn($page) => self::ajaxHandler($page));
        add_action('wp_ajax_nopriv_' . Display::PACKAGENAME, fn($page) => self::ajaxHandler($page));
    }

    private static function ajaxHandler($page)
    {
        $dat = new API();
        $dat->resolve();
    }
}
