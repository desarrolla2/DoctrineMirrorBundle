<?php

/*
 * This file is part of the Jotelulu package
 *
 * Copyright (c) 2017 Adder Global && Devtia
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Desarrolla2\DoctrineMirrorBundle\Model;

class Item
{
    protected $id;
    protected $entity;
    protected $operation;
    protected $map;

    /**
     * @param mixed  $entity
     * @param string $operation
     * @param string $map
     */
    public function __construct($entity, string $operation, string $map)
    {
        $this->id = md5(spl_object_hash($entity));
        $this->entity = $entity;
        $this->operation = $operation;
        $this->map = $map;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return string
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * @return string
     */
    public function getMap(): string
    {
        return $this->map;
    }
}
