<?php

class SimpleDB {

    public $db;

    public function __construct($database, $username, $password, $host = "127.0.0.1", $port = 3306){
        $this->db = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        $this->baseSql = "SELECT * FROM quotes WHERE channel=:channel";
        $this->baseCountSql = "SELECT COUNT(id) FROM quotes WHERE channel=:channel";

        $this->filterSql = " AND quote LIKE :filter";
    }

    public function count_quotes($channel = "", $filter = "") {
        $sqlQuery = $this->baseCountSql;
        if (!empty($filter)) {
            $sqlQuery .= $this->filterSql;
        }
        $sql = $this->db->prepare($sqlQuery);
        $sqlChannel = "#".$channel;
        $sql->bindParam(":channel", $sqlChannel);

        if (!empty($filter)) {
            $sqlFilter = "%$filter%";
            $sql->bindParam(":filter", $sqlFilter);
        }

        $sql->execute();
        return (int) $sql->fetchColumn();
    }

    public function get_quotes($channel = "", $filter = "", $page = 1, $quotesPerPage = 100) {
        $sqlQuery = $this->baseSql;
        if (!empty($filter)) {
            $sqlQuery .= $this->filterSql;
        }
        #TODO: Add pagination
        $sqlQuery .= " ORDER BY id DESC LIMIT :startLimit, :endLimit";

        $sql = $this->db->prepare($sqlQuery);
        $sqlChannel = "#".$channel;
        $sql->bindParam(":channel", $sqlChannel);
        $startLimit = ($page * $quotesPerPage) - $quotesPerPage;
        $sql->bindParam(":startLimit", $startLimit);
        $endLimit = $page * $quotesPerPage;
        $sql->bindParam(":endLimit", $endLimit);

        if (!empty($filter)) {
            $sqlFilter = "%$filter%";
            $sql->bindParam(":filter", $sqlFilter);
        }

        $sql->execute();
        return $sql->fetchAll();
    }
}
