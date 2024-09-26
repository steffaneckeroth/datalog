<?php
class Database
{
    private $pdo;
    private $host = '127.0.0.1';
    private $port = '5432';
    private $dbname = 'vongfrysehuse';
    private $user = 'hovmark';
    private $password = 'hovmark';

    public function connect() {
        $result = false;
        
        try {
            if ($this->pdo = new PDO('pgsql:host=' . $this->host . ';port=' . $this->port . ';user=' . $this->user . ';dbname=' . $this->dbname . ';password=' . $this->password)) {
                $this->pdo->setAttribute(PDO::ATTR_TIMEOUT, 60);
                $result = true;
            }
        } catch (PDOException $e) {}

        return $result;
    }

    public function select($sql, $params = null) {
        $stmt = $this->pdo->prepare($sql);

        if ($stmt->execute($params) === FALSE) {
            $data = array();
        } else {
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (count($data) == 0) {
            $data = false;
        }
        
        return $data;
    }

    public function query($sql, $params = null)
    {
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($params);
        return $result;
    }

    public function error()
    {
        $error = $this->pdo->errorInfo();
        if ($error[0] == "00000" || $error == null) {
            $error = array();
        }
        return $error;
    }

    public function disconnect()
    {
        $this->pdo = null;
    }
}
