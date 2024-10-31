<?php

namespace platy\etsy\data;
use platy\etsy\DataService;

class SQLDataService{

    public function __construct($tbl_name) {
        global $wpdb;
        $this->db = $wpdb;
        $this->tbl_name = $wpdb->prefix . $tbl_name;
    }

    public function insert($data) {
        $this->db->insert($this->tbl_name, $data);
    }

    public function update($data, $item) {
        if(empty($item)) {
            throw new \RuntimeException("empty where clause sent to update");
        }
        $exists = !empty($this->get($item));
        if($exists) {
            $this->db->update($this->tbl_name, $data, $item);
            return;
        }
        $this->insert($data);
    }

    protected function where($item, $control = []) {
        $and = [];
        $comparator = $control['_compare'] ?? '=';
        foreach($item as $column => $value){
            $and[] = "$column $comparator '$value'";
        }
        return implode(" and ", $and);
    }

    private function remove_control_params(&$params) {
        $control = [];
        foreach($params as $key => $value) {
            if($key[0] == "_") {
                $control[$key] = $value;
                unset($params[$key]);
            }
        }
        return $control;
    }

    public function get($item, $single = true) {
        $control = $this->remove_control_params($item);
        $columns = $control["_columns"] ?? "*";
        $where = $this->where($item, $control);
        $results = $this->db->get_results("select $columns from {$this->tbl_name} where $where", ARRAY_A);
        if($single && !empty($resutls[1])) {
            throw new \RuntimeException("found more then one result");
        }
        return $single ? ($results[0] ?? null) : $results;
    }

    public function get_all() {
        return $this->db->get_results("select * from {$this->tbl_name}", ARRAY_A);
    }

    public function delete($item) {
        if(empty($item)) {
            throw new \RuntimeException("empty where clause sent to delete");
        }
        $this->db->delete($this->tbl_name, $item);
    }
}

