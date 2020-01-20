<?php declare(strict_types=1);

namespace Mei\Instrumentation;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Class PDOStatementInstrumentationWrapper
 *
 * {@inheritDoc}
 *
 * @package Mei\Instrumentation
 */
class PDOStatementInstrumentationWrapper extends PDOStatement
{
    /**
     * @var Instrumentor
     */
    private $instrumentor;

    /**
     * @var array
     */
    private $bindings = [];

    /**
     * PDOStatementInstrumentationWrapper constructor.
     *
     * @param Instrumentor $instrumentor
     */
    protected function __construct(Instrumentor $instrumentor)
    {
        $this->instrumentor = $instrumentor;
    }

    /** {@inheritDoc} */
    public function bindParam(
        $parameter,
        &$variable,
        $data_type = PDO::PARAM_STR,
        $length = null,
        $driver_options = null
    ) {
        $this->bindings[$parameter] = ['value' => $variable, 'type' => $data_type];
        return parent::bindParam($parameter, $variable, $data_type, $length, $driver_options);
    }

    /** {@inheritDoc} */
    public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR)
    {
        $this->bindings[$parameter] = ['value' => $value, 'type' => $data_type];
        return parent::bindValue($parameter, $value, $data_type);
    }

    /** {@inheritDoc} */
    public function execute($params = null, int $retries = 3)
    {
        $iid = $this->instrumentor->start(
            'pdostatement:execute:' . md5($this->queryString) . '_' . $retries,
            ['params' => $params, 'bindings' => $this->bindings]
        );

        try {
            if (is_null($params)) {
                $out = parent::execute();
            } else {
                $out = parent::execute($params);
            }
        } catch (PDOException $e) {
            if (!in_array($e->errorInfo[1], [1213, 1205]) || $retries < 0) {
                throw $e;
            }
            sleep(max([2, (3 - $retries) * 3])); // wait longer as attempts increase
            $this->instrumentor->end($iid, $e);
            return $this->execute($params, $retries - 1);
        }

        $this->instrumentor->end($iid, $out);
        return $out;
    }
}
