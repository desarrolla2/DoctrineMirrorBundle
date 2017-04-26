<?php

/*
 * This file is part of the Jotelulu package
 *
 * Copyright (c) 2017 Adder Global && Devtia
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Desarrolla2\DoctrineMirrorBundle\EventSubscriber;

use Desarrolla2\DoctrineMirrorBundle\Handler\MirrorHandler;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;

class EntitySubscriber implements EventSubscriber
{
    /** @var MirrorHandler */
    private $handler;

    /** @var bool */
    private $enabled;

    /**
     * @param MirrorHandler $handler
     * @param bool          $enabled
     */
    public function __construct(MirrorHandler $handler, bool $enabled = true)
    {
        $this->handler = $handler;
        $this->enabled = $enabled;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        if (!$this->isEnabled()) {
            return;
        }
        $entity = $args->getObject();
        $this->handler->persist($entity);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        if (!$this->isEnabled()) {
            return;
        }
        $entity = $args->getObject();
        $this->handler->persist($entity);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        if (!$this->isEnabled()) {
            return;
        }
        $entity = $args->getObject();
        $this->handler->remove($entity);
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if (!$this->isEnabled()) {
            return;
        }
        $this->handler->flush();
    }

    public function enable()
    {
        $this->enabled = true;
    }

    public function disable()
    {
        $this->enabled = false;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            Events::postUpdate,
            Events::postPersist,
            Events::postFlush,
            Events::preRemove,
        ];
    }
}
