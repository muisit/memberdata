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

class MigrationObject extends Migration
{
    public function save()
    {
        // when we save the MigrationObject, it is always new and unexecuted
        $this->setKey(0);
        $this->state = 'N';
        parent::save();

        // to actually interface with the DB object, first Find it and then
        // use that base model
    }

    public function exists()
    {
        $results = $this->find('name', $this->name)->count();
        return $results == 0;
    }

    public function checkDb()
    {
        if (!$this->exists() == 0) {
            // this migrates filename and classname to the database
            $this->save();
        }
    }

    public function find()
    {
        $res = $this->find('name', $this->name)->first();
        if (!empty($res)) {
            return new Migration($res);
        }
        return new Migration();
    }

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
}
