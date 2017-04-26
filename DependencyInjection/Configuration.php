<?php

/*
 * This file is part of the Jotelulu package
 *
 * Copyright (c) 2017 Adder Global && Devtia
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Desarrolla2\DoctrineMirrorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('doctrine_mirror');
        $rootNode
                ->children()
                    ->booleanNode('active')
                        ->defaultTrue()
                    ->end()
                ->end()
                ->children()
                    ->arrayNode('mappers')
                        ->prototype('scalar')
                        ->end()
                        ->isRequired()
                    ->end()
                ->end()
                ->children()
                    ->arrayNode('connections')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('dbname')->end()
                                ->scalarNode('host')->end()
                                ->scalarNode('port')->defaultNull()->end()
                                ->scalarNode('user')->end()
                                ->scalarNode('password')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
