<?php

/*
 * This file is part of the Jotelulu package
 *
 * Copyright (c) 2017 Adder Global && Devtia
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Desarrolla2\DoctrineMirrorBundle\Mapper;

class AlwaysMapper implements MapperInterface
{
    protected $connection;

    /**
     * @param $connection
     */
    public function __construct($connection = false)
    {
        $this->connection = $connection;
    }

    public function map($entity)
    {
        return $this->connection;
    }
}
