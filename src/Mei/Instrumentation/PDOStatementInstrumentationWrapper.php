<?php
namespace Mei\Instrumentation;

use PDOException;

class PDOStatementInstrumentationWrapper
{
    private $instrumentor;
    /** @var $statement \PDOStatement **/
    private $statement;
    private $id;
    private $pdoQueries;

    private $storedParams = array();

    public function __construct($instrumentor, $pdostatement, $id, &$pdoQueries)
    {
        $this->instrumentor = $instrumentor;
        $this->statement = $pdostatement;
        $this->id = $id;
        $this->pdoQueries = &$pdoQueries;
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
                $this->pdoQueries[$this->id] = array('query' => $this->statement->queryString, 'params' => $this->storedParams, 'rows' => $this->statement->rowCount(), 'method' => 'execute');
            }
            else {
                $out = $this->statement->execute($params);
                $this->storedParams = $params;
                $this->pdoQueries[$this->id] = array('query' => $this->statement->queryString, 'params' => $this->storedParams, 'rows' => $this->statement->rowCount(), 'method' => 'execute');
            }
        } catch (PDOException $e) {
            if (!in_array($e->errorInfo[1], array(1213, 1205)) || $retries < 0) {
                $this->pdoQueries[$this->id] = array('query' => $this->statement->queryString, 'params' => $this->storedParams, 'method' => 'execute', 'error' => $e->errorInfo);
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
        $args = func_get_args();
        $result = call_user_func_array(array($this->statement, 'fetchAll'), $args);
        $this->pdoQueries[$this->id] = array('query' => $this->statement->queryString, 'params' => $this->storedParams, 'results' => $result, 'method' => 'fetchAll');

        return $result;
    }

    public function bindValue()
    {
        $args = func_get_args();
        $result = call_user_func_array(array($this->statement, 'bindValue'), $args);
        $this->storedParams[$args[0]] = $args[1];

        return $result;
    }

    public function fetch()
    {
        $args = func_get_args();
        $result = call_user_func_array(array($this->statement, 'fetch'), $args);
        $this->pdoQueries[$this->id] = array('query' => $this->statement->queryString, 'params' => $this->storedParams, 'results' => [$result], 'method' => 'fetch');

        return $result;
    }

    public function fetchColumn()
    {
        $args = func_get_args();
        $result = call_user_func_array(array($this->statement, 'fetchColumn'), $args);
        $this->pdoQueries[$this->id] = array('query' => $this->statement->queryString, 'params' => $this->storedParams, 'results' => [$result], 'method' => 'fetchColumn');

        return $result;
    }

    private function makeCall($method, $args)
    {
        return call_user_func_array(array($this->statement, $method), $args);
    }
}
