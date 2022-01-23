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
    private PDOWrapper $PDO;
    private array $bindings;

    protected function __construct(PDOWrapper $PDO)
    {
        $this->PDO = $PDO;
        $this->bindings = [];
    }

    /**
     * @inheritDoc
     * @noinspection PhpParameterNameChangedDuringInheritanceInspection
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
     */
    public function bindParam(
        mixed $param,
        mixed &$var,
        int $type = PDO::PARAM_INT,
        int $length = null,
        mixed $options = null
    ): bool {
        $this->bindings[$param] = $var;

        return parent::bindParam(...func_get_args());
    }

    /** @inheritDoc */
    public function bindValue(mixed $param, mixed $value, int $type = PDO::PARAM_INT): bool
    {
        $this->bindings[$param] = $value;
        return parent::bindValue(...func_get_args());
    }

    /** @inheritDoc */
    public function execute(array $params = null, int $retries = 3): bool
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

        $this->PDO->addLog($statement, microtime(true) - $start);

        return $out;
    }

    private function addValuesToQuery(array $bindings, string $query): ?string
    {
        $indexed = array_is_list($bindings);
        foreach ($bindings as $param => $value) {
            $value = (is_numeric($value) || $value === null) ? $value : $this->PDO->quote($value);
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
