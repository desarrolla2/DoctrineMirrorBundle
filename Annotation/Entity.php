<?php

/*
 * This file is part of the Jotelulu package
 *
 * Copyright (c) 2017 Adder Global && Devtia
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Desarrolla2\DoctrineMirrorBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Entity
{
    public $remoteIdProperty;

    public $remoteEntity;

    /**
     * @return mixed
     */
    public function getRemoteIdProperty(): string
    {
        return $this->remoteIdProperty;
    }

    /**
     * @return mixed
     */
    public function getRemoteEntity(): string
    {
        return $this->remoteEntity;
    }
}
