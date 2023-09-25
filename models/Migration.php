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

class Migration extends Base
{
    public $table = "memberdata_migration";
    public $pk = "id";
    public $fields = array("id", "name", "status");
    public $rules = array(
        "id" => "skip",
        "name" => "skip",
        "status" => "int"
    );

    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    public function activate()
    {
        if (!$this->tableExists($this->table)) {
            $this->createTable($this->table, "( 
                `id` INT NOT NULL AUTO_INCREMENT , 
                `name` VARCHAR(255) NOT NULL , 
                `status` INT NOT NULL,
                PRIMARY KEY (`id`)) ENGINE = InnoDB; ");
        }

        // load all the migration objects from the migrations subfolder
        $objects = scandir(dirname(__FILE__) . '/migrations');
        $allmigrations = array();
        foreach ($objects as $filename) {
            $path = dirname(__FILE__) . "/migrations/" . $filename;

            if ($filename != '.' && $filename != '..' && is_file($path)) {
                $model = $this->loadClassFile($path);
                if (!empty($model)) {
                    $model->checkDb();
                    $allmigrations[$model->name] = $model;
                }
            }
        }

        foreach ($allmigrations as $model) {
            $dbmodel = $model->find();
            if (intval($dbmodel->status) == 0) {
                $retval = $this->execute($dbmodel, $model);
                if ($retval !== 1) {
                    // failure to execute a migration means we need to stop
                    error_log("breaking off migrations at " . $dbmodel->name);
                    break;
                }
            }
        }
    }

    public function uninstall()
    {
        $allmigrations = array_reverse($this->selectAll());
        foreach ($allmigrations as $model) {
            if ($model->status == '1') {
                $retval = $this->execute(new Migration($model)); // this should run the 'down' version
                if ($retval !== 0) {
                    // failure to execute a migration is no reason to stop
                    error_log("failed rolling back a migration at " . $model->name);
                }
            }
        }

        // finally, remove our own table
        global $wpdb;
        $tablename = $wpdb->base_prefix . $this->table;
        $sql = "DROP TABLE `$tablename`;";
        $wpdb->query($sql);
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

    public function execute($data, $model = null)
    {
        $retval = -1;
        ob_start();
        try {
            if (empty($model)) {
                $model = $this->loadClassFile(dirname(__FILE__) . "/migrations/" . $data->name . '.php');
            }
            if (!empty($model)) {
                if (intval($data->status) == 0) {
                    if ($model->up()) {
                        $data->status = 1;
                        $data->save();
                        $retval = 1;
                    }
                } else {
                    if ($model->down()) {
                        $data->status = 0;
                        $data->save();
                        $retval = 0;
                    }
                }
            }
        }
        catch (Exception $e) {
            error_log("caught exception on migration: " . $e->getMessage());
        }
        ob_end_clean();
        return $retval;
    }

    public function tableName($name)
    {
        global $wpdb;
        return $wpdb->base_prefix . $name;
    }

    public function tableExists($tablename)
    {
        global $wpdb;
        $table_name = $this->tableName($tablename);
        $query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table_name));
        return $wpdb->get_var($query) == $table_name;
    }

    public function columnExists($tablename, $columnname)
    {
        global $wpdb;
        $query = $wpdb->prepare('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = %s AND column_name = %s', $wpdb->esc_like($this->tableName($tablename)), $wpdb->esc_like($columnname));
        return $wpdb->get_var($query) == $columnname;
    }

    public function createTable($tablename, $content)
    {
        global $wpdb;
        $table_name = $this->tableName($tablename);
        return $wpdb->query("CREATE TABLE $table_name $content;");
    }

    public function dropTable($tablename)
    {
        global $wpdb;
        $table_name = $this->tableName($tablename);
        return $wpdb->query("DROP TABLE $table_name;");
    }
}
