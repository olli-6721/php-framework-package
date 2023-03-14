<?php

namespace Os\Framework\Kernel\Data\Collection;

use Os\Framework\Exception\FrameworkException;

abstract class Collection implements \Iterator
{
    protected bool $associated;
    protected int $iteratorPosition;

    public function __construct(protected array $items = []){
        $this->associated = false;
        $this->iteratorPosition = 0;
    }

    public function getElements(): array
    {
        return $this->items;
    }

    /**
     * @throws FrameworkException
     * Returns the key or index of the added object
     */
    public function add($value, string $key = null): string|int
    {
        if($key === null && $this->associated === true)
            throw new FrameworkException("Cannot add unassociated item if the previous list items where associated to an key");
        if($key !== null && $this->associated === false)
            throw new FrameworkException("Cannot add associated item if the previous list items where unassociated to an key");

        if($key === null){
            $this->items[] = $value;
            return count($this->items) - 1;
        }
        $this->items[$key] = $value;
        return $key;
    }

    public function remove(string|int $keyOrIndex): static
    {
        if(empty($this->items[$keyOrIndex])) return $this;
        unset($this->items[$keyOrIndex]);
        $this->items = array_values($this->items);
        return $this;
    }

    public function get(string|int $keyOrIndex){
        if(empty($this->items[$keyOrIndex])) return null;
        return $this->items[$keyOrIndex];
    }

    public function current(): mixed
    {
        return array_values($this->items)[$this->iteratorPosition];
    }

    public function next(): void
    {
        ++$this->iteratorPosition;
    }

    public function key(): mixed
    {
        return $this->iteratorPosition;
    }

    public function valid(): bool
    {
        return isset(array_values($this->items)[$this->iteratorPosition]);
    }

    public function rewind(): void
    {
        $this->iteratorPosition = 0;
    }
}