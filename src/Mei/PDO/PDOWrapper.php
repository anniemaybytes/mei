<?php

declare(strict_types=1);

namespace Mei\PDO;

use ArrayAccess;
use PDO;
use PDOException;
use PDOStatement;
use Psr\Container\ContainerInterface as Container;
use RuntimeException;
use Tracy\Debugger;

/**
 * Class PDOInstrumentationWrapper
 *
 * @package Mei\Instrumentation
 */
final class PDOWrapper extends PDO
{
    protected Container $di;
    protected ArrayAccess $config;

    private PDOLogger $logger;

    private array $transactions = [];

    public function __construct(Container $di, ?array $options = null)
    {
        $this->di = $di;
        $this->config = $di->get('config');
        $this->logger = $di->get(PDOLogger::class);

        $dsn = "mysql:dbname={$this->config['db.database']};charset=utf8;";
        if ($this->config['db.socket']) {
            $dsn .= "unix_socket={$this->config['db.socket']};";
        } elseif ($this->config['db.hostname'] && $this->config['db.port']) {
            $dsn .= "host={$this->config['db.hostname']};port={$this->config['db.port']};";
        } else {
            throw new RuntimeException('Either db.socket or both db.hostname and db.port must be configured');
        }

        parent::__construct(
            $dsn,
            $this->config['db.username'],
            $this->config['db.password'],
            ([PDO::ATTR_PERSISTENT => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION] + $options)
        );
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, [PDOStatementWrapper::class, [$this, $this->logger]]);
    }

    public function __destruct()
    {
        if ($this->transactions) {
            Debugger::log('PDO transaction queue was not empty on destruct!', Debugger::WARNING);
            while (count($this->transactions) > 0) {
                $this->rollBack();
            }
        }
    }

    /**
     * This implements nested transactions - a savepoint is created within parent transaction to which child
     * transaction can rollback to, however if code or query would to fail, a parent transaction will be rollbacked
     * fully along with all child transactions regardless of where the issue was even if child transaction was
     * already commited (commiting child transaction just removes it from queue stack)
     *
     * @inheritDoc
     * @noinspection MissUsingParentKeywordInspection
     */
    public function beginTransaction(): bool
    {
        $tid = str_replace('.', '', (string)microtime(true)) . '_' . count($this->transactions);
        $this->transactions[] = $tid;

        if (!parent::inTransaction()) {
            $start = microtime(true);
            $res = parent::beginTransaction(); // creating new parent
            $this->logger->recordEvent('START TRANSACTION', microtime(true) - $start);
            return $res;
        }

        return (bool)$this->exec('SAVEPOINT tq_' . $tid); // creating new child via savepoint
    }

    /** @inheritDoc */
    public function commit(): bool
    {
        array_pop($this->transactions);

        if (count($this->transactions) !== 0) {
            return true; // there are still transactions left, do nothing as they'll be commited by parent
        }

        $start = microtime(true);
        $res = parent::commit();
        $this->logger->recordEvent('COMMIT', microtime(true) - $start);
        return $res;
    }

    /**
     * @inheritDoc
     * @phpstan-impure
     */
    public function rollBack(): bool
    {
        $tid = array_pop($this->transactions); // pop transactionId

        if (count($this->transactions) === 0) { // this is parent transaction, do transaction rollback
            $start = microtime(true);
            $res = parent::rollBack();
            $this->logger->recordEvent('ROLLBACK', microtime(true) - $start);
        } else {
            $res = (bool)parent::exec('ROLLBACK TO tq_' . $tid); // this is child transaction, rollback to savepoint
        }

        return $res;
    }

    /** @inheritDoc */
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        $start = microtime(true);

        $res = parent::query(...func_get_args());

        $this->logger->recordEvent($query, microtime(true) - $start);

        return $res;
    }

    /** @inheritDoc */
    public function exec(string $statement, int $retries = 3): int|false
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
        $this->logger->recordEvent($statement, microtime(true) - $start);

        return $res;
    }
}
