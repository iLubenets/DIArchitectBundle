<?php

namespace iLubenets\DIArchitectBundle\Classes;

/**
 * Reference storage
 *
 * @package iLubenets\DIArchitectBundle\Classes
 */
class IndexBag
{
    const UNKNOWN_VALUE = 'UNKNOWN';

    /**
     * @var array
     */
    private $items = [];
    /**
     * @var array
     */
    private $itemIndexes = [];

    /**
     * Add an element
     * @param      $key
     * @param null $value
     *
     * @return int - index of the added element
     */
    public function addItem($key, $value = null)
    {
        if (array_key_exists($key, $this->items)) {
            return $this->getItemIndex($key);
        }

        $index = count($this->items);
        $this->items[$key] = $value;
        $this->itemIndexes[$key] = $index;
        return $index;
    }

    /**
     * Get element index
     * @param $key
     * @return mixed
     */
    public function getItemIndex($key)
    {
        return isset($this->itemIndexes[$key]) ? $this->itemIndexes[$key] : self::UNKNOWN_VALUE;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array_values($this->items);
    }

}