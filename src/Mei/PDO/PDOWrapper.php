<?php

declare(strict_types=1);

namespace Mei\PDO;

use BadFunctionCallException;
use PDO;
use PDOException;
use Tracy\Debugger;

/**
 * Class PDOInstrumentationWrapper
 *
 * @package Mei\Instrumentation
 */
final class PDOWrapper extends PDO
{
    /**
     * @var array $transactionQueue
     */
    private $transactionQueue = [];

    /**
     * Logged queries.
     *
     * @var array
     */
    private $log = [];

    /**
     * PDOInstrumentationWrapper constructor.
     *
     * @param string $dsn
     * @param string|null $username
     * @param string|null $passwd
     * @param array|null $options
     */
    public function __construct(
        string $dsn,
        ?string $username = null,
        ?string $passwd = null,
        ?array $options = null
    ) {
        parent::__construct($dsn, $username, $passwd, $options);
        $this->setAttribute(
            PDO::ATTR_STATEMENT_CLASS,
            [PDOStatementWrapper::class, [$this]]
        );
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

    /**
     * this implements nested transactions - a savepoint is created within parent transaction to which child transaction can rollback to
     * however if code or query would to fail, a parent transaction will be rollbacked fully along with all child transactions regardless of where the issue was
     * even if child transaction was already commited (commiting child transaction just removes it from queue stack)
     *
     * {@inheritDoc}
     * @noinspection MissUsingParentKeywordInspection
     */
    public function beginTransaction(): bool
    {
        $tid = str_replace('.', '', (string)microtime(true)) . '_' . count($this->transactionQueue);
        $this->transactionQueue[] = $tid; // ... and push it to array

        if (!parent::inTransaction()) {
            $start = microtime(true);
            $res = parent::beginTransaction(); // creating new parent transaction
            $this->addLog('START TRANSACTION', microtime(true) - $start);
            return $res;
        }

        return $this->exec('SAVEPOINT tq_' . $tid) ? true : false; // creating new child transaction via savepoint
    }

    /** {@inheritDoc} */
    public function commit(): bool
    {
        array_pop($this->transactionQueue);

        if (count($this->transactionQueue) !== 0) {
            return true;
        } // there are still transactions left, do nothing as theyll be commited by parent transaction

        $start = microtime(true);
        $res = parent::commit();
        $this->addLog('COMMIT', microtime(true) - $start);
        return $res;
    }

    /** {@inheritDoc} */
    public function rollBack(): bool
    {
        $tid = array_pop($this->transactionQueue); // pop transactionId

        if (count($this->transactionQueue) === 0) { // this is parent transaction, do transaction rollback
            $start = microtime(true);
            $res = parent::rollBack();
            $this->addLog('ROLLBACK', microtime(true) - $start);
        } else {
            $res = parent::exec(
                'ROLLBACK TO tq_' . $tid
            ) ? true : false; // this is child transaction, rollback to savepoint
        }

        return $res;
    }

    /** {@inheritDoc} */
    public function query($statement, $type = null, $type_arg = null, $ctorarg = [])
    {
        $start = microtime(true);

        $set = [$type, $type_arg, $ctorarg];
        $n = 0;
        foreach ($set as $elem) {
            if ($elem === null) {
                break;
            }
            $n++;
        }

        switch ($n) {
            case 0:
                $res = parent::query($statement);
                break;
            case 1:
                $res = parent::query($statement, $type);
                break;
            case 2:
                $res = parent::query($statement, $type, $type_arg);
                break;
            case 3:
                $res = parent::query($statement, $type, $type_arg, $ctorarg);
                break;
            default:
                throw new BadFunctionCallException(
                    "PDOInstrumentationWrapper can't handle query with " . $n . ' additional arguments'
                );
        }

        $this->addLog($statement, microtime(true) - $start);

        return $res;
    }

    /** {@inheritDoc} */
    public function exec($statement, int $retries = 3)
    {
        $start = microtime(true);

        try {
            $res = parent::exec($statement);
        } catch (PDOException $e) {
            if ($retries < 0 || !in_array($e->errorInfo[1], [1213, 1205], true)) {
                throw $e;
            }
            sleep(max([2, (3 - $retries) * 3])); // wait longer as attempts increase
            return $this->exec($statement, $retries - 1);
        }
        $this->addLog($statement, microtime(true) - $start);

        return $res;
    }

    /**
     * Add query to logged queries.
     *
     * @param string $statement
     * @param float $time Elapsed seconds with microseconds
     */
    public function addLog(string $statement, float $time): void
    {
        $query = [
            'statement' => $statement,
            'time' => $time * 1000
        ];
        $this->log[] = $query;
    }

    /**
     * Return logged queries.
     *
     * @return array Logged queries
     */
    public function getLog(): array
    {
        return $this->log;
    }
}
