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
 * {@inheritDoc}
 *
 * @package Mei\Instrumentation
 */
final class PDOInstrumentationWrapper extends PDO
{
    /**
     * @var Instrumentor $instrumentor
     */
    private $instrumentor;

    /**
     * @var array $transactionQueue
     */
    private $transactionQueue = [];

    /**
     * PDOInstrumentationWrapper constructor.
     *
     * @param Instrumentor $instrumentor
     * @param string $dsn
     * @param string|null $username
     * @param string|null $passwd
     * @param array|null $options
     */
    public function __construct(
        Instrumentor $instrumentor,
        string $dsn,
        ?string $username = null,
        ?string $passwd = null,
        ?array $options = null
    ) {
        $this->instrumentor = $instrumentor;
        parent::__construct($dsn, $username, $passwd, $options);
        $this->setAttribute(
            PDO::ATTR_STATEMENT_CLASS,
            [PDOStatementInstrumentationWrapper::class, [$instrumentor]]
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
        $tid = $this->instrumentor->start('pdo:transaction'); // get tid
        $this->transactionQueue[] = $tid; // ... and push it to array

        if (!parent::inTransaction()) {
            return parent::beginTransaction(); // creating new parent transaction
        }

        return $this->exec('SAVEPOINT tq_' . $tid) ? true : false; // creating new child transaction via savepoint
    }

    /** {@inheritDoc} */
    public function commit(): bool
    {
        $tid = array_pop($this->transactionQueue);
        if (count($this->transactionQueue) !== 0) {
            return true;
        } // there are still transactions left, do nothing as theyll be commited by parent transaction

        $res = parent::commit();
        $this->instrumentor->end($tid, 'commit');
        return $res;
    }

    /** {@inheritDoc} */
    public function rollBack(): bool
    {
        $tid = array_pop($this->transactionQueue); // pop transactionId
        if (count($this->transactionQueue) === 0) { // this is parent transaction, do transaction rollback
            $res = parent::rollBack();
        } else {
            $res = parent::exec(
                'ROLLBACK TO tq_' . $tid
            ) ? true : false; // this is child transaction, rollback to savepoint
        }

        $this->instrumentor->end($tid, 'rollBack');
        return $res;
    }

    /** {@inheritDoc} */
    public function query($statement, $type = null, $type_arg = null, $ctorarg = [])
    {
        $set = [$type, $type_arg, $ctorarg];
        $n = 0;
        foreach ($set as $elem) {
            if ($elem === null) {
                break;
            }
            $n++;
        }

        $iid = $this->instrumentor->start('pdo:query:' . md5($statement), $statement);
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
        $this->instrumentor->end($iid, $res);

        return $res;
    }

    /** {@inheritDoc} */
    public function prepare($statement, $driver_options = [])
    {
        $iid = $this->instrumentor->start('pdo:prepare:' . md5($statement), $statement);
        $res = parent::prepare($statement, $driver_options);
        $this->instrumentor->end($iid, $res instanceof PDOStatement);
        return $res;
    }

    /** {@inheritDoc} */
    public function exec($statement, int $retries = 3)
    {
        $hash = md5($statement . rand());
        $iid = $this->instrumentor->start('pdo:exec:' . $hash . '_' . $retries, $statement);
        try {
            $res = parent::exec($statement);
        } catch (PDOException $e) {
            if ($retries < 0 || !in_array($e->errorInfo[1], [1213, 1205], true)) {
                throw $e;
            }
            sleep(max([2, (3 - $retries) * 3])); // wait longer as attempts increase
            $this->instrumentor->end($iid, $e);
            return $this->exec($statement, $retries - 1);
        }
        $this->instrumentor->end($iid, $res);
        return $res;
    }

    /** {@inheritDoc} */
    public function quote($string, $parameter_type = PDO::PARAM_STR)
    {
        $iid = $this->instrumentor->start(
            'pdo:quote:' . md5($string . $parameter_type),
            $string . '_' . $parameter_type
        );
        $res = parent::quote($string, $parameter_type);
        $this->instrumentor->end($iid, $res);
        return $res;
    }
}
