<?php

declare(strict_types=1);

namespace Mei\PDO;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Class PDOStatementWrapper
 *
 * @package Mei\PDO
 */
final class PDOStatementWrapper extends PDOStatement
{
    /**
     * @var PDOWrapper
     */
    private $pdo;

    /**
     * @var array
     */
    private $bindings = [];

    /**
     * PDOStatementWrapper constructor.
     *
     * @param PDOWrapper $pdo
     */
    protected function __construct(PDOWrapper $pdo)
    {
        $this->pdo = $pdo;
    }

    /** {@inheritDoc} */
    public function bindParam(
        $parameter,
        &$variable,
        $data_type = PDO::PARAM_STR,
        $length = null,
        $driver_options = null
    ): bool {
        $this->bindings[$parameter] = $variable;

        if ($length && $driver_options) {
            return parent::bindParam($parameter, $variable, $data_type, $length, $driver_options);
        }
        if ($length && !$driver_options) {
            return parent::bindParam($parameter, $variable, $data_type, $length);
        }
        return parent::bindParam($parameter, $variable, $data_type);
    }

    /** {@inheritDoc} */
    public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR): bool
    {
        $this->bindings[$parameter] = $value;
        return parent::bindValue($parameter, $value, $data_type);
    }

    /** {@inheritDoc} */
    public function execute($params = null, int $retries = 3): bool
    {
        if (is_array($params)) {
            $this->bindings = $params;
        }

        $statement = $this->addValuesToQuery(
            $this->bindings,
            $this->queryString
        );
        $start = microtime(true);

        try {
            if ($params === null) {
                $out = parent::execute();
            } else {
                $out = parent::execute($params);
            }
        } catch (PDOException $e) {
            if ($retries < 0 || !in_array($e->errorInfo[1], [1213, 1205], true)) {
                throw $e;
            }
            sleep(max([2, (3 - $retries) * 3])); // wait longer as attempts increase
            return $this->execute($params, $retries - 1);
        }

        $this->pdo->addLog($statement, microtime(true) - $start);

        return $out;
    }

    /**
     * @param array $bindings
     * @param string $query
     *
     * @return mixed
     */
    private function addValuesToQuery(array $bindings, string $query)
    {
        $indexed = ($bindings === array_values($bindings));
        foreach ($bindings as $param => $value) {
            $value = (is_numeric($value) || $value === null) ? $value : $this->pdo->quote($value);
            $value = $value ?? 'null';
            if ($indexed) {
                $query = preg_replace('/\?/', $value, $query, 1);
            } else {
                $query = str_replace($param, $value, $query);
            }
        }

        return $query;
    }
}
