<?php

/**
 * MemberData page display routines
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

use MemberData\Controllers\Base;
use MemberData\Lib\Services\ManifestService;

class Display
{
    public const PACKAGENAME = MEMBERDATA_PACKAGENAME;
    private const ADMINSCRIPT = 'src/admin.ts';

    public static function adminPage()
    {
        ManifestService::enqueueAssets(self::ADMINSCRIPT, self::PACKAGENAME, __DIR__ . '/../dist');
        $nonce = wp_create_nonce(Base::createNonceText());
        $data = [
            "nonce" => $nonce,
            "url" => admin_url('admin-ajax.php?action=' . self::PACKAGENAME),
        ];
        $obj = json_encode($data);
        $id = self::PACKAGENAME . '-admin';
        $dataName = 'data-' . self::PACKAGENAME;
        echo <<<HEREDOC
        <div id="$id" $dataName='$obj'></div>
HEREDOC;
    }

    public static function frontendPage()
    {
        $nonce = wp_create_nonce(Base::createNonceText());
        $data = [
            "nonce" => $nonce,
            "url" => admin_url('admin-ajax.php?action=' . self::PACKAGENAME),
        ];
        $obj = json_encode($data);
        $id = self::PACKAGENAME . '-fe';
        $dataName = 'data-' . self::PACKAGENAME;
        echo <<<HEREDOC
        <div id="$id" $dataName='$obj'></div>
HEREDOC;
    }

    public static function register($plugin)
    {
        //add_action('admin_enqueue_scripts', fn($page) => ManifestService::scripts($page, self::PACKAGENAME, self::ADMINSCRIPT, __DIR__ . '/../dist'));
        add_action('admin_menu', fn() => self::adminMenu());
    }

    private static function adminMenu()
    {
        add_menu_page(
            __('MemberData'),
            __('MemberData'),
            'manage_' . self::PACKAGENAME,
            self::PACKAGENAME,
            fn() => Display::adminPage(),
            'dashicons-media-spreadsheet',
            100
        );
    }
}
