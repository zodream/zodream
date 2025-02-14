<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Support;


use Countable;
use JsonSerializable;
use Zodream\Helpers\Str;
use Zodream\Infrastructure\Contracts\ArrayAble;
use Zodream\Infrastructure\Contracts\JsonAble;

class MessageBag implements Countable, JsonSerializable, ArrayAble, JsonAble {
    /**
     * All of the registered messages.
     *
     * @var array
     */
    protected array $messages = [];
    /**
     * Default format for message output.
     *
     * @var string
     */
    protected string $format = ':message';
    /**
     * Create a new message bag instance.
     *
     * @param  array  $messages
     */
    public function __construct(array $messages = []) {
        foreach ($messages as $key => $value) {
            $this->messages[$key] = (array) $value;
        }
    }
    /**
     * Get the keys present in the message bag.
     *
     * @return array
     */
    public function keys(): array {
        return array_keys($this->messages);
    }
    /**
     * Add a message to the bag.
     *
     * @param  string  $key
     * @param  string  $message
     * @return $this
     */
    public function add(string $key, string $message) {
        if ($this->isUnique($key, $message)) {
            $this->messages[$key][] = $message;
        }
        return $this;
    }
    /**
     * Merge a new array of messages into the bag.
     *
     * @param MessageBag|array  $messages
     * @return $this
     */
    public function merge(array|MessageBag $messages) {
        if ($messages instanceof MessageBag) {
            $messages = $messages->getMessageBag()->getMessages();
        }
        $this->messages = array_merge_recursive($this->messages, $messages);
        return $this;
    }
    /**
     * Determine if a key and message combination already exists.
     *
     * @param  string  $key
     * @param  string  $message
     * @return bool
     */
    protected function isUnique(string $key, string $message): bool {
        $messages = $this->messages;
        return ! isset($messages[$key]) || ! in_array($message, $messages[$key]);
    }

    /**
     * Determine if messages exist for all of the given keys.
     *
     * @param array|string|null $key
     * @return bool
     */
    public function has(array|string|null $key): bool {
        if (is_null($key)) {
            return $this->any();
        }
        $keys = is_array($key) ? $key : func_get_args();
        foreach ($keys as $key) {
            if ($this->first($key) === '') {
                return false;
            }
        }
        return true;
    }
    /**
     * Determine if messages exist for any of the given keys.
     *
     * @param  array  $keys
     * @return bool
     */
    public function hasAny(array $keys = []): bool {
        foreach ($keys as $key) {
            if ($this->has($key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the first message from the bag for a given key.
     *
     * @param string|null $key
     * @param string|null $format
     * @return string
     */
    public function first(string|null $key = null, string|null $format = null): string {
        $messages = is_null($key) ? $this->all($format) : $this->get($key, $format);
        return count($messages) > 0 ? $messages[0] : '';
    }

    /**
     * Get all of the messages from the bag for a given key.
     *
     * @param string $key
     * @param string|null $format
     * @return array
     */
    public function get(string $key, string|null $format = null): array {
        // If the message exists in the container, we will transform it and return
        // the message. Otherwise, we'll check if the key is implicit & collect
        // all the messages that match a given key and output it as an array.
        if (isset($this->messages[$key])) {
            return $this->transform(
                $this->messages[$key], $this->checkFormat($format), $key
            );
        }
        if (Str::contains($key, '*')) {
            return $this->getMessagesForWildcardKey($key, $format);
        }
        return [];
    }
    /**
     * Get the messages for a wildcard key.
     *
     * @param  string  $key
     * @param  string|null  $format
     * @return array
     */
    protected function getMessagesForWildcardKey(string $key, string|null $format): array {
        return (new Collection($this->messages))
            ->filter(function ($messages, $messageKey) use ($key) {
                return Str::is($key, $messageKey);
            })
            ->map(function ($messages, $messageKey) use ($format) {
                return $this->transform(
                    $messages, $this->checkFormat($format), $messageKey
                );
            })->all();
    }

    /**
     * Get all of the messages for every key in the bag.
     *
     * @param string|null $format
     * @return array
     */
    public function all(string|null $format = null): array {
        $format = $this->checkFormat($format);
        $all = [];
        foreach ($this->messages as $key => $messages) {
            $all = array_merge($all, $this->transform($messages, $format, $key));
        }
        return $all;
    }

    /**
     * Get all of the unique messages for every key in the bag.
     *
     * @param string|null $format
     * @return array
     */
    public function unique(string|null $format = null): array {
        return array_unique($this->all($format));
    }
    /**
     * Format an array of messages.
     *
     * @param  array   $messages
     * @param  string  $format
     * @param  string  $messageKey
     * @return array
     */
    protected function transform(array $messages, string $format, string $messageKey) {
        $messages = (array) $messages;
        // We will simply spin through the given messages and transform each one
        // replacing the :message place holder with the real message allowing
        // the messages to be easily formatted to each developer's desires.
        $replace = [':message', ':key'];
        foreach ($messages as &$message) {
            $message = str_replace($replace, [$message, $messageKey], $format);
        }
        return $messages;
    }

    /**
     * Get the appropriate format based on the given format.
     *
     * @param string|null $format
     * @return string
     */
    protected function checkFormat(string|null $format): string {
        return $format ?: $this->format;
    }
    /**
     * Get the raw messages in the container.
     *
     * @return array
     */
    public function messages(): array {
        return $this->messages;
    }
    /**
     * Get the raw messages in the container.
     *
     * @return array
     */
    public function getMessages(): array {
        return $this->messages();
    }
    /**
     * Get the messages for the instance.
     *
     * @return MessageBag
     */
    public function getMessageBag() {
        return $this;
    }
    /**
     * Get the default message format.
     *
     * @return string
     */
    public function getFormat(): string {
        return $this->format;
    }
    /**
     * Set the default message format.
     *
     * @param  string  $format
     * @return MessageBag
     */
    public function setFormat(string $format = ':message') {
        $this->format = $format;
        return $this;
    }
    /**
     * Determine if the message bag has any messages.
     *
     * @return bool
     */
    public function isEmpty(): bool {
        return ! $this->any();
    }
    /**
     * Determine if the message bag has any messages.
     *
     * @return bool
     */
    public function any(): bool {
        return $this->count() > 0;
    }
    /**
     * Get the number of messages in the container.
     *
     * @return int
     */
    public function count(): int {
        return count($this->messages, COUNT_RECURSIVE) - count($this->messages);
    }
    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array {
        return $this->getMessages();
    }
    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize(): mixed {
        return $this->toArray();
    }
    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson(int $options = 0): string {
        return json_encode($this->jsonSerialize(), $options);
    }
    /**
     * Convert the message bag to its string representation.
     *
     * @return string
     */
    public function __toString(): string {
        return $this->toJson();
    }
}