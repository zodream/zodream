<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Support;

use Countable;
use ArrayAccess;
use ArrayIterator;
use CachingIterator;
use JsonSerializable;
use IteratorAggregate;
use InvalidArgumentException;
use Traversable;
use Zodream\Infrastructure\Concerns\Macroable;
use Zodream\Infrastructure\Contracts\ArrayAble;
use Zodream\Infrastructure\Contracts\JsonAble;
use Zodream\Helpers\Arr;
use Zodream\Helpers\Str;

class Collection implements ArrayAccess, ArrayAble, Countable, IteratorAggregate, JsonAble, JsonSerializable {
    use Macroable;
    /**
     * The items contained in the collection.
     *
     * @var array
     */
    protected array $items = [];
    /**
     * Create a new collection.
     *
     * @param  mixed  $items
     */
    public function __construct(mixed $items = []) {
        $this->items = $this->getArrayAbleItems($items);
    }
    /**
     * Create a new collection instance if the value isn't one already.
     *
     * @param  mixed  $items
     * @return static
     */
    public static function make(mixed $items = []) {
        return new static($items);
    }
    /**
     * Get all of the items in the collection.
     *
     * @return array
     */
    public function all(): array {
        return $this->items;
    }
    /**
     * Get the average value of a given key.
     *
     * @param  callable|string|null  $callback
     * @return integer
     */
    public function avg(mixed $callback = null): int {
        if ($count = $this->count()) {
            return $this->sum($callback) / $count;
        }
        return 0;
    }
    /**
     * Alias for the "avg" method.
     *
     * @param  callable|string|null  $callback
     * @return mixed
     */
    public function average(mixed $callback = null): mixed {
        return $this->avg($callback);
    }
    /**
     * Get the median of a given key.
     *
     * @param  null $key
     * @return mixed|null
     */
    public function median(mixed $key = null) {
        $count = $this->count();
        if ($count == 0) {
            return null;
        }
        $arg = isset($key) ? $this->pluck($key) : $this;
        $values = $arg->sort()->values();
        $middle = (int) ($count / 2);
        if ($count % 2) {
            return $values->get($middle);
        }
        return (new static([
            $values->get($middle - 1), $values->get($middle),
        ]))->average();
    }
    /**
     * Get the mode of a given key.
     *
     * @param  mixed  $key
     * @return array
     */
    public function mode(mixed $key = null): array {
        $count = $this->count();
        if ($count == 0) {
            return [];
        }
        $collection = isset($key) ? $this->pluck($key) : $this;
        $counts = new self;
        $collection->each(function ($value) use ($counts) {
            $counts[$value] = isset($counts[$value]) ? $counts[$value] + 1 : 1;
        });
        $sorted = $counts->sort();
        $highestValue = $sorted->last();
        return $sorted->filter(function ($value) use ($highestValue) {
            return $value == $highestValue;
        })->sort()->keys()->all();
    }
    /**
     * Collapse the collection of items into a single array.
     *
     * @return static
     */
    public function collapse() {
        return new static(Arr::collapse($this->items));
    }
    /**
     * Determine if an item exists in the collection.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return bool
     */
    public function contains(mixed $key, mixed $value = null): bool {
        if (func_num_args() == 2) {
            return $this->contains(function ($item) use ($key, $value) {
                return Arr::dataGet($item, $key) == $value;
            });
        }
        if ($this->useAsCallable($key)) {
            return ! is_null($this->first($key));
        }
        return in_array($key, $this->items);
    }
    /**
     * Determine if an item exists in the collection using strict comparison.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return bool
     */
    public function containsStrict(mixed $key, mixed $value = null): bool {
        if (func_num_args() == 2) {
            return $this->contains(function ($item) use ($key, $value) {
                return Arr::dataGet($item, $key) === $value;
            });
        }
        if ($this->useAsCallable($key)) {
            return !is_null($this->first($key));
        }
        return in_array($key, $this->items, true);
    }
    /**
     * Get the items in the collection that are not present in the given items.
     *
     * @param  mixed  $items
     * @return static
     */
    public function diff(mixed $items) {
        return new static(array_diff($this->items, $this->getArrayAbleItems($items)));
    }
    /**
     * Get the items in the collection whose keys are not present in the given items.
     *
     * @param  mixed  $items
     * @return static
     */
    public function diffKeys(mixed $items) {
        return new static(array_diff_key($this->items, $this->getArrayAbleItems($items)));
    }
    /**
     * Execute a callback over each item.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function each(callable $callback): static {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }
        return $this;
    }
    /**
     * Create a new collection consisting of every n-th element.
     *
     * @param  int  $step
     * @param  int  $offset
     * @return static
     */
    public function every(int $step, int $offset = 0): static {
        $new = [];
        $position = 0;
        foreach ($this->items as $item) {
            if ($position % $step === $offset) {
                $new[] = $item;
            }
            $position++;
        }
        return new static($new);
    }
    /**
     * Get all items except for those with the specified keys.
     *
     * @param  mixed  $keys
     * @return static
     */
    public function except(mixed $keys): static {
        $keys = is_array($keys) ? $keys : func_get_args();
        return new static(Arr::except($this->items, $keys));
    }
    /**
     * Run a filter over each of the items.
     *
     * @param  callable|null  $callback
     * @return static
     */
    public function filter(callable|null $callback = null): static {
        if ($callback) {
            return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
        }
        return new static(array_filter($this->items));
    }
    /**
     * Filter items by the given key value pair.
     *
     * @param  string  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return static
     */
    public function where(mixed $key, mixed $operator, mixed $value = null) {
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->filter($this->operatorForWhere($key, $operator, $value));
    }
    /**
     * Get an operator checker callback.
     *
     * @param  string  $key
     * @param  string  $operator
     * @param  mixed  $value
     * @return \Closure
     */
    protected function operatorForWhere(mixed $key, mixed $operator, mixed $value) {
        return function ($item) use ($key, $operator, $value) {
            $retrieved = Arr::dataGet($item, $key);
            switch ($operator) {
                default:
                case '=':
                case '==':  return $retrieved == $value;
                case '!=':
                case '<>':  return $retrieved != $value;
                case '<':   return $retrieved < $value;
                case '>':   return $retrieved > $value;
                case '<=':  return $retrieved <= $value;
                case '>=':  return $retrieved >= $value;
                case '===': return $retrieved === $value;
                case '!==': return $retrieved !== $value;
            }
        };
    }
    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return static
     */
    public function whereStrict(mixed $key, mixed $value) {
        return $this->where($key, '===', $value);
    }
    /**
     * Filter items by the given key value pair.
     *
     * @param  string  $key
     * @param  mixed  $values
     * @param  bool  $strict
     * @return static
     */
    public function whereIn(mixed $key, mixed $values, bool $strict = false) {
        $values = $this->getArrayAbleItems($values);
        return $this->filter(function ($item) use ($key, $values, $strict) {
            return in_array(Arr::dataGet($item, $key), $values, $strict);
        });
    }
    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * @param  string  $key
     * @param  mixed  $values
     * @return static
     */
    public function whereInStrict(mixed $key, mixed $values) {
        return $this->whereIn($key, $values, true);
    }
    /**
     * Get the first item from the collection.
     *
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public function first(callable $callback = null, mixed $default = null) {
        return Arr::first($this->items, $callback, $default);
    }
    /**
     * Get a flattened array of the items in the collection.
     *
     * @param  int  $depth
     * @return static
     */
    public function flatten(int $depth = INF) {
        return new static(Arr::flatten($this->items, $depth));
    }
    /**
     * Flip the items in the collection.
     *
     * @return static
     */
    public function flip() {
        return new static(array_flip($this->items));
    }
    /**
     * Remove an item from the collection by key.
     *
     * @param  string|array  $keys
     * @return $this
     */
    public function forget(mixed $keys) {
        foreach ((array) $keys as $key) {
            $this->offsetUnset($key);
        }
        return $this;
    }
    /**
     * Get an item from the collection by key.
     *
     * @param  mixed  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get(mixed $key, mixed $default = null) {
        if ($this->offsetExists($key)) {
            return $this->items[$key];
        }
        return Str::value($default);
    }
    /**
     * Group an associative array by a field or using a callback.
     *
     * @param  callable|string  $groupBy
     * @param  bool  $preserveKeys
     * @return static
     */
    public function groupBy(mixed $groupBy, bool $preserveKeys = false) {
        $groupBy = $this->valueRetriever($groupBy);
        $results = [];
        foreach ($this->items as $key => $value) {
            $groupKeys = $groupBy($value, $key);
            if (! is_array($groupKeys)) {
                $groupKeys = [$groupKeys];
            }
            foreach ($groupKeys as $groupKey) {
                if (! array_key_exists($groupKey, $results)) {
                    $results[$groupKey] = new static;
                }
                $results[$groupKey]->offsetSet($preserveKeys ? $key : null, $value);
            }
        }
        return new static($results);
    }
    /**
     * Key an associative array by a field or using a callback.
     *
     * @param  callable|string  $keyBy
     * @return static
     */
    public function keyBy(mixed $keyBy) {
        $keyBy = $this->valueRetriever($keyBy);
        $results = [];
        foreach ($this->items as $key => $item) {
            $resolvedKey = $keyBy($item, $key);
            if (is_object($resolvedKey)) {
                $resolvedKey = (string) $resolvedKey;
            }
            $results[$resolvedKey] = $item;
        }
        return new static($results);
    }
    /**
     * Determine if an item exists in the collection by key.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function has(mixed $key): bool {
        return $this->offsetExists($key);
    }
    /**
     * Concatenate values of a given key as a string.
     *
     * @param  string  $value
     * @param  string  $glue
     * @return string
     */
    public function implode(mixed $value, null|string|array $glue = null) {
        $first = $this->first();
        if (is_array($first) || is_object($first)) {
            return implode($glue, $this->pluck($value)->all());
        }
        return implode($value, $this->items);
    }
    /**
     * Intersect the collection with the given items.
     *
     * @param  mixed  $items
     * @return static
     */
    public function intersect(mixed $items) {
        return new static(array_intersect($this->items, $this->getArrayAbleItems($items)));
    }
    /**
     * Determine if the collection is empty or not.
     *
     * @return bool
     */
    public function isEmpty(): bool {
        return empty($this->items);
    }
    /**
     * Determine if the given value is callable, but not a string.
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function useAsCallable(mixed $value): bool {
        return ! is_string($value) && is_callable($value);
    }
    /**
     * Get the keys of the collection items.
     *
     * @return static
     */
    public function keys() {
        return new static(array_keys($this->items));
    }
    /**
     * Get the last item from the collection.
     *
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public function last(callable|null $callback = null, mixed $default = null) {
        return Arr::last($this->items, $callback, $default);
    }
    /**
     * Get the values of a given key.
     *
     * @param  string  $value
     * @param  string|null  $key
     * @return static
     */
    public function pluck(mixed $value, mixed $key = null) {
        return new static(Arr::pluck($this->items, $value, $key));
    }
    /**
     * Run a map over each of the items.
     *
     * @param  callable  $callback
     * @return static
     */
    public function map(callable $callback) {
        $keys = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);
        return new static(array_combine($keys, $items));
    }
    /**
     * Run an associative map over each of the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @param  callable  $callback
     * @return static
     */
    public function mapWithKeys(callable $callback) {
        return $this->flatMap($callback);
    }
    /**
     * Map a collection and flatten the result by a single level.
     *
     * @param  callable  $callback
     * @return static
     */
    public function flatMap(callable $callback) {
        return $this->map($callback)->collapse();
    }
    /**
     * Get the max value of a given key.
     *
     * @param  callable|string|null  $callback
     * @return mixed
     */
    public function max(mixed $callback = null) {
        $callback = $this->valueRetriever($callback);
        return $this->reduce(function ($result, $item) use ($callback) {
            $value = $callback($item);
            return is_null($result) || $value > $result ? $value : $result;
        });
    }
    /**
     * Merge the collection with the given items.
     *
     * @param  mixed  $items
     * @return static
     */
    public function merge(mixed $items) {
        return new static(array_merge($this->items, $this->getArrayAbleItems($items)));
    }
    /**
     * Create a collection by using this collection for keys and another for its values.
     *
     * @param  mixed  $values
     * @return static
     */
    public function combine(mixed $values) {
        return new static(array_combine($this->all(), $this->getArrayAbleItems($values)));
    }
    /**
     * Union the collection with the given items.
     *
     * @param  mixed  $items
     * @return static
     */
    public function union(mixed $items) {
        return new static($this->items + $this->getArrayAbleItems($items));
    }
    /**
     * Get the min value of a given key.
     *
     * @param  callable|string|null  $callback
     * @return mixed
     */
    public function min(mixed $callback = null) {
        $callback = $this->valueRetriever($callback);
        return $this->reduce(function ($result, $item) use ($callback) {
            $value = $callback($item);
            return is_null($result) || $value < $result ? $value : $result;
        });
    }
    /**
     * Get the items with the specified keys.
     *
     * @param  mixed  $keys
     * @return static
     */
    public function only(mixed $keys) {
        if (is_null($keys)) {
            return new static($this->items);
        }
        $keys = is_array($keys) ? $keys : func_get_args();
        return new static(Arr::only($this->items, $keys));
    }
    /**
     * "Paginate" the collection by slicing it into a smaller collection.
     *
     * @param  int  $page
     * @param  int  $perPage
     * @return static
     */
    public function forPage(int $page, int $perPage) {
        return $this->slice(($page - 1) * $perPage, $perPage);
    }
    /**
     * Partition the collection into two array using the given callback.
     *
     * @param  callable  $callback
     * @return array
     */
    public function partition(callable $callback) {
        $partitions = [new static(), new static()];
        foreach ($this->items as $item) {
            $partitions[! (int) $callback($item)][] = $item;
        }
        return $partitions;
    }
    /**
     * Pass the collection to the given callback and return the result.
     *
     * @param  callable $callback
     * @return mixed
     */
    public function pipe(callable $callback) {
        return $callback($this);
    }
    /**
     * Get and remove the last item from the collection.
     *
     * @return mixed
     */
    public function pop() {
        return array_pop($this->items);
    }
    /**
     * Push an item onto the beginning of the collection.
     *
     * @param  mixed  $value
     * @param  mixed  $key
     * @return $this
     */
    public function prepend(mixed $value, mixed $key = null) {
        $this->items = Arr::prepend($this->items, $value, $key);
        return $this;
    }
    /**
     * Push an item onto the end of the collection.
     *
     * @param  mixed  $value
     * @return $this
     */
    public function push(mixed $value) {
        $this->offsetSet(null, $value);
        return $this;
    }
    /**
     * Get and remove an item from the collection.
     *
     * @param  mixed  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function pull(mixed $key, mixed $default = null) {
        return Arr::pull($this->items, $key, $default);
    }
    /**
     * Put an item in the collection by key.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return $this
     */
    public function put(mixed $key, mixed $value) {
        $this->offsetSet($key, $value);
        return $this;
    }
    /**
     * Get one or more items randomly from the collection.
     *
     * @param  int  $amount
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function random(int $amount = 1) {
        if ($amount > ($count = $this->count())) {
            throw new InvalidArgumentException(
                __('You requested {amount} items, but there are only {count} items in the collection', [
                    'amount' => $amount,
                    'count' => $count
                ])
            );
        }
        $keys = array_rand($this->items, $amount);
        if ($amount == 1) {
            return $this->items[$keys];
        }
        return new static(array_intersect_key($this->items, array_flip($keys)));
    }
    /**
     * Reduce the collection to a single value.
     *
     * @param  callable  $callback
     * @param  mixed     $initial
     * @return mixed
     */
    public function reduce(callable $callback, mixed $initial = null) {
        return array_reduce($this->items, $callback, $initial);
    }
    /**
     * Create a collection of all elements that do not pass a given truth test.
     *
     * @param  callable|mixed  $callback
     * @return static
     */
    public function reject(mixed $callback) {
        if ($this->useAsCallable($callback)) {
            return $this->filter(function ($value, $key) use ($callback) {
                return ! $callback($value, $key);
            });
        }
        return $this->filter(function ($item) use ($callback) {
            return $item != $callback;
        });
    }
    /**
     * Reverse items order.
     *
     * @return static
     */
    public function reverse() {
        return new static(array_reverse($this->items, true));
    }
    /**
     * Search the collection for a given value and return the corresponding key if successful.
     *
     * @param  mixed  $value
     * @param  bool   $strict
     * @return mixed
     */
    public function search(mixed $value, bool $strict = false) {
        if (! $this->useAsCallable($value)) {
            return array_search($value, $this->items, $strict);
        }
        foreach ($this->items as $key => $item) {
            if (call_user_func($value, $item, $key)) {
                return $key;
            }
        }
        return false;
    }
    /**
     * Get and remove the first item from the collection.
     *
     * @return mixed
     */
    public function shift() {
        return array_shift($this->items);
    }
    /**
     * Shuffle the items in the collection.
     *
     * @param int $seed
     * @return static
     */
    public function shuffle(int|null $seed = null) {
        $items = $this->items;
        if (is_null($seed)) {
            shuffle($items);
        } else {
            srand($seed);
            usort($items, function () {
                return rand(-1, 1);
            });
        }
        return new static($items);
    }
    /**
     * Slice the underlying collection array.
     *
     * @param  int   $offset
     * @param  int   $length
     * @return static
     */
    public function slice(int $offset, int|null $length = null) {
        return new static(array_slice($this->items, $offset, $length, true));
    }
    /**
     * Split a collection into a certain number of groups.
     *
     * @param  int  $numberOfGroups
     * @return static
     */
    public function split(int $numberOfGroups) {
        if ($this->isEmpty()) {
            return new static;
        }
        $groupSize = ceil($this->count() / $numberOfGroups);
        return $this->chunk((int)$groupSize);
    }
    /**
     * Chunk the underlying collection array.
     *
     * @param  int   $size
     * @return static
     */
    public function chunk(int $size) {
        if ($size <= 0) {
            return new static;
        }
        $chunks = [];
        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = new static($chunk);
        }
        return new static($chunks);
    }
    /**
     * Sort through each item with a callback.
     *
     * @param  callable|null  $callback
     * @return static
     */
    public function sort(callable|null $callback = null) {
        $items = $this->items;
        $callback
            ? uasort($items, $callback)
            : asort($items);
        return new static($items);
    }
    /**
     * Sort the collection using the given callback.
     *
     * @param  callable|string  $callback
     * @param  int   $options
     * @param  bool  $descending
     * @return static
     */
    public function sortBy(mixed $callback, int $options = SORT_REGULAR, bool $descending = false) {
        $results = [];
        $callback = $this->valueRetriever($callback);
        // First we will loop through the items and get the comparator from a callback
        // function which we were given. Then, we will sort the returned values and
        // and grab the corresponding values for the sorted keys from this array.
        foreach ($this->items as $key => $value) {
            $results[$key] = $callback($value, $key);
        }
        $descending ? arsort($results, $options)
            : asort($results, $options);
        // Once we have sorted all of the keys in the array, we will loop through them
        // and grab the corresponding model so we can set the underlying items list
        // to the sorted version. Then we'll just return the collection instance.
        foreach (array_keys($results) as $key) {
            $results[$key] = $this->items[$key];
        }
        return new static($results);
    }
    /**
     * Sort the collection in descending order using the given callback.
     *
     * @param  callable|string  $callback
     * @param  int  $options
     * @return static
     */
    public function sortByDesc(mixed $callback, int $options = SORT_REGULAR) {
        return $this->sortBy($callback, $options, true);
    }
    /**
     * Splice a portion of the underlying collection array.
     *
     * @param  int  $offset
     * @param  int|null  $length
     * @param  mixed  $replacement
     * @return static
     */
    public function splice(int $offset, int|null $length = null, mixed $replacement = []) {
        if (func_num_args() == 1) {
            return new static(array_splice($this->items, $offset));
        }
        return new static(array_splice($this->items, $offset, $length, $replacement));
    }
    /**
     * Get the sum of the given values.
     *
     * @param  callable|string|null  $callback
     * @return mixed
     */
    public function sum(mixed $callback = null) {
        if (is_null($callback)) {
            return array_sum($this->items);
        }
        $callback = $this->valueRetriever($callback);
        return $this->reduce(function ($result, $item) use ($callback) {
            return $result + $callback($item);
        }, 0);
    }
    /**
     * Take the first or last {$limit} items.
     *
     * @param  int  $limit
     * @return static
     */
    public function take(int $limit) {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }
        return $this->slice(0, $limit);
    }
    /**
     * Transform each item in the collection using a callback.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function transform(callable $callback) {
        $this->items = $this->map($callback)->all();
        return $this;
    }
    /**
     * Return only unique items from the collection array.
     *
     * @param  string|callable|null  $key
     * @param  bool  $strict
     *
     * @return static
     */
    public function unique(mixed $key = null, bool $strict = false) {
        if (is_null($key)) {
            return new static(array_unique($this->items, SORT_REGULAR));
        }
        $key = $this->valueRetriever($key);
        $exists = [];
        return $this->reject(function ($item) use ($key, $strict, &$exists) {
            if (in_array($id = $key($item), $exists, $strict)) {
                return true;
            }
            $exists[] = $id;
            return false;
        });
    }
    /**
     * Return only unique items from the collection array using strict comparison.
     *
     * @param  string|callable|null  $key
     * @return static
     */
    public function uniqueStrict(mixed $key = null) {
        return $this->unique($key, true);
    }
    /**
     * Reset the keys on the underlying array.
     *
     * @return static
     */
    public function values() {
        return new static(array_values($this->items));
    }
    /**
     * Get a value retrieving callback.
     *
     * @param  string  $value
     * @return callable
     */
    protected function valueRetriever(mixed $value) {
        if ($this->useAsCallable($value)) {
            return $value;
        }
        return function ($item) use ($value) {
            return Arr::dataGet($item, $value);
        };
    }
    /**
     * Zip the collection together with one or more arrays.
     *
     * e.g. new Collection([1, 2, 3])->zip([4, 5, 6]);
     *      => [[1, 4], [2, 5], [3, 6]]
     *
     * @param  mixed ...$items
     * @return static
     */
    public function zip(mixed $items) {
        $arrayAbleItems = array_map(function ($items) {
            return $this->getArrayAbleItems($items);
        }, func_get_args());
        $params = array_merge([function () {
            return new static(func_get_args());
        }, $this->items], $arrayAbleItems);
        return new static(call_user_func_array('array_map', $params));
    }
    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArray(): array {
        return array_map(function ($value) {
            return $value instanceof ArrayAble ? $value->toArray() : $value;
        }, $this->items);
    }
    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize(): array {
        return array_map(function ($value) {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            } elseif ($value instanceof JsonAble) {
                return json_decode($value->toJson(), true);
            } elseif ($value instanceof ArrayAble) {
                return $value->toArray();
            } else {
                return $value;
            }
        }, $this->items);
    }
    /**
     * Get the collection of items as JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson(int $options = 0): string {
        return json_encode($this->jsonSerialize(), $options);
    }
    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): Traversable {
        return new ArrayIterator($this->items);
    }
    /**
     * Get a CachingIterator instance.
     *
     * @param  int  $flags
     * @return \CachingIterator
     */
    public function getCachingIterator(int $flags = CachingIterator::CALL_TOSTRING) {
        return new CachingIterator($this->getIterator(), $flags);
    }
    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count(): int {
        return count($this->items);
    }
    /**
     * Get a base Support collection instance from this collection.
     *
     * @return Collection
     */
    public function toBase() {
        return new self($this);
    }
    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists(mixed $key): bool {
        return array_key_exists($key, $this->items);
    }
    /**
     * Get an item at a given offset.
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet(mixed $key): mixed {
        return $this->items[$key];
    }
    /**
     * Set the item at a given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet(mixed $key, mixed $value): void {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }
    /**
     * Unset the item at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset(mixed $key): void {
        unset($this->items[$key]);
    }
    /**
     * Convert the collection to its string representation.
     *
     * @return string
     */
    public function __toString() {
        return $this->toJson();
    }
    /**
     * Results array of items from Collection or ArrayAble.
     *
     * @param  mixed  $items
     * @return array
     */
    protected function getArrayAbleItems(mixed $items): array {
        return Arr::toArray($items);
    }
}