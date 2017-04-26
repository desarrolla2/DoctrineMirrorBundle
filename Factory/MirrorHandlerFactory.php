<?php

/*
 * This file is part of the Jotelulu package
 *
 * Copyright (c) 2017 Adder Global && Devtia
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Desarrolla2\DoctrineMirrorBundle\Factory;

use Desarrolla2\DoctrineMirrorBundle\Handler\MirrorHandler;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MirrorHandlerFactory
{
    private $container;

    private $mappers;

    private $connections;

    /**
     * @param ContainerInterface $container
     * @param array              $mappers
     * @param array              $connections
     */
    public function __construct(ContainerInterface $container, array $mappers, array $connections)
    {
        $this->container = $container;
        $this->mappers = $mappers;
        $this->connections = $connections;
    }

    public function create()
    {
        $handler = new MirrorHandler($this->container->get('doctrine_mirror.annotation_reader'));

        foreach ($this->container->getParameter('doctrine_mirror.mappers') as $entityName => $mapper) {
            $handler->setMapper($entityName, $this->container->get($mapper));
        }

        foreach ($this->container->getParameter('doctrine_mirror.connections') as $connectionName => $data) {
            $data['driver'] = 'pdo_mysql';
            $conn = DriverManager::getConnection($data, new \Doctrine\DBAL\Configuration());
            $handler->setConnection($connectionName, $conn);
        }

        return $handler;
    }
}
