<?php

namespace core;

class Model {
    protected \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }
}