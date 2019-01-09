<?php
namespace Mei\Instrumentation;

use Exception;
use PDO;
use PDOException;

class PDOInstrumentationWrapper
{
    private $instrumentor;
    /** @var $pdo PDO **/
    private $pdo;
    private $transactionQueue = array();

    public function __construct($instrumentor, $pdo)
    {
        $this->instrumentor = $instrumentor;
        $this->pdo = $pdo;
    }

    public function __destruct()
    {
        if ($this->transactionQueue) {
            while(count($this->transactionQueue) > 0) $this->rollBack();
        }
    }

    // this implements nested transactions - a savepoint is created within parent transaction to which child transaction can rollback to
    // however if code or query would to fail, a parent transaction will be rollbacked fully along with all child transactions regardless of where the issue was
    // even if child transaction was already commited (commiting child transaction just removes it from queue stack)
    public function beginTransaction()
    {
        $tid = $this->instrumentor->start('pdo:transaction'); // get tid
        array_push($this->transactionQueue, $tid); // ... and push it to array

        if(!$this->pdo->inTransaction()) {
            return $this->pdo->beginTransaction(); // creating new parent transaction
        } else {
            return $this->exec('SAVEPOINT tq_' . $tid); // creating new child transaction via savepoint
        }
    }

    public function commit()
    {
        $tid = array_pop($this->transactionQueue);
        if(count($this->transactionQueue) != 0) return true; // there are still transactions left, do nothing as theyll be commited by parent transaction

        $res = $this->pdo->commit();
        $this->instrumentor->end($tid, 'commit');
        return $res;
    }

    public function rollBack()
    {
        $tid = array_pop($this->transactionQueue); // pop transactionId
        if(count($this->transactionQueue) == 0) { // this is parent transaction, do transaction rollback
            $res = $this->pdo->rollBack();
        } else {
            $res = $this->pdo->exec('ROLLBACK TO tq_' . $tid); // this is child transaction, rollback to savepoint
        }

        $this->instrumentor->end($tid, 'rollBack');
        return $res;
    }

    public function query($statement, $type = null, $type_arg = null, $ctorarg = null)
    {
        $set = array($type, $type_arg, $ctorarg);
        $n = 0;
        foreach ($set as $elem) {
            if (is_null($elem)) break;
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
                throw new Exception("PDOInstrumentationWrapper can't handle query with " . $n . " additional arguments");
                break;
        }
        $this->instrumentor->end($iid);

        return $res;
    }

    public function prepare($statement, $driver_options = array())
    {
        $hash = md5($statement.rand());
        $iid = $this->instrumentor->start('pdo:prepare:' . $hash, $statement);
        $res = new PDOStatementInstrumentationWrapper($this->instrumentor, $this->pdo->prepare($statement, $driver_options), $hash);
        $this->instrumentor->end($iid);
        return $res;
    }

    public function exec($statement, $retries = 3)
    {
        $hash = md5($statement.rand());
        $iid = $this->instrumentor->start('pdo:exec:'. $hash . '_' . $retries, $statement);
        try {
            $res = $this->pdo->exec($statement);
        } catch (PDOException $e) {
            if (!in_array($e->errorInfo[1], array(1213, 1205)) || $retries < 0) {
                throw $e;
            }
            sleep(max([2, (3 - $retries) * 3])); // wait longer as attempts increase
            return $this->exec($statement, $retries - 1);
        }
        $this->instrumentor->end($iid);
        return $res;
    }

    public function __call($method, $args)
    {
        return $this->makeCall($method, $args);
    }

    private function makeCall($method, $args)
    {
        return call_user_func_array(array($this->pdo, $method), $args);
    }

    public function quote($string, $parameter_type = PDO::PARAM_STR)
    {
        $iid = $this->instrumentor->start('pdo:quote:'. md5($string.$parameter_type), $string . '_' . $parameter_type);
        $res = $this->pdo->quote($string, $parameter_type);
        $this->instrumentor->end($iid);
        return $res;
    }
}
