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

class Display
{
    public const PACKAGENAME = MEMBERDATA_PACKAGENAME;
    private const ADMINSCRIPT = 'src/admin.ts';

    public static function adminPage()
    {
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

    private static function readManifest()
    {
        if (file_exists(__DIR__ . '/../dist/manifest.json')) {
            return json_decode(file_get_contents(__DIR__ . '/../dist/manifest.json'), true);
        }
        return [];
    }

    private static function enqueueAsset($entryPoint, $manifest)
    {
        if (isset($manifest[$entryPoint])) {
            if (isset($manifest[$entryPoint]['css'])) {
                foreach ($manifest[$entryPoint]['css'] as $asset) {
                    $file = realpath(__DIR__ . '/../dist/' . $asset);
                    if (file_exists($file)) {
                        $file = plugins_url('/dist/' . $asset, __DIR__);
                        wp_enqueue_style(self::PACKAGENAME . '/' . basename($file), $file, []);
                    }
                }
            }
            if (isset($manifest[$entryPoint]['imports'])) {
                foreach ($manifest[$entryPoint]['imports'] as $asset) {
                    $this->enqueueAsset($asset, $manifest);
                }
            }
            if (isset($manifest[$entryPoint]['file'])) {
                $file = realpath(__DIR__ . '/../dist/' . $manifest[$entryPoint]['file']);
                if (file_exists($file)) {
                    $this->registerScriptModuleFilter(self::PACKAGENAME . '/' . basename($file));
                    $file = plugins_url('/dist/' . $manifest[$entryPoint]['file'], __DIR__);
                    wp_enqueue_script(self::PACKAGENAME . '/' . basename($file), $file, []);
                }
            }
        }
    }

    private static function enqueueAssets($entryPoint)
    {
        $manifest = self::readManifest();
        self::enqueueAsset($entryPoint, $manifest);
    }

    public static function scripts($page)
    {
        if (in_array($page, array("toplevel_page_" . self::PACKAGENAME))) {
            self::enqueueAssets(self::ADMINSCRIPT);
        }
    }

    private function registerScriptModuleFilter($handle)
    {
        add_filter('script_loader_tag', fn (...$args) => self::setScriptTypeAttribute($handle, ...$args), 10, 3);
    }

    public static function register($plugin)
    {
        add_action('admin_enqueue_scripts', fn($page) => self::scripts($page));
        add_action('admin_menu', fn() => self::adminMenu());
    }

    private static function adminMenu()
    {
        add_menu_page(
            __('ELO'),
            __('ELO'),
            'manage_' . self::PACKAGENAME,
            self::PACKAGENAME,
            fn() => Display::adminPage(),
            'dashicons-media-spreadsheet',
            100
        );
    }

    private static function setScriptTypeAttribute(string $target_handle, string $tag, string $handle): string
    {
        if ($target_handle !== $handle) {
            return $tag;
        }

        $attribute = 'type="module"';
        $script_type_regex = '/type=(["\'])([\w\/]+)(["\'])/';

        if (preg_match($script_type_regex, $tag)) {
            // Pre-HTML5.
            $tag = preg_replace($script_type_regex, $attribute, $tag);
        }
        else {
            $pattern = '#(<script)(.*></script>)#';
            $tag = preg_replace($pattern, sprintf('$1 %s$2', $attribute), $tag);
        }

        return $tag;
    }
}
