<?php

declare(strict_types=1);

namespace Mei\Model;

use InvalidArgumentException;
use Mei\Cache\IKeyStore;
use Mei\Entity\IEntity;
use Mei\Entity\PDOParamMapper;
use PDO;

/**
 * Class Model
 *
 * @package Mei\Model
 */
abstract class Model implements IModel
{
    protected PDO $db;

    protected IKeyStore $cache;

    protected $entityBuilder;

    protected bool $inTransaction;

    public function __construct(PDO $db, IKeyStore $cache, callable $entityBuilder)
    {
        $this->entityBuilder = $entityBuilder;
        $this->inTransaction = false;
        $this->cache = $cache;
        $this->db = $db;
    }

    protected function getDatabase(): PDO
    {
        return $this->db;
    }

    protected function getCache(): IKeyStore
    {
        return $this->cache;
    }

    abstract public function getTableName(): string;

    /**
     * Needs to be run immediately after a SELECT SQL_CALC_FOUND_ROWS statement
     */
    public function getFoundRows(): int
    {
        $q = $this->getDatabase()->query('SELECT FOUND_ROWS()');
        return (int)$q->fetchColumn();
    }

    public function getEntitiesFromIds(?array $ids): array
    {
        if (!$ids) {
            return [];
        }
        return array_map(fn($id) => ($this->getById($id)), $ids);
    }

    /** @inheritDoc */
    public function getById(?array $id): ?IEntity
    {
        if ($id === [] || !(is_array($id))) {
            return null;
        }

        $builder = $this->entityBuilder;
        $table = $this->getTableName();
        $entityCache = $this->getCache()->getEntityCache($table, $id);

        if ($this->inTransaction) {
            $row = null; // ignore cache if in the middle of a transaction
        } else {
            $row = $entityCache->getRow();
        }
        if (!$row) {
            $whereStr = implode(
                ' AND ',
                array_map(static fn($col) => "`$col` = :$col", array_keys($id))
            );

            $attrs = $builder($entityCache)->getAttributes(); // we need to create mockup of entity first

            /*
             * Note that when searching for strings, mysql is case-insensitive by default.
             * You can force a search to be case sensitive by using COLLATE utf8_bin
             */
            $query = "SELECT * FROM `$table` WHERE $whereStr";
            $q = $this->getDatabase()->prepare($query);
            foreach ($id as $param => $value) {
                $q->bindValue(':' . $param, $value, PDOParamMapper::map($attrs[$param]));
            }
            $q->execute();
            $row = $q->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $entityCache = $entityCache->setRow($row);
                $entityCache->save($this->getCache());
            } else {
                return null;
            }
        }

