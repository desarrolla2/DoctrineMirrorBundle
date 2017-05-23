<?php

/*
 * This file is part of the Jotelulu package
 *
 * Copyright (c) 2017 Adder Global && Devtia
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Desarrolla2\DoctrineMirrorBundle\Handler;

use Desarrolla2\DoctrineMirrorBundle\Annotation as Mirror;
use Desarrolla2\DoctrineMirrorBundle\Mapper\MapperInterface;
use Desarrolla2\DoctrineMirrorBundle\Model\Collection;
use Desarrolla2\DoctrineMirrorBundle\Model\Item;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\Setup;

class MirrorHandler
{
    /**
     * @var AnnotationReader
     */
    private $reader;

    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var array
     */
    private $mappers = [];

    /**
     * @var array
     */
    private $mappedEntities = [];

    /**
     * @var array
     */
    private $connections = [];

    /**
     * @var array
     */
    private $connectionsNames = [];

    /**
     * @var array
     */
    private $entityManagers = [];

    /**
     * @var array
     */
    private $reflections = [
        'class' => [],
        'properties' => [],
        'methods' => [],
        'manyToOne' => [],
    ];

    /**
     * @param AnnotationReader $reader
     */
    public function __construct(AnnotationReader $reader)
    {
        $this->reader = $reader;
        $this->collection = new Collection();
    }

    /**
     * @param string          $entityName
     * @param MapperInterface $mapper
     */
    public function setMapper(string $entityName, MapperInterface $mapper)
    {
        $this->mappers[$entityName] = $mapper;
        $this->mappedEntities = array_keys($this->mappers);
    }

    /**
     * @param string $connName
     * @param        $conn
     */
    public function setConnection(string $connName, Connection $conn)
    {
        $this->connections[$connName] = $conn;
        $this->connectionsNames = array_keys($this->connections);
    }

    /**
     * @param object $entity
     */
    public function persist($entity)
    {
        $map = $this->getMap($entity);
        if (!$map) {
            return;
        }
        $this->collection->addItem(
            new Item($entity, Operation::PERSIST, $map)
        );
    }

    /**
     * @param object $entity
     */
    public function remove($entity)
    {
        $map = $this->getMap($entity);
        if (!$map) {
            return;
        }
        $this->collection->addItem(
            new Item(clone $entity, Operation::REMOVE, $map)
        );
    }

    public function flush()
    {
        if (!$this->collection->count()) {
            return;
        }
        $ems = [];

        $items = $this->collection->getItems();
        $this->collection->clear();

        foreach ($items as $item) {
            $em = $this->getEntityManager($item->getMap());
            $entity = $item->getEntity();
            $remote = $this->findRemoteEntity($em, $entity);

            if ($item->getOperation() == Operation::PERSIST) {
                $this->copyEntity($em, $entity, $remote);
                $em->persist($remote);
                $ems[spl_object_hash($em)] = $em;
                continue;
            }

            if ($item->getOperation() == Operation::REMOVE) {
                $em->remove($remote);
                $ems[spl_object_hash($em)] = $em;
                continue;
            }
        }

        foreach ($ems as $em) {
            $em->flush();
        }
    }

    /**
     * @param object $entity
     *
     * @return bool
     */
    protected function getMap($entity)
    {
        $class = $this->getClass($entity);

        if (!in_array($class, $this->mappedEntities)) {
            return false;
        }

        return $this->mappers[$class]->map($entity);
    }

    /**
     * @param string $name
     *
     * @return EntityManager
     */
    protected function getEntityManager($name)
    {
        if (!in_array($name, $this->entityManagers)) {
            $conn = $this->getConnection($name);
            $paths = [];
            foreach ($this->mappedEntities as $mappedEntity) {
                $mirrorEntity = $this->getMirrorEntityAnnotation($mappedEntity);
                $reflector = new \ReflectionClass($mirrorEntity->getRemoteEntity());
                $directory = dirname($reflector->getFileName());

                $paths[$directory] = $directory;
            }

            $paths = array_values($paths);

            $config = Setup::createConfiguration(false);
            $driver = new AnnotationDriver(new AnnotationReader(), $paths);
            AnnotationRegistry::registerLoader('class_exists');
            $config->setMetadataDriverImpl($driver);

            $this->entityManagers[$name] = EntityManager::create($conn, $config);
        }

        return $this->entityManagers[$name];
    }

    /**
     * @param string $name
     *
     * @return Connection
     */
    protected function getConnection($name)
    {
        if (!in_array($name, $this->connectionsNames)) {
            throw new \RuntimeException(
                'Connection with name "%s" not found. Available connections: %s',
                $name,
                implode(', ', $this->connectionsNames)
            );
        }

        return $this->connections[$name];
    }

    /**
     * @param EntityManager $em
     * @param object        $entity
     * @param object        $remoteEntity
     */
    protected function copyEntity(EntityManager $em, $entity, $remoteEntity)
    {
        $this->copyProperties($entity, $remoteEntity);
        $this->copyMethods($entity, $remoteEntity);
        $this->copyManyToOne($em, $entity, $remoteEntity);
    }

    /**
     * @param $entity
     * @param $remoteEntity
     */
    protected function copyProperties($entity, $remoteEntity)
    {
        $annotations = $this->getMirrorPropertiesAnnotation($entity);
        foreach ($annotations as $getter => $setter) {
            $remoteEntity->$setter($entity->$getter());
        }
    }

    /**
     * @param $entity
     * @param $remoteEntity
     */
    protected function copyMethods($entity, $remoteEntity)
    {
        $annotations = $this->getMirrorMethodsAnnotation($entity);
        foreach ($annotations as $getter => $setter) {
            $remoteEntity->$setter($entity->$getter());
        }
    }

    /**
     * @param EntityManager $em
     * @param object        $entity
     * @param object        $remoteEntity
     */
    protected function copyManyToOne(EntityManager $em, $entity, $remoteEntity)
    {
        $annotations = $this->getMirrorManyToOneAnnotation($entity);
        foreach ($annotations as $annotation) {
            $getter = $annotation['getter'];
            $setter = $annotation['setter'];
            $localRelation = $entity->$getter();
            if (!$localRelation) {
                $remoteEntity->$setter(null);
                continue;
            }
            $remoteEntityRelated = $em->getRepository($annotation['remoteRelatedClass'])->findOneBy(
                [$annotation['remoteEntityEntityId'] => $localRelation->getId()]
            );
            if (!$remoteEntityRelated) {
                $remoteEntity->$setter(null);
                continue;
            }

            $remoteEntity->$setter($remoteEntityRelated->getId());
        }
    }

    /**
     * @param EntityManager $em
     * @param object        $entity
     *
     * @return null|object
     */
    protected function findRemoteEntity(EntityManager $em, $entity)
    {
        $mirrorEntity = $this->getMirrorEntityAnnotation($entity);
        $remoteEntityName = $mirrorEntity->getRemoteEntity();
        $remoteIdProperty = $mirrorEntity->getRemoteIdProperty();
        $id = $entity->getId();

        $setter = 'set'.$remoteIdProperty;
        if (!$id) {
            throw new \RuntimeException(
                sprintf('Not persisted entities is not supported "%s"', $this->getClass($entity))
            );
        }
        $remoteEntity = $em->getRepository($remoteEntityName)->findOneBy([$remoteIdProperty => $id]);
        if ($remoteEntity) {
            return $remoteEntity;
        }
        $remoteEntity = new $remoteEntityName();
        $remoteEntity->$setter($id);

        return $remoteEntity;
    }

    /**
     * @param object $entity
     *
     * @return Mirror\Entity
     */
    protected function getMirrorEntityAnnotation($entity): Mirror\Entity
    {
        $reflection = $this->getReflectionClass($entity);

        /** @var Mirror\Entity $mirrorEntity */
        $mirrorEntity = $this->reader->getClassAnnotation($reflection, Mirror\Entity::class);
        if (!$mirrorEntity) {
            throw new \RuntimeException(
                sprintf(
                    'Mapped class "%s" has not required "%s" annotations',
                    $this->getClass($entity),
                    Mirror\Entity::class
                )
            );
        }

        return $mirrorEntity;
    }

    /**
     * @param object $entity
     *
     * @return array
     */
    public function getMirrorPropertiesAnnotation($entity)
    {
        $entityClass = $this->getClass($entity);
        if (!in_array($entityClass, $this->reflections['properties'])) {
            $reflection = $this->getReflectionClass($entity);
            $annotations = [];
            foreach ($reflection->getProperties() as $reflectionProperty) {
                $propertyAnnotation = $this->reader->getPropertyAnnotation($reflectionProperty, Mirror\Property::class);
                if (!$propertyAnnotation) {
                    continue;
                }

                $getter = 'get'.ucfirst($reflectionProperty->getName());
                $setter = 'set'.ucfirst($propertyAnnotation->getName());

                $annotations[$getter] = $setter;
            }
            $this->reflections['properties'][$entityClass] = $annotations;
        }

        return $this->reflections['properties'][$entityClass];
    }

    /**
     * @param object $entity
     *
     * @return array
     */
    public function getMirrorManyToOneAnnotation($entity)
    {
        $entityClass = $this->getClass($entity);
        if (!in_array($entityClass, $this->reflections['manyToOne'])) {
            $reflection = $this->getReflectionClass($entity);
            $annotations = [];
            foreach ($reflection->getProperties() as $reflectionProperty) {
                /** @var Mirror\ManyToOne $propertyAnnotation */
                $propertyAnnotation = $this->reader->getPropertyAnnotation(
                    $reflectionProperty,
                    Mirror\ManyToOne::class
                );
                if (!$propertyAnnotation) {
                    continue;
                }
                $annotations[] = [
                    'getter' => 'get'.ucfirst($reflectionProperty->getName()),
                    'setter' => $propertyAnnotation->getRemoteMethod(),
                    'remoteRelatedClass' => $propertyAnnotation->getRemoteRelatedEntity(),
                    'remoteEntityEntityId' => $propertyAnnotation->getRemoteRelatedEntityId(),
                    'remoteMethod' => $propertyAnnotation->getRemoteMethod(),
                ];
            }
            $this->reflections['manyToOne'][$entityClass] = $annotations;
        }

        return $this->reflections['manyToOne'][$entityClass];
    }

    /**
     * @param object $entity
     *
     * @return array
     */
    public function getMirrorMethodsAnnotation($entity)
    {
        $entityClass = $this->getClass($entity);
        if (!in_array($entityClass, $this->reflections['methods'])) {
            $reflection = $this->getReflectionClass($entity);
            $annotations = [];
            foreach ($reflection->getMethods() as $reflectionMethod) {
                $methodAnnotation = $this->reader->getMethodAnnotation($reflectionMethod, Mirror\Method::class);
                if (!$methodAnnotation) {
                    continue;
                }

                $getter = $reflectionMethod->getName();
                $setter = 'set'.ucfirst($methodAnnotation->getName());

                $annotations[$getter] = $setter;
            }
            $this->reflections['methods'][$entityClass] = $annotations;
        }

        return $this->reflections['methods'][$entityClass];
    }

    /**
     * @param $entity
     *
     * @return \ReflectionClass
     */
    protected function getReflectionClass($entity)
    {
        if (!is_string($entity)) {
            return $this->getReflectionClass($this->getClass($entity));
        }
        if (!in_array($entity, $this->reflections['class'])) {
            $this->reflections['class'][$entity] = new \ReflectionClass($entity);
        }

        return $this->reflections['class'][$entity];
    }

    /**
     * @param mixed $entity
     *
     * @return string
     */
    protected function getClass($entity)
    {
        return str_replace('Proxies\\__CG__\\', '', get_class($entity));
    }
}
