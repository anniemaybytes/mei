<?php declare(strict_types=1);

namespace Mei\Instrumentation;

use BadFunctionCallException;
use PDO;
use PDOException;
use PDOStatement;
use Tracy\Debugger;

/**
 * Class PDOInstrumentationWrapper
 *
 * @package Mei\Instrumentation
 */
class PDOInstrumentationWrapper
{
    /** @var Instrumentor $instrumentor */
    private $instrumentor;
    /** @var PDO $pdo * */
    private $pdo;
    /** @var array $transactionQueue */
    private $transactionQueue = [];

    /**
     * PDOInstrumentationWrapper constructor.
     *
     * @param Instrumentor $instrumentor
     * @param PDO $pdo
     */
    public function __construct(Instrumentor $instrumentor, PDO $pdo)
    {
        $this->instrumentor = $instrumentor;
        $this->pdo = $pdo;
    }

    public function __destruct()
    {
        if ($this->transactionQueue) {
            Debugger::log('PDO transaction queue was not empty on destruct!', Debugger::WARNING);
            while (count($this->transactionQueue) > 0) {
                $this->rollBack();
            }
        }
    }

    // this implements nested transactions - a savepoint is created within parent transaction to which child transaction can rollback to
    // however if code or query would to fail, a parent transaction will be rollbacked fully along with all child transactions regardless of where the issue was
    // even if child transaction was already commited (commiting child transaction just removes it from queue stack)
    /**
     * @return bool|int
     */
    public function beginTransaction()
    {
        $tid = $this->instrumentor->start('pdo:transaction'); // get tid
        array_push($this->transactionQueue, $tid); // ... and push it to array

        if (!$this->pdo->inTransaction()) {
            return $this->pdo->beginTransaction(); // creating new parent transaction
        } else {
            return $this->exec('SAVEPOINT tq_' . $tid); // creating new child transaction via savepoint
        }
    }

    /**
     * @return bool
     */
    public function commit(): bool
    {
        $tid = array_pop($this->transactionQueue);
        if (count($this->transactionQueue) != 0) {
            return true;
        } // there are still transactions left, do nothing as theyll be commited by parent transaction

        $res = $this->pdo->commit();
        $this->instrumentor->end($tid, 'commit');
        return $res;
    }

    /**
     * @return bool|int
     */
    public function rollBack()
    {
        $tid = array_pop($this->transactionQueue); // pop transactionId
        if (count($this->transactionQueue) == 0) { // this is parent transaction, do transaction rollback
            $res = $this->pdo->rollBack();
        } else {
            $res = $this->pdo->exec('ROLLBACK TO tq_' . $tid); // this is child transaction, rollback to savepoint
        }

        $this->instrumentor->end($tid, 'rollBack');
        return $res;
    }

    /**
     * @param string $statement
     * @param int $type
     * @param string $type_arg
     * @param array $ctorarg
     *
     * @return false|PDOStatement
     */
    public function query(string $statement, ?int $type = null, ?string $type_arg = null, ?array $ctorarg = null)
    {
        $set = [$type, $type_arg, $ctorarg];
        $n = 0;
        foreach ($set as $elem) {
            if (is_null($elem)) {
                break;
            }
            $n++;
        }

        $iid = $this->instrumentor->start('pdo:query:' . md5($statement), $statement);
        switch ($n) {
            case 0:
                $res = $this->pdo->query($statement);
                break;
            case 1:
                $res = $this->pdo->query($statement, $type);
                break;
            case 2:
                $res = $this->pdo->query($statement, $type, $type_arg);
                break;
            case 3:
                $res = $this->pdo->query($statement, $type, $type_arg, $ctorarg);
                break;
            default:
                throw new BadFunctionCallException(
                    "PDOInstrumentationWrapper can't handle query with " . $n . " additional arguments"
                );
                break;
        }
        $this->instrumentor->end($iid);

        return $res;
    }

    /**
     * @param string $statement
     * @param array $driver_options
     *
     * @return PDOStatementInstrumentationWrapper
     */
    public function prepare(string $statement, array $driver_options = []): PDOStatementInstrumentationWrapper
    {
        $hash = md5($statement . rand());
        $iid = $this->instrumentor->start('pdo:prepare:' . $hash, $statement);
        $res = new PDOStatementInstrumentationWrapper(
            $this->instrumentor,
            $this->pdo->prepare($statement, $driver_options),
            $hash
        );
        $this->instrumentor->end($iid);
        return $res;
    }

    /**
     * @param string $statement
     * @param int $retries
     *
     * @return false|int
     */
    public function exec(string $statement, int $retries = 3)
    {
        $hash = md5($statement . rand());
        $iid = $this->instrumentor->start('pdo:exec:' . $hash . '_' . $retries, $statement);
        try {
            $res = $this->pdo->exec($statement);
        } catch (PDOException $e) {
            if (!in_array($e->errorInfo[1], [1213, 1205]) || $retries < 0) {
                throw $e;
            }
            sleep(max([2, (3 - $retries) * 3])); // wait longer as attempts increase
            return $this->exec($statement, $retries - 1);
        }
        $this->instrumentor->end($iid);
        return $res;
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
     * @param $method
     * @param $args
     *
     * @return mixed
     */
    private function makeCall($method, $args)
    {
        return call_user_func_array([$this->pdo, $method], $args);
    }

    /**
     * @param string $string
     * @param int $parameter_type
     *
     * @return false|string
     */
    public function quote(string $string, int $parameter_type = PDO::PARAM_STR)
    {
        $iid = $this->instrumentor->start(
            'pdo:quote:' . md5($string . $parameter_type),
            $string . '_' . $parameter_type
        );
        $res = $this->pdo->quote($string, $parameter_type);
        $this->instrumentor->end($iid);
        return $res;
    }
}
