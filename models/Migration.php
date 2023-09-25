<?php

/**
 * MemberData Migration Model
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

namespace MemberData\Models;

class Migration
{
    private const CONFIG = "metadata_migrations";

    public $fields = array("id", "name", "status");
    public $rules = array(
        "id" => "skip",
        "name" => "skip",
        "status" => "int"
    );

    public function __construct($data = null)
    {
        foreach ($this->fields as $field) {
            $this->{$field} = isset($data[$field]) ? $data[$field] : null;
        }
    }

    private function scanAllMigrations()
    {
        // load all the migration objects from the migrations subfolder
        $objects = scandir(dirname(__FILE__) . '/migrations');
        $fileObjects = [];
        foreach ($objects as $filename) {
            $path = dirname(__FILE__) . "/migrations/" . $filename;

            if ($filename != '.' && $filename != '..' && is_file($path)) {
                $model = $this->loadClassFile($path);
                if (!empty($model)) {
                    $fileObjects[$model->name] = $model;
                }
            }
        }
        return $fileObjects;
    }

    public function activate()
    {
        // get the wordpress options field
        $migrations = json_decode(get_option(self::CONFIG));
        if (empty($migrations)) {
            $migrations = [];
            add_option(self::CONFIG, json_encode($migrations));
        }

        $fileObjects = $this->scanAllMigrations();

        foreach ($fileObjects as $name => $model) {
            $wasRun = isset($migrations[$model->name]) ? true : false;
            if (!$wasRun) {
                $retval = $this->execute($model);
                if ($retval !== 1) {
                    // failure to execute a migration means we need to stop
                    error_log("breaking off migrations at " . $model->name);
                    break;
                }
                $migrations[$model->name] = date('Y-m-d H:i:s');
            }
        }
        update_option(self::CONFIG, json_encode($migrations));
    }

    public function uninstall()
    {
        // get the wordpress options field
        $migrations = json_decode(get_option(self::CONFIG));
        if (empty($migrations)) {
            $migrations = [];
            add_option(self::CONFIG, json_encode($migrations));
        }

        $fileObjects = $this->scanAllMigrations();
        foreach ($fileObjects as $name => $date) {
            $wasRun = isset($migrations[$model->name]) ? true : false;
            if ($wasRun) {
                $retval = $this->execute($model, true);
                if ($retval !== 0) {
                    // failure to execute a migration means we need to stop
                    error_log("failed rolling back a migration at " . $model->name);
                    break;
                }
                $migrations[$model->name] = null;
                unset($migrations[$model->name]);
            }
        }
        update_option(self::CONFIG, json_encode($migrations));
    }

    private function loadClassFile($filename)
    {
        $classes = get_declared_classes();
        require_once($filename);
        $diff = array_diff(get_declared_classes(), $classes);
        $class = reset($diff);
        if (!empty($class)) {
            $model = new $class();
            $base = basename($filename, ".php");
            $model->name = $base;
            return $model;
        }
        return null;
    }

    public function execute($model, $down = false)
    {
        $retval = -1;
        ob_start();
        try {
            if ($down === false) {
                if ($model->up()) {
                    $retval = 1;
                }
            } else {
                if ($model->down()) {
                    $retval = 0;
                }
            }
        }
        catch (Exception $e) {
            error_log("caught exception on migration: " . $e->getMessage());
        }
        ob_end_clean();
        return $retval;
    }
}
