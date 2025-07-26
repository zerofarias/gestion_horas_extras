<?php
// ----------------------------------------------------------------------
// ARCHIVO 1: app/models/Database.php (VERSIÓN CORREGIDA Y ROBUSTA)
// Se han modificado resultSet() y single() para que acepten parámetros.
// ----------------------------------------------------------------------

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;

    private $dbh; // Database Handler
    private $stmt;
    private $error;

    public function __construct(){
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );

        try{
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e){
            $this->error = $e->getMessage();
            die('Error de Conexión: ' . $this->error);
        }
    }

    public function query($sql){
        $this->stmt = $this->dbh->prepare($sql);
    }

    public function bind($param, $value, $type = null){
        if(is_null($type)){
            switch(true){
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    public function execute($params = null){
        return is_null($params) ? $this->stmt->execute() : $this->stmt->execute($params);
    }

    /**
     * ACTUALIZADO: Ahora puede aceptar un array de parámetros.
     */
    public function resultSet($params = null){
        $this->execute($params);
        return $this->stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * ACTUALIZADO: Ahora puede aceptar un array de parámetros.
     */
    public function single($params = null){
        $this->execute($params);
        return $this->stmt->fetch(PDO::FETCH_OBJ);
    }

    public function rowCount(){
        return $this->stmt->rowCount();
    }
    
    public function lastInsertId(){
        return $this->dbh->lastInsertId();
    }
    
    public function beginTransaction(){
        return $this->dbh->beginTransaction();
    }

    public function commit(){
        return $this->dbh->commit();
    }

    public function rollBack(){
        return $this->dbh->rollBack();
    }
}
?>