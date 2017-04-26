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
 * @Target("PROPERTY")
 */
class ManyToOne
{
    public $remoteRelatedEntity;

    public $remoteRelatedEntityId;

    public $remoteMethod;

    /**
     * @return mixed
     */
    public function getRemoteRelatedEntity()
    {
        return $this->remoteRelatedEntity;
    }

    /**
     * @return mixed
     */
    public function getRemoteMethod()
    {
        return $this->remoteMethod;
    }

    /**
     * @return mixed
     */
    public function getRemoteRelatedEntityId()
    {
        return $this->remoteRelatedEntityId;
    }
}
