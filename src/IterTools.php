<?php

namespace IterTools;

class IterWrapper implements \Iterator {
    protected $inner;
    protected $callable;
    public function __construct($callable, $input) {
        if (!is_array($input) && !($input instanceof \Traversable)) {
            throw new \InvalidArgumentException("IterWrapper needs its argument to be traversable (array or implement Traversable).");
        }
        if (is_array($input)) {
            $input = new \ArrayIterator($input);
        }
        $this->inner = $input;
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException("IterWrapper needs the first argument to be a callable.");
        }
        $this->callable = $callable;
    }

    public function next() {
        return $this->inner->next();
    }

    public function rewind() {
        return $this->inner->rewind();
    }

    public function valid() {
        return $this->inner->valid();
    }

    public function current() {
        return $this->inner->current();
    }

    public function key() {
        return $this->inner->key();
    }

    public function getInner() {
        return $this->inner;
    }

    public function toArray() {
        $result = array();
        foreach ($this as $k => $value) {
            $result[$k] = $value;
        }
        return $result;
    }
}

class MapIterator extends IterWrapper {
    public function current() {
        $m = $this->callable;
        return $m(parent::current());
    }
}

class MapXYIterator extends IterWrapper {
    public function current() {
        $m = $this->callable;
        return $m($this->key(), parent::current());
    }
}

class MergeIterator extends IterWrapper {
    public function toArray() {
        $result = array();
        foreach($this as $k => $value) {
            if(is_numeric($k) && array_key_exists($k, $result)) {
                $result[] = $value;
            } else {
                $result[$k] = $value;
            }
        }
        return $result;
    }
}

class FilterIterator extends IterWrapper {
    public function current() {
        $m = $this->callable;
        while (($c = parent::current()) && $this->valid()) {
            if ($m($c))
                return $c;
            $this->next();
        }
    }
}


/**
 * Given an array or a Traversable, returns an Iterator
 * which applies $callable to each element of $input and
 * returns the result.
 *
 * If any of parameter is of wrong type, InvalidArgumentException is thrown.
 *
 * @param callable $callable
 * @param array|Traversable $input
 * @return IterWrapper
 * @throws InvalidArgumentException
 *
 */
function map ($callable, $input) {
    if (!is_callable($callable)) {
        throw new \InvalidArgumentException("map needs the first argument to be a callable.");
    }
    if (!is_array($input) && !($input instanceof \Traversable)) {
        throw new \InvalidArgumentException("map needs the second argument to be traversable (array or implement Traversable).");
    }
    return new MapIterator($callable, $input);
}

function mapxy ($callable, $input) {
    if (!is_callable($callable)) {
        throw new \InvalidArgumentException("map needs the first argument to be a callable.");
    }
    if (!is_array($input) && !($input instanceof \Traversable)) {
        throw new \InvalidArgumentException("map needs the second argument to be traversable (array or implement Traversable).");
    }
    return new MapXYIterator($callable, $input);
}

function filter ($callable, $input) {
    if (!is_callable($callable)) {
        throw new \InvalidArgumentException("filter needs the first argument to be a callable.");
    }
    if (!is_array($input) && !($input instanceof \Traversable)) {
        throw new \InvalidArgumentException("filter needs the second argument to be traversable (array or implement Traversable).");
    }
    return new FilterIterator($callable, $input);
}

function reduce($callable, $input, $initial = null) {
    if (!is_callable($callable)) {
        throw new \InvalidArgumentException("reduce needs the first argument to be a callable.");
    }
    if (!is_array($input) && !($input instanceof \Traversable)) {
        throw new \InvalidArgumentException("reduce needs the second argument to be traversable (array or implement Traversable).");
    }
    reset($input);
    $result = $initial;
    foreach($input as $y) {
        $result = $callable($result, $y);
    }
    return $result;
}

function merge() {
    $arguments = func_get_args();
    foreach($arguments as $input) {
        if (!is_array($input) && !($input instanceof \Traversable)) {
            throw new \InvalidArgumentException("merge() needs its arguments to be traversable (array or implement Traversable).");
        }
    }
    $result = new \AppendIterator();
    foreach($arguments as $x) {
       if (is_array($x)) {
           $x = new \ArrayIterator($x);
       }
       $result->append($x);
    }
    return new MergeIterator(function ($x) {return $x;}, $result);
}
