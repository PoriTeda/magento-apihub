<?php

namespace Riki\Catalog\Stock;

class Pool implements \Iterator, \ArrayAccess
{
    protected $stocks;
    /**
     * @param array $stocks
     * @param \Iterator $target
     */
    public function __construct(
        array $stocks,
        \Iterator $target = null
    ) {
        $this->stocks = $stocks;
        foreach ($target ?: [] as $code => $class) {
            if (empty($this->stocks[$code])) {
                $this->stocks[$code] = $class;
            }
        }
    }

    /**
     * Reset the Collection to the first element
     *
     * @return mixed
     */
    public function rewind()
    {
        return reset($this->stocks);
    }

    /**
     * Return the current element
     *
     * @return mixed
     */
    public function current()
    {
        return current($this->stocks);
    }

    /**
     * Return the key of the current element
     *
     * @return string
     */
    public function key()
    {
        return key($this->stocks);
    }

    /**
     * Move forward to next element
     *
     * @return mixed
     */
    public function next()
    {
        return next($this->stocks);
    }

    /**
     * Checks if current position is valid
     *
     * @return bool
     */
    public function valid()
    {
        return (bool)$this->key();
    }

    /**
     * Returns stock class by code
     *
     * @param string $code
     * @return string
     */
    public function get($code)
    {
        return $this->stocks[$code];
    }

    /**
     * The value to set.
     *
     * @param string $offset
     * @param string $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->stocks[] = $value;
        } else {
            $this->stocks[$offset] = $value;
        }
    }

    /**
     * The return value will be casted to boolean if non-boolean was returned.
     *
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->stocks[$offset]);
    }

    /**
     * The offset to unset.
     *
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->stocks[$offset]);
    }

    /**
     * The offset to retrieve.
     *
     * @param string $offset
     * @return string
     */
    public function offsetGet($offset)
    {
        return isset($this->stocks[$offset]) ? $this->stocks[$offset] : null;
    }
}
