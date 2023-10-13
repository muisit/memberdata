<?php

/**
 * MemberData Manifest handling service
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

namespace MemberData\Lib\Services;

class ManifestService
{
    public static function scripts($page, $packagename, $entrypoint, $dir)
    {
        if (in_array($page, array("toplevel_page_" . $packagename))) {
            self::enqueueAssets($entrypoint, $packagename, $dir);
        }
    }

    public static function enqueueAssets($entryPoint, $packagename, $dir, $i18nDomain = null)
    {
        $manifest = self::readManifest($dir);
        self::enqueueAsset($entryPoint, $manifest, $packagename, realpath($dir), $i18nDomain);
    }

    private static function readManifest($dir)
    {
        if (file_exists($dir . '/manifest.json')) {
            return json_decode(file_get_contents($dir . '/manifest.json'), true);
        }
        return [];
    }

    private static function enqueueAsset($entryPoint, $manifest, $packagename, $dir, $i18nDomain = null)
    {
        if (isset($manifest[$entryPoint])) {
            if (isset($manifest[$entryPoint]['css'])) {
                foreach ($manifest[$entryPoint]['css'] as $asset) {
                    $file = realpath($dir . '/' . $asset);
                    if (file_exists($file)) {
                        $file = plugins_url('/dist/' . $asset, $dir);
                        wp_enqueue_style($packagename . '/' . basename($file), $file, []);
                    }
                }
            }
            if (isset($manifest[$entryPoint]['imports'])) {
                foreach ($manifest[$entryPoint]['imports'] as $asset) {
                    self::enqueueAsset($asset, $manifest, $packagename, $dir, $i18nDomain);
                }
            }
            if (isset($manifest[$entryPoint]['file'])) {
                $file = realpath($dir . '/' . $manifest[$entryPoint]['file']);
                if (file_exists($file)) {
                    self::registerScriptModuleFilter($packagename . '/' . basename($file));
                    $file = plugins_url('/dist/' . $manifest[$entryPoint]['file'], $dir);
                    if (!empty($i18nDomain)) {
                        wp_register_script($packagename . '/' . basename($file), $file, ['wp-i18n']);
                        wp_enqueue_script($packagename . '/' . basename($file), $file, ['wp-i18n']);
                        wp_set_script_translations($packagename . '/' . basename($file), $i18nDomain, realpath($dir . '/../languages'));
                    }
                    else {
                        wp_enqueue_script($packagename . '/' . basename($file), $file, []);
                    }
                }
            }
        }
    }

    private static function registerScriptModuleFilter($handle)
    {
        add_filter('script_loader_tag', fn (...$args) => self::setScriptTypeAttribute($handle, ...$args), 10, 3);
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
