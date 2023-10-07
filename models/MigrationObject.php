<?php

/**
 * MemberData Migration Object Model
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

class MigrationObject
{
    public function rawQuery($txt)
    {
        global $wpdb;
        return $wpdb->query($txt);
    }

    public function up()
    {
        error_log("abstract parent UP");
    }

    public function down()
    {
        error_log("abstract parent DOWN");
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

    public function addColumn($tablename, $columnname, $spec)
    {
        global $wpdb;
        $table_name = $this->tableName($tablename);
        return $wpdb->query("ALTER TABLE `$table_name` ADD `$columnname` $spec;");
    }

    public function dropColumn($tablename, $columnname)
    {
        global $wpdb;
        $table_name = $this->tableName($tablename);
        return $wpdb->query("ALTER TABLE `$table_name` DROP COLUMN `$columnname`;");
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
