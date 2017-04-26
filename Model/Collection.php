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

class Collection implements \Countable
{
    protected $items = [];

    /**
     * @param array $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function count()
    {
        return count($this->items);
    }

    /**
     * @param string $operation
     *
     * @return Item[]
     */
    public function filter($operation)
    {
        return array_filter(
            $this->items,
            function ($item) use ($operation) {
                /* @var Item $item */
                return $item->getOperation() == $operation;
            }
        );
    }

    /**
     * @param Item $item
     */
    public function addItem(Item $item)
    {
        $this->items[$item->getId()] = $item;
    }

    /**
     * @return Item[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array $items
     */
    public function addItems(array $items)
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }
    }

    /**
     * @param array $items
     */
    public function setItems(array $items)
    {
        $this->clear();
        foreach ($items as $item) {
            $this->addItem($item);
        }
    }

    public function clear()
    {
        $this->items = [];
    }
}
