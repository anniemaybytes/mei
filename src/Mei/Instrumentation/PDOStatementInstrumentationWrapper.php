<?php

namespace Mei\Instrumentation;

use PDOException;
use PDOStatement;

/**
 * Class PDOStatementInstrumentationWrapper
 *
 * @package Mei\Instrumentation
 */
class PDOStatementInstrumentationWrapper
{
    private $instrumentor;
    /** @var PDOStatement $statement * */
    private $statement;
    private $id;

    /**
     * PDOStatementInstrumentationWrapper constructor.
     *
     * @param $instrumentor
     * @param $pdostatement
     * @param $id
     */
    public function __construct($instrumentor, $pdostatement, $id)
    {
        $this->instrumentor = $instrumentor;
        $this->statement = $pdostatement;
        $this->id = $id;
    }

    /**
     * @param $method
     * @param $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        return $this->makeCall($method, $args);
    }

    /**
     * @param null $params
     * @param int $retries
     *
     * @return bool|null
     */
    public function execute($params = null, $retries = 3)
    {
        $iid = $this->instrumentor->start('pdostatement:execute:' . $this->id . '_' . $retries, $params);
        $out = null;

        try {
            if (is_null($params)) {
                $out = $this->statement->execute();
            } else {
                $out = $this->statement->execute($params);
            }
        } catch (PDOException $e) {
            if (!in_array($e->errorInfo[1], [1213, 1205]) || $retries < 0) {
                throw $e;
            }
            sleep(max([2, (3 - $retries) * 3])); // wait longer as attempts increase
            $this->execute($params, $retries - 1);
        }

        $this->instrumentor->end($iid);
        return $out;
    }

    /**
     * @param $method
     * @param $args
     *
     * @return mixed
     */
    private function makeCall($method, $args)
    {
        return call_user_func_array([$this->statement, $method], $args);
    }
}
