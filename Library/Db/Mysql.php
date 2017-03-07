<?php
namespace Library\Db;

class Mysql
{
    /**
     * PDO instance
     * @var \PDO
     */
    protected $handler;

    /**
     *
     */
    private $connect = array();

    protected $attributes = array(
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::MYSQL_ATTR_INIT_COMMAND => "SET CHARACTER SET utf8"
    );
    static private $_connections = [];
    static private $_debug = false;

    private function _connect()
    {
            // Try to use persistent connection
            $this->handler = new \PDO(
                $this->connect['type'] . ":host=" . $this->connect['host'] . ";dbname=" . $this->connect['database'],
                $this->connect['user'],
                $this->connect['pass'],
                $this->attributes);

    }

    function __construct($host, $user, $pass, $database)
    {
        $this->connect = array(
            'type' => "mysql",
            'host' => $host,
            'user' => $user,
            'pass' => $pass,
            'database' => $database,
            'key' => $host.$user
        );

        if (empty(self::$_connections[$this->connect["key"]])){
            $this->_connect();
            self::$_connections[$this->connect["key"]] = $this->handler;
        } else {
            $this->handler = self::$_connections[$this->connect["key"]];
        }
    }

    /**
     * @param $debug
     */
    public static function setDebug($debug)
    {
        static::$_debug = $debug;
    }

    /**
     * @param $query
     * @return DBStatement
     * @throws Exception
     */
    public function query($query)
    {
        $this->handler->exec("USE " . $this->connect['database']);
        if (self::$_debug) {
            echo $query."<br />\n";
        }
        $result = $this->handler->query($query);
        return new DbStatement($result);
    }

    /**
     * @param $query
     * @param array $params
     * @return DBStatement
     */
    public function paramQuery($query, $params = [])
    {
        if (empty($params) == true || is_array($params) == false) {
            return $this->query($query);
        } else {
            $this->handler->exec("USE " . $this->connect['database']);
            $statement = $this->handler->prepare($query);
            foreach ($params as $param => $value) {
                $statement->bindValue($param, $value);
            }
            $statement->execute();
            return new DbStatement($statement);
        }
    }

    /**
     * @param $query
     * @param $params
     * @return string
     */
    public function insert($query, $params)
    {
        $this->handler->exec("USE " . $this->connect['database']);
        try {
            $statement = $this->handler->prepare($query);
            foreach ($params as $param => $value) {
                $statement->bindValue($param, $value);
            }
            $statement->execute();
            return $this->handler->lastInsertId();
        } catch(\PDOException $e) {

        }

    }

    /**
     * @param $query
     * @param $params
     * @return bool|int
     */
    public function update($query, $params)
    {
        $this->handler->exec("USE " . $this->connect['database']);
        try {
            $statement = $this->handler->prepare($query);
            foreach ($params as $param => $value) {
                $statement->bindValue($param, $value);
            }
            $success =$statement->execute();
            return ($success == true) ? $statement->rowCount() : false;
        } catch(\PDOException $e) {
            //Todo log errors
        }

    }
    /**
     * @param string $name [optional] <p>
     * Name of the sequence object from which the ID should be returned.
     * </p>
     * @return string If a sequence name was not specified for the <i>name</i>
     * parameter, <b>PDO::lastInsertId</b> returns a
     * string representing the row ID of the last row that was inserted into
     * the database.
     * </p>
     */
    public function getLastInsertId($name = null)
    {
        return $this->handler->lastInsertId($name);
    }
}