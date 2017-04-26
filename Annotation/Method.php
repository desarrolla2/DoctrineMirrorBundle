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

/**
 * @Annotation
 * @Target("METHOD")
 */
class Method
{
    public $name;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
