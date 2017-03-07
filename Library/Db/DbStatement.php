<?php
namespace Library\Db;

class DbStatement
{
    /**
     * PDO instance
     * @var \PDOStatement
     */
    private $_result;
    private $_row;

    function __construct($result)
    {
        $this->_result = $result;
    }

    public function fetch()
    {
        if ($this->_result) {
            return $this->_row = $this->_result->fetch(\PDO::FETCH_ASSOC);//  mysql_fetch_assoc($this->_result);
        }
        return false;
    }

    public function row()
    {
        return $this->_row;
    }

    public function f($field)
    {
        return $this->_row[$field];
    }

    public function num()
    {
        if ($this->_result) {
            return $this->_result->rowCount();
        }
        return 0;
    }

    public function getResult()
    {
        return $this->_result;
    }
}