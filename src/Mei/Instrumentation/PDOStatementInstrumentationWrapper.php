<?php declare(strict_types=1);

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
    /** @var Instrumentor */
    private $instrumentor;
    /** @var PDOStatement $statement * */
    private $statement;
    /** @var string $id */
    private $id;

    /**
     * PDOStatementInstrumentationWrapper constructor.
     *
     * @param Instrumentor $instrumentor
     * @param PDOStatement $pdostatement
     * @param string $id
     */
    public function __construct(Instrumentor $instrumentor, PDOStatement $pdostatement, string $id)
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
     * @param array|null $params
     * @param int $retries
     *
     * @return bool|null
     */
    public function execute(?array $params = null, int $retries = 3): ?bool
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
