<?php

/**
 * MemberData Base Model
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

class Base
{
    public $table = "";
    public $fields = [];
    public $internalFields = [];
    public $rules = [];
    public $pk = "";
    public $last_id = -1;

    // validation and saving
    public $errors = null;

    private $_state = "new";
    private $_ori_fields = array();

    public function __construct($id = null)
    {
        $this->_state = "new";
        if (!empty($id)) {
            if (is_array($id) || is_object($id)) {
                $this->read($id);
            }
            else {
                $this->setKey($id);
            }
        }
    }

    public function tableName($name = null)
    {
        global $wpdb;
        return $wpdb->base_prefix . ($name === null ? $this->table : $name);
    }

    public function getKey()
    {
        return !isset($this->{$this->pk}) ? -1 : $this->{$this->pk};
    }

    public function setKey($id = null)
    {
        if ($id === null) {
            $id = 0;
        }
        elseif ($id <= 0) {
            $id = 0;
        }
        $this->{$this->pk} = $id;
        $this->_state = $id <= 0 ? "new" : "pending";
    }

    public function get($id)
    {
        $obj = new static($id);
        $obj->load();
        $pk = $obj->pk;
        if (empty($obj->$pk)) {
            return null;
        }
        return $obj;
    }

    public function isNew()
    {
        return $this->_state == 'new' || $this->getKey() <= 0;
    }

    public function load()
    {
        if ($this->_state == "loaded" || $this->_state == "new") {
            return;
        }

        global $wpdb;
        $pkval = $this->{$this->pk};
        $sql = "select * from " . $wpdb->base_prefix . $this->table . " where " . $this->pk . "=%d";
        $sql = $wpdb->prepare($sql, array($pkval));
        $results = $wpdb->get_results($sql);

        if (empty($results) || sizeof($results) != 1) {
            $this->setKey(0);
            $this->_state = "new";
        }
        else {
            $this->read($results[0]);
        }
    }

    private function read($values)
    {
        $values = (array)$values;
        $this->_state = "reading";
        foreach ($this->fields as $fld) {
            if (isset($values[$fld])) {
                $this->{$fld} = $values[$fld];
                $this->_ori_fields[$fld] = $values[$fld];
            }
        }
        $this->_state = "loaded";
        if ($this->isNew()) {
            $this->_state = "new";
            $this->_ori_fields = array();
        }
    }

    public function export($result = null)
    {
        if (empty($result)) {
            $result = $this;
            $this->load();
        }
        $result = (array) $result;
        $retval = array();

        foreach ($this->fields as $fld) {
            if (isset($result[$fld]) && !in_array($fld, $this->internalFields)) {
                $retval[$fld] = $result[$fld];
            }
        }
        return $retval;
    }

    public function save()
    {
        $fieldstosave = array();
        foreach ($this->fields as $f) {
            if ($this->differs($f)) {
                $fieldstosave[$f] = $this->$f;
            }
        }
        if (empty($fieldstosave)) {
            error_log("no fields to save");
        }
        else {
            global $wpdb;
            if ($this->isNew()) {
                $wpdb->insert($wpdb->base_prefix . $this->table, $fieldstosave);
                $this->{$this->pk} = $wpdb->insert_id;
            }
            else {
                $wpdb->update($wpdb->base_prefix . $this->table, $fieldstosave, array($this->pk => $this->{$this->pk}));
            }
        }
        // save attached objects
        $this->postSave();

        foreach ($this->fields as $field) {
            $this->_ori_fields[$field] = $this->$field;
        }

        return true;
    }

    public function postSave()
    {
        return true;
    }

    public function identical($other)
    {
        // if id's match, we're identical
        if (!$this->isNew() && $this->getKey() == $other->getKey()) {
            return true;
        }

        // else, compare all fields
        foreach ($this->fields as $field) {
            $v1 = $this->{$field};
            $v2 = $other->{$field};

            if (is_bool($v1)) {
                if (!is_bool($v2) || ($v1 !== $v2)) {
                    return false;
                }
            }
            elseif (is_numeric($v1)) {
                $v1 = floatval($v1);
                $v2 = floatval($v2);
                if (abs($v1 - $v2) > 0.000000001) {
                    return false;
                }
            }
            elseif (strcmp($v1, $v2)) {
                return false;
            }
        }
        return true;
    }

    private function differs($field)
    {
        if (!property_exists($this, $field)) {
            return false; // unset fields are never different
        }
        if ($field === $this->pk && !$this->isNew()) {
            return false; // cannot reset the PK
        }
        if (!isset($this->_ori_fields[$field])) {
            return true; // no original found, so always different
        }

        $value = $this->$field;
        $original = $this->_ori_fields[$field];

        if (is_bool($value)) {
            return !is_bool($original) || ($original !== $value);
        }
        if (is_numeric($value)) {
            $value = floatval($value);
            $original = floatval($original);
            return abs($value - $original) > 0.000000001;
        }
        // if we have a null-allowed field and it is filled/cleared, always differs
        if (
               ($value === null && $original !== null)
            || ($original === null && $value !== null)
        ) {
            return true;
        }
        return strcmp(strval($value), $original) != 0;
    }

    public function delete()
    {
        if (!$this->isNew()) {
            global $wpdb;
            $retval = $wpdb->delete($wpdb->base_prefix . $this->table, array($this->pk => $this->getKey()));
            return ($retval !== false || intval($retval) < 1);
        }
        return true; // deleting a new item is always succesful
    }

    public function __get($key)
    {
        if (!isset($this->$key) && $this->_state == "pending") {
            $this->load();
        }
        if (isset($this->$key)) {
            return $this->$key;
        }
        return null;
    }

    public function __set($key, $value)
    {
        if (!isset($this->$key) && $key != $this->pk && $this->_state == "pending") {
            $this->load();
        }
        $this->$key = $value;
    }

    public function query()
    {
        $qb = new QueryBuilder($this);
        return $qb->from($this->table);
    }

    public function select($p = '*')
    {
        return $this->query()->select($p);
    }

    public function find($id = null, $clause = null)
    {
        if (is_numeric($id) && $clause === null) {
            $clause = $id;
            $id = $this->pk;
        }
        return $this->select('*')->where($id, $clause);
    }

    public function prepare($query, $values, $dofirst = false)
    {
        global $wpdb;

        if (!empty($values)) {
            // find all the variables and replace them with proper markers based on the values
            // then prepare the query
            $pattern = "/{[a-f0-9]+}/";
            $matches = array();
            $replvals = array();
            if (preg_match_all($pattern, $query, $matches)) {
                $keys = array_keys($values);
                foreach ($matches[0] as $m) {
                    $match = trim($m, '{}');
                    if (in_array($match, $keys)) {
                        $v = $values[$match];
                        if (is_float($v)) {
                            $query = str_replace($m, "%f", $query);
                            $replvals[] = $v;
                        }
                        elseif (is_int($v)) {
                            $query = str_replace($m, "%d", $query);
                            $replvals[] = $v;
                        }
                        elseif (is_null($v)) {
                            $query = str_replace($m, "NULL", $query);
                        }
                        else {
                            $query = str_replace($m, "%s", $query);
                            $replvals[] = "$v";
                        }
                    }
                }
            }

            error_log("SQL: $query");
            error_log("VAL: " . json_encode($replvals));
            $query = $wpdb->prepare($query, $replvals);
        }
        else {
            error_log("SQL: $query");
        }

        $results = $wpdb->get_results($query);
        if ($dofirst) {
            if (is_array($results) && sizeof($results) > 0) {
                return $results[0];
            }
            return array();
        }
        return $results;
    }

    public function validate()
    {
        $validator = new Validator($this);

        $this->errors = null;
        if (!$validator->validate($this)) {
            $this->errors = $validator->errors;
            return false;
        }
        return true;
    }
}
