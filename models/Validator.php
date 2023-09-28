<?php

/**
 * MemberData Validator Model
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

class Validator
{
    public $model = null;
    public $errors = array();

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function validate()
    {
        $this->errors = [];
        if (empty($this->model)) {
            $this->errors[] = "No object found";
            return false;
        }

        $allgood = true;
        foreach ($this->model->rules as $field => $rules) {
            $allgood = $this->validateField($field, $rules, $this->model->{$field}) && $allgood;
        }

        if (!$allgood) {
            if (!is_array($this->errors) || !sizeof($this->errors)) {
                $this->errors = array("There were errors");
            }
            return false;
        }
        return true;
    }

    public function validateField($field, $rules, $value)
    {
        $label = $field;
        $msg = null;
        if (is_array($rules)) {
            if (isset($rules['label'])) {
                $label = $rules['label'];
            }
            if (isset($rules['message'])) {
                $msg = $rules['message'];
            }
            if (isset($rules['rules'])) {
                $rules = $rules['rules'];
            }
            else {
                if (isset($rules['label'])) {
                    unset($rules['label']);
                }
                if (isset($rules['message'])) {
                    unset($rules['message']);
                }
            }
        }
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        $allgood = true;
        $isskip = false;
        foreach ($rules as $rule) {
            $rule = $this->expandRule($rule, $label, $msg);
            if ($rule["rule"] == "skip") {
                $isskip = true;
            }
            else {
                $allgood = $this->validateRule($value, $rule) && $allgood;
            }
        }
        return $allgood;
    }

    public function expandRule($rule, $label, $msg)
    {
        $ruleelements = array("label" => $label, "message" => $msg, "parameters" => array());
        if (is_string($rule)) {
            $rule = explode('=', $rule, 2);
            $ruleelements["rule"] = $rule[0];

            if (sizeof($rule) > 1) {
                $ruleelements["parameters"] = explode(',', $rule[1]);
            }
        }
        else {
            $ruleelements = array_merge($rule, $ruleelements);
        }
        return $ruleelements;
    }

    public function validateRule(&$value, $ruleelements)
    {
        error_log('validating rule ' . json_encode($ruleelements));
        $rule = $ruleelements['rule'];
        // always pass if we have an empty value and this is not the required rule
        if ($rule != 'required' && empty($value)) {
            return true;
        }

        // if the rule is a method on the model, invoke it
        if (method_exists($this->model, $rule)) {
            return $this->model->$rule($this, $value, $ruleelements);
        }

        if (method_exists($this, $rule . 'Rule')) {
            return $this->{$rule . 'Rule'}($value, $ruleelements);
        }

        return false;
    }

    private function requiredRule(&$value, $ruleelements)
    {
        $retval = !(empty($value) && $value !== false);
        if ($retval === false) {
            $msg = isset($ruleelements['message']) ? $ruleelements['message'] : "{label} is a required field";
            $this->addError($msg, $ruleelements);
        }
        return $retval;
    }

    private function nullableRule(&$value, $ruleelements)
    {
        return true; // always true, whatever it contains
    }

    private function skipRule(&$value, $ruleelements)
    {
        return true;
    }

    private function failRule(&$value, $ruleelements)
    {
        $msg = isset($ruleelements['message']) ? $ruleelements['message'] : "{label} is an unsupported field";
        $this->addError($msg, $ruleelements);
        return false;
    }

    private function intRule(&$value, $ruleelements)
    {
        $value = intval($value);
        return true;
    }

    private function floatRule(&$value, $ruleelements)
    {
        $params = isset($ruleelements['parameters']) ? $ruleelements['parameters'] : '%f';
        $value = floatval(sprintf($format, floatval($value)));
        return true;
    }

    private function boolRule(&$value, $ruleelements)
    {
        $tst = strtolower($value);
        if ($tst == 'y' || $tst == 't' || $tst == 'yes' || $tst == 'true' || $tst == 'on') {
            $value = 'Y';
        }
        else {
            $value = 'N';
        }
        return true;
    }

    private function compareRule($value, $ruleelements, $compareFunc)
    {
        $msg = isset($ruleelements['message']) ? $ruleelements['message'] : null;
        $params = isset($ruleelements['parameters']) ? $ruleelements['parameters'] : [];
        if (count($params) == 1) {
            if (is_numeric($value)) {
                $p1 = floatval($params[0]);
                list($retval, $msg) = $compareFunc('value', floatval($value), $p1, $msg);
            }
            elseif (is_string($value)) {
                $p1 = intval($params[0]);
                list($retval, $msg) = $compareFunc('string', strlen($value), $p1, $msg);
            }
            elseif ($this->isDate($value)) {
                $p1 = date_parse($params[0]);
                $dt1 = sprintf("%04d-%02d-%02d", $p1['year'], $p1['month'], $p1['day']);
                $tm1 = strtotime(sprintf("%04d-%02d-%02d", $value['year'], $value['month'], $value['day']));
                $tm2 = strtotime($dt1);
                $p1 = $dt1;
                list($retval, $msg) = $compareFunc('date', $tm1, $tm2, $msg);
            }

            if (!$retval) {
                $ruleelements['p1'] = $p1;
                $this->addError($msg, $ruleelements);
            }
            return $retval;
        }
        return false;
    }

    private function ltRule(&$value, $ruleelements)
    {
        return $this->compareRule($value, $ruleelements, function ($type, $v, $p, $msg) {
            switch ($type) {
                case 'string':
                    $msg = empty($msg) ? "{label} should contain less than {p1} characters" : $msg;
                    return [$v < $p1, $msg];
                default:
                case 'value':
                    $msg = empty($msg) ? "{label} should be less than {p1}" : $msg;
                    return [$v < $p1, $msg];
                case 'date':
                    $msg = empty($msg) ? "{label} should be before {p1}" : $msg;
                    return [$v < $p1, $msg];
            }
        });
    }

    private function maxRule(&$value, $ruleelements)
    {
        return $this->lteRule($value, $ruleelements);
    }

    private function lteRule(&$value, $ruleelements)
    {
        return $this->compareRule($value, $ruleelements, function ($type, $v, $p1, $msg) {
            switch ($type) {
                case 'string':
                    $msg = empty($msg) ? "{label} should contain no more than {p1} characters" : $msg;
                    return [$v <= $p1, $msg];
                default:
                case 'value':
                    $msg = empty($msg) ? "{label} should be less than or equal to {p1}" : $msg;
                    return [$v <= $p1, $msg];
                case 'date':
                    $msg = empty($msg) ? "{label} should be at or before {p1}" : $msg;
                    return [$v <= $p1, $msg];
            }
        });
    }

    private function eqRule(&$value, $ruleelements)
    {
        return $this->compareRule($value, $ruleelements, function ($type, $v, $p1, $msg) {
            switch ($type) {
                case 'string':
                    $msg = empty($msg) ? "{label} should contain exactly {p1} characters" : $msg;
                    return [$v == $p1, $msg];
                default:
                case 'value':
                    $msg = empty($msg) ? "{label} should be equal to {p1}" : $msg;
                    return [abs($v - $p1) < 0.0001, $msg];
                case 'date':
                    $msg = empty($msg) ? "{label} should be at {p1}" : $msg;
                    return [$v == $p1, $msg];
            }
        });
    }

    private function gtRule(&$value, $ruleelements)
    {
        return $this->compareRule($value, $ruleelements, function ($type, $v, $p1, $msg) {
            switch ($type) {
                case 'string':
                    $msg = empty($msg) ? "{label} should contain more than {p1} characters" : $msg;
                    return [$v > $p1, $msg];
                default:
                case 'value':
                    $msg = empty($msg) ? "{label} should be greater than to {p1}" : $msg;
                    return [$v > $p1, $msg];
                case 'date':
                    $msg = empty($msg) ? "{label} should be after {p1}" : $msg;
                    return [$v > $p1, $msg];
            }
        });
    }
    
    private function minRule(&$value, $ruleelements)
    {
        return $this->gteRule($value, $ruleelements);
    }

    private function gteRule(&$value, $ruleelements)
    {
        return $this->compareRule($value, $ruleelements, function ($type, $v, $p1, $msg) {
            switch ($type) {
                case 'string':
                    $msg = empty($msg) ? "{label} should contain no less than {p1} characters" : $msg;
                    return [$v >= $p1, $msg];
                default:
                case 'value':
                    $msg = empty($msg) ? "{label} should be greater than or equal to {p1}" : $msg;
                    return [$v >= $p1, $msg];
                case 'date':
                    $msg = empty($msg) ? "{label} should be at or after {p1}" : $msg;
                    return [$v >= $p1, $msg];
            }
        });
    }

    private function trimRule(&$value, $ruleelements)
    {
        $value = trim("$value");
        return true;
    }

    private function upperRule(&$value, $ruleelements)
    {
        $value = strtoupper($value);
        return true;
    }

    private function ucfirstRule(&$value, $ruleelements)
    {
        $value = ucfirst($value);
        return true;
    }

    private function lowerRule(&$value, $ruleelements)
    {
        $value = strtolower($value);
        return true;
    }

    private function emailRule(&$value, $ruleelements)
    {
        $retval = filter_var($value, FILTER_VALIDATE_EMAIL);
        if ($retval === false) {
            $msg = isset($ruleelements['message']) ? $ruleelements['message'] : "{label} is not a correct e-mail address";
            $this->addError($msg, $ruleelements);
        }
        return $retval;
    }

    private function urlRule(&$value, $ruleelements)
    {
        $retval = filter_var($value, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED);
        if ($retval === false) {
            $msg = isset($ruleelements['message']) ? $ruleelements['message'] : "{label} is not a correct website";
            $this->addError($msg, $ruleelements);
        }
        return $retval;
    }

    private function dateRule(&$value, $ruleelements)
    {
        $retval = $this->sanitizeDate($value);
        if ($retval === false) {
            $msg = isset($ruleelements['message']) ? $ruleelements['message'] : "{label} is not a date";
            $this->addError($msg, $ruleelements);
        }
        return $retval;
    }

    private function datetimeRule(&$value, $ruleelements)
    {
        $retval = $this->sanitizeDateTime($value);
        if ($retval === false) {
            $msg = isset($ruleelements['message']) ? $ruleelements['message'] : "{label} is not a date + time";
            $this->addError($msg, $ruleelements);
        }
        return $retval;
    }

    private function jsonRule(&$value, $ruleelements)
    {
        $value = json_encode($value);
        return true;
    }

    private function enumRule(&$value, $ruleelements)
    {
        $params = isset($ruleelements['parameters']) ? $ruleelements['parameters'] : [];
        $retval = in_array($value, $params);
        if ($retval === false) {
            $msg = isset($ruleelements['message']) ? $ruleelements['message'] : "{label} should be one of {p1}";
            $ruleelements['p1'] =  json_encode($params);
            $this->addError($msg, $ruleelements);
        }
        return $retval;
    }

    private function modelRule(&$value, $ruleelements)
    {
        $params = isset($ruleelements['parameters']) ? $ruleelements['parameters'] : [];
        $msg = isset($ruleelements['message']) ? $ruleelements['message'] : null;
        $retval = false;
        if (count($params) > 0) {
            $cname = $params[0];
            try {
                $id = intval($value);
                $attrmodel = new $cname($id);
                $attrmodel->load();

                if ($attrmodel->getKey() != $id) {
                    $retval = false;
                }
                if ($msg === null) {
                    $msg = "Please select a valid value for {label}";
                }
            }
            catch (Exception $e) {
                if ($msg === null) {
                    $msg = "{label} caused internal model error";
                }
                $retval = false;
            }
        }
        if (!$retval) {
            $this->addError($msg, $ruleelements);
        }
        return $retval;
    }

    private function containsRule(&$value, $ruleelements)
    {
        $params = isset($ruleelements['parameters']) ? $ruleelements['parameters'] : [];
        $msg = isset($ruleelements['message']) ? $ruleelements['message'] : null;
        $retval = false;
        if (count($params) > 0 && is_array($value)) {
            $cname = $params[0];
            try {
                $lst = array();
                foreach ($value as $objvals) {
                    $id = intval($objvals['id']);
                    if (empty($id)) {
                        $id = 0;
                    }

                    $obj = new $cname($id);
                    $validator = new Validator($obj);

                    $result = $validator->validate($objvals);
                    $retval = $result && $retval;
                    if (!$result && isset($validator->errors) && sizeof($validator->errors)) {
                        $this->errors = array_merge($this->errors, $validator->errors);
                    }
                    $lst[] = $obj;
                }
                $addfield = sizeof($params) > 1 ? $params[1] : "sublist";
                $this->model->$addfield = $lst;
            }
            catch (Exception $e) {
                if ($msg === null) {
                    $msg = "{label} caused internal model error";
                }
                $retval = false;
            }
        }
        if (!$retval) {
            $this->addError($msg, $ruleelements);
        }
        return $retval;
    }


    private function addError($msg, $ruleelements)
    {
        $rule = $ruleelements['rule'];
        $label = $ruleelements['label'];
        $p1 = isset($ruleelements['p1']) ? $ruleelements['p1'] : null;
        $p2 = isset($ruleelements['p2']) ? $ruleelements['p2'] : null;

        $msg = str_replace(array("{label}", "{rule}", "{p1}", "{p2}"), array($label, $rule, $p1, $p2), $msg);
        $this->errors[] = $msg;
    }

    protected function sanitizeName($name)
    {
        // names contain only alphabetic characters, dash, apostrophe and spaces
        return mb_ereg_replace("([^\w \-'])", '', $name);
    }

    protected function sanitizeDate($date)
    {
        // we expect yyyy-mm-dd, but we'll let the date_parse function manage this
        $vals = date_parse($date);
        if ($this->isDate($vals)) {
            return $vals;
        }
        return null;
    }

    public function isDate($value)
    {
        return is_array($value) && isset($value['year']) && isset($value['month']) && isset($value['day'])
            && is_numeric($value['year']) && $value['year'] !== false
            && is_numeric($value['month']) && $value['month'] !== false
            && is_numeric($value['day']) && $value['day'] !== false;
    }

    protected function sanitizeDatetime($date)
    {
        // we expect yyyy-mm-dd HH:MM:SS, but we'll let the strtotime function manage this
        $ts = strtotime($date);
        if ($ts === false) {
            return null;
        }
        return strftime('%F %T', $ts);
    }
}