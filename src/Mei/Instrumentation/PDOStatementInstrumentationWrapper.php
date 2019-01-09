<?php
namespace Mei\Instrumentation;

use PDOException;

class PDOStatementInstrumentationWrapper
{
    private $instrumentor;
    /** @var $statement \PDOStatement **/
    private $statement;
    private $id;

    public function __construct($instrumentor, $pdostatement, $id)
    {
        $this->instrumentor = $instrumentor;
        $this->statement = $pdostatement;
        $this->id = $id;
    }

    public function __call($method, $args)
    {
        return $this->makeCall($method, $args);
    }

    public function execute($params = null, $retries = 3)
    {
        $iid = $this->instrumentor->start('pdostatement:execute:' . $this->id . '_' . $retries, $params);
        $out = null;

        try {
            if (is_null($params)) {
                $out = $this->statement->execute();
            }
            else {
                $out = $this->statement->execute($params);
            }
        } catch (PDOException $e) {
            if (!in_array($e->errorInfo[1], array(1213, 1205)) || $retries < 0) {
                throw $e;
            }
            sleep(max([2, (3 - $retries) * 3])); // wait longer as attempts increase
            $this->execute($params, $retries - 1);
        }

        $this->instrumentor->end($iid);
        return $out;
    }

    public function fetchAll()
    {
        return call_user_func_array(array($this->statement, 'fetchAll'), func_get_args());
    }

    public function bindValue()
    {
        return call_user_func_array(array($this->statement, 'bindValue'), func_get_args());
    }

    public function fetch()
    {
        return call_user_func_array(array($this->statement, 'fetch'), func_get_args());
    }

    public function fetchColumn()
    {
        return call_user_func_array(array($this->statement, 'fetchColumn'), func_get_args());
    }

    private function makeCall($method, $args) {
        return call_user_func_array(array($this->statement, $method), $args);
    }
}
