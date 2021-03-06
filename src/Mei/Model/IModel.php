<?php

declare(strict_types=1);

namespace Mei\Model;

use Mei\Entity\IEntity;

/**
 *
 * A Model interacts with the database. It allows retrieving and saving entities.
 *
 * A Model is allowed to handle only one Entity.
 *
 * A Model must be named the same as the Entity it handles.
 *
 * Only one function in the Model is allowed to persist an Entity - the save function.
 *
 * Only one function in the Model is allowed to delete an Entity - the delete function.
 *
 * All other functions in the Model must return an Entity, an array of Entities,
 * or some data related to an Entity. They must not alter the database in any way.
 *
 */
interface IModel
{
    /**
     * Return an entity corresponding to the primary key $id
     *
     * $id must be an array even if the primary key is not multi-column
     * The key of id must be the id attribute, and the value the value to search by
     *
     * Return null if no entity is found, or if the id is invalid
     */
    public function getById(array $id): ?IEntity;

    /**
     * Return an entity created from the provided array
     * The entity must return true to isNew and false to hasChanged
     *
     * @param array $arr array of entity attribute-value pairs
     */
    public function createEntity(array $arr): IEntity;

    /**
     * Save the entity handled by the model.
     * This method must not mutate the entity passed as a parameter.
     * This method must return the resulting entity.
     */
    public function save(IEntity $entity): ?IEntity;

    /**
     * Delete the entity handled by the model.
     * This method must not mutate the entity passed as a parameter.
     * This method must return back entity given as parameter.
     */
    public function delete(?IEntity $entity): ?IEntity;

    public function deleteById(array $id): mixed;
}