        return $builder($entityCache);
    }

    /** @inheritDoc */
    public function createEntity(array $arr): IEntity
    {
        $builder = $this->entityBuilder;
        $entityCache = $this->getCache()->getEntityCache($this->getTableName());
        /** @var IEntity $entity */
        $entity = $builder($entityCache);

        foreach ($arr as $k => $v) {
            $entity->$k = $v;
        }

        $entity->reset($entityCache);
        $entity->setNew(true);
        return $entity;
    }

    /** @inheritDoc */
    public function save(IEntity $entity): ?IEntity
    {
        $table = $this->getTableName();
        $entity = clone $entity;

        if ($entity->isNew()) {
            $idAttr = $entity->getIdAttributes();
            $id = $entity->getId();

            /*
             * If there are multiple primary keys, require that both are set before saving; otherwise, there is no way
             * to identify the entity after insert
             */
            if (count($idAttr) > 1) {
                if ($id === null || count($id) !== count($idAttr)) {
                    throw new InvalidArgumentException('Unable to save entity - primary key not set');
                }
            }

            /*
             * Note that getValues only returns values that are set (if ID is not set, no entry will exist for that key)
             */
            $values = $entity->getValues();
            $attrs = $entity->getAttributes();
            $cols = $vals = '';
            if ($values) {
                $cols = '`' . implode('`, `', array_keys($values)) . '`';
                $vals = ':' . implode(', :', array_keys($values));
            }

            // this query is fine even if $cols/$vals are empty
            $sql = "INSERT INTO `$table` ($cols) VALUES ($vals)";
            $q = $this->getDatabase()->prepare($sql);
            foreach ($values as $param => $value) {
                $q->bindValue(":$param", $value, PDOParamMapper::map($attrs[$param]));
            }
            $q->execute();

            $cache = $entity->getCacheable();

            // if using autoincrement id
            $idCol = reset($idAttr);
            if (count($idAttr) === 1 && !$id[$idCol]) {
                $insertId = $this->getDatabase()->lastInsertId();
                if ($insertId === '0') {
                    throw new InvalidArgumentException(
                        'Unable to save entity - failed to retrieve auto-increment id after save'
                    );
                }

                $id = [$idCol => $insertId];
                $cache->setId($id);
            }

            // delete anything that might have been in cache and retrieve what we just saved
            $cache->delete($this->getCache());

            /*
             * Note that it is possible the entity has no IDs, thus it is impossible to retrieve the entity
             * that just got inserted
             */
            return $this->getById($id) ?? $entity;
        }

        // nothing to change - return entity as is
        if (!$entity->hasChanged()) {
            return $entity;
        }

        $id = $entity->getId();
        $idAttr = $entity->getIdAttributes();
        if (($id === null) || (count($id) === 0) || (count($id) !== count($idAttr))) {
            throw new InvalidArgumentException('Unable to save entity - primary key not set');
        }

        $values = $entity->getChangedValues();
        $attrs = $entity->getAttributes();

        if (count($values) === 0) {
            throw new InvalidArgumentException(
                'Unable to save entity - nothing was changed, but marked as changed'
            );
        }

        /*
         * Prevent changing primary key, since this could result in overwriting other entities
         * Also checks that each of the ID columns is set
         */
        foreach ($idAttr as $idAttribute) {
            if (array_key_exists($idAttribute, $values)) {
                throw new InvalidArgumentException('Unable to save entity - primary key was changed');
            }
            if (!array_key_exists($idAttribute, $id)) {
                throw new InvalidArgumentException('Unable to save entity - primary key not set');
            }
        }

        $sql = "UPDATE `$table` SET ";

        // there must be changed values if we reached here
        $cols = array_keys($values);
        $cols = array_map(static fn($col) => ("`$col` = :$col"), $cols);
        $sql .= implode(', ', $cols);

        $where = array_map(static fn($col) => ("`$col` = :$col"), $idAttr);
        $where = implode(' AND ', $where);

        $sql .= " WHERE $where LIMIT 1";

        // add ID columns for the query execution
        $values = array_merge($values, $id);

        $q = $this->getDatabase()->prepare($sql);
        foreach ($values as $param => $value) {
            $q->bindValue(':' . $param, $value, PDOParamMapper::map($attrs[$param]));
        }
        $q->execute();

        // delete anything that might have been in cache and retrieve what we just saved
        $cache = $entity->getCacheable();
        $cache->delete($this->getCache());
        return $this->getById($id);
    }

    /** @inheritDoc */
    public function delete(?IEntity $entity): ?IEntity
    {
        if ($entity === null) {
            return null;
        }

        $table = $this->getTableName();
        $entity = clone $entity;

        $id = $entity->getId();
        if ($id === null || count($id) === 0) {
            throw new InvalidArgumentException('Unable to delete entity - primary key not set');
        }

        $where = [];
        foreach (array_keys($id) as $k) {
            $where[] = "`$k` = :$k";
        }
        $where = implode(' AND ', $where);

        $attrs = $entity->getAttributes();

        $sql = "DELETE FROM `$table` WHERE $where LIMIT 1";
        $q = $this->getDatabase()->prepare($sql);
        foreach ($id as $param => $value) {
            $q->bindValue(':' . $param, $value, PDOParamMapper::map($attrs[$param]));
        }
        $q->execute();
        $cache = $entity->getCacheable();
        $cache->delete($this->getCache());
        return $entity;
    }

    public function deleteById(array $id): ?IEntity
    {
        return $this->delete($this->getById($id));
    }

    public function beginTransaction(): bool|int
    {
        // if we indicate we want to use a transaction, stop using cache
        $this->inTransaction = true;
        return $this->getDatabase()->beginTransaction();
    }

    public function commit(): bool
    {
        $this->inTransaction = false;
        return $this->getDatabase()->commit();
    }

    public function rollBack(): bool|int
    {
        $this->inTransaction = false;
        return $this->getDatabase()->rollBack();
    }
}
