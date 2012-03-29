<?php

namespace IterTools;

/**
 * The iterator wrapper used by all the above functions.
 */
class IterWrapper implements \Iterator {
    protected $inner;
    protected $callable;
    public function __construct($input) {
        if (!is_array($input) && !($input instanceof \Traversable)) {
            throw new \InvalidArgumentException("IterWrapper needs its argument to be traversable (array or implement Traversable).");
        }
        if (is_array($input)) {
            $input = new \ArrayIterator($input);
        }
        $this->inner = $input;
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

/**
 * The iterator returned by the map() function.
 */
class MapIterator extends IterWrapper {
    public function __construct($callable, $input) {
        parent::__construct($input);
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException("IterWrapper needs the first argument to be a callable.");
        }
        $this->callable = $callable;
    }
    public function current() {
        $m = $this->callable;
        return $m(parent::current());
    }
}

/**
 * The iterator returned by the mapxy() function.
 */
class MapXYIterator extends MapIterator {
    public function current() {
        $m = $this->callable;
        return $m($this->key(), parent::current());
    }
}

/**
 * The iterator returned by the merge() function.
 */
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

/**
 * The iterator returned by the flip() function.
 */
class FlipIterator extends IterWrapper {
    public function current() {
        return parent::key();
    }
    public function key() {
        return parent::current();
    }
}

/**
 * The iterator returned by the filter() function.
 */
class FilterIterator extends MapIterator {
    public function current() {
        $m = $this->callable;
        while (($c = IterWrapper::current()) && $this->valid()) {
            if ($m($c))
                return $c;
            $this->next();
        }
    }
}

/**
 * The iterator returned by the keys() function.
 */
class KeyIterator extends IterWrapper {
    private $position = 0;
    public function rewind() {
        $this->position = 0;
        return parent::rewind();
    }

    public function next() {
        $this->position++;
        return parent::next();
    }

    public function key() {
        return $this->position;
    }

    public function current() {
        return $this->inner->key();
    }
}

/**
 * The iterator returned by the vallues() function.
 */
class ValueIterator extends IterWrapper {
    private $position = 0;
    public function rewind() {
        $this->position = 0;
        return parent::rewind();
    }

    public function next() {
        $this->position++;
        return parent::next();
    }

    public function key() {
        return $this->position;
    }
}


/**
 * Given an array or a Traversable, returns an Iterator
 * which applies $callable to each value of $input and
 * returns the result.
 *
 * The $callable is expected to accept a single argument.
 * 
 * If any of parameter is of wrong type, InvalidArgumentException is thrown.
 *
 * @param callable $callable
 * @param array|Traversable $input
 * @return MapIterator
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

/**
 * Given an array or a Traversable, returns an Iterator
 * which applies $callable to each key/value pair of $input and
 * returns the result.
 *
 * The $callable is expected to accept two arguments, the first a key and
 * the second a value.
 * 
 * If any of parameter is of wrong type, InvalidArgumentException is thrown.
 *
 * @param callable $callable
 * @param array|Traversable $input
 * @return MapXYIterator
 * @throws InvalidArgumentException
 *
 */
function mapxy ($callable, $input) {
    if (!is_callable($callable)) {
        throw new \InvalidArgumentException("map needs the first argument to be a callable.");
    }
    if (!is_array($input) && !($input instanceof \Traversable)) {
        throw new \InvalidArgumentException("map needs the second argument to be traversable (array or implement Traversable).");
    }
    return new MapXYIterator($callable, $input);
}

/**
 * Given an array or a Traversable, returns an Iterator
 * which applies $callable to each value of $input and
 * returns only those values for which the $callable returns true.
 *
 * The $callable is expected to accept a single argument.
 * 
 * If any of parameter is of wrong type, InvalidArgumentException is thrown.
 *
 * @param callable $callable
 * @param array|Traversable $input
 * @return IterTools\FilterIterator
 * @throws InvalidArgumentException
 */
function filter ($callable, $input) {
    if (!is_callable($callable)) {
        throw new \InvalidArgumentException("filter needs the first argument to be a callable.");
    }
    if (!is_array($input) && !($input instanceof \Traversable)) {
        throw new \InvalidArgumentException("filter needs the second argument to be traversable (array or implement Traversable).");
    }
    return new FilterIterator($callable, $input);
}

/**
 * Given an array or a Traversable and an optional initial value,
 * iterates over $input, applying $callable to the previous iteration
 * result and the current value, returning the last result.
 *
 * The $callable is expected to accept two arguments.
 * 
 * If any of parameter is of wrong type, InvalidArgumentException is thrown.
 *
 * Do not apply this function to iterators which never finish, 
 * it will do you no good.
 *
 * @param callable $callable
 * @param array|Traversable $input
 * @param mixed $initial
 * @return mixed
 * @throws InvalidArgumentException
 *
 */
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

/**
 * Given an arbitrary number of Traversable/arrays as input, returns an Iterator
 * which iterates them one after another.
 * 
 * The toArray() method of this Iterator behaves in the way array_merge() function
 * does, renumbering the numeric keys and overwriting the non-numeric ones.
 * 
 * @return MergeIterator
 */
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
    return new MergeIterator($result);
}

function flip($argument) {
    return new FlipIterator($argument);
}

function keys($argument) {
    return new KeyIterator($argument);
}

function values($argument) {
    return new ValueIterator($argument);
}
