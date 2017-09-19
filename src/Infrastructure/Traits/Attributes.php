<?php
namespace Zodream\Infrastructure\Traits;

use InvalidArgumentException;
use DateTime;
use DateTimeZone;

trait Attributes {
    /**
     * `__clone` resets change history for cloned instances
     *
     * @api     public
     * @return  array
     */
    public function __clone() {
        foreach (array_keys($this->__attributes) as $attribute) { $this->resetChangeHistory($attribute); }
    }
    /**
     * `changed` provides changed attribute history
     *
     * @param   string  $attribute  attribute name
     *
     * @api     public
     * @return  array
     */
    public function changed($attribute) {
        return $this->propertyExists($attribute, 'changes')
            ? $this->getPropertyFor($attribute, 'changes')
            : [];
    }
    /**
     * `resetChangeHistory` clears the change history for an attribute
     *
     * @param   string  $attribute  attribute name
     *
     * @return  $this
     */
    public function resetChangeHistory($attribute) {
        if ($this->propertyExists($attribute, 'changes')) {
            $this->setPropertyFor($attribute, 'changes', [], null, false);
        }
        return $this;
    }
    /**
     * `has` determines if an attribute "has" been defined
     *
     * @param   string  $attribute  attribute name
     *
     * @api     public
     * @return  boolean
     */
    public function has($attribute) {
        return array_key_exists($attribute, $this->__attributes);
    }
    /**
     * `propertyExists` determines if an attribute property has been set.
     *
     * @param   string  $attribute  attribute name
     * @param   string  $property   property name
     *
     * @api     public
     * @return  boolean
     */
    public function propertyExists($attribute, $property) {
        return array_key_exists($attribute, $this->__attributes)
            && array_key_exists($property,  $this->__attributes[$attribute]);
    }
    /**
     * `__isset` determines if an attribute:
     * (1) has been defined
     * (2) has been set
     * (3) is not NULL
     *
     * @param   string  $attribute
     *
     * @return  boolean
     */
    public function __isset($attribute) {
        return $this->propertyExists($attribute, 'value')
            && $this->getPropertyFor($attribute, 'value') !== null;
    }
    /**
     * `get` returns attributes value or, if attribute value is null, returns default value if given
     *
     * @param   string  $name     attribute name
     * @param   mixed   $default  [optional] default return value
     *
     * @return  mixed
     */
    public function get($name, $default=null) {
        $map = array_map(function($name) use($default){
            if ($this->__isset($name)) {
                return $this->__attributes[$name]['value'];
            } elseif ($this->propertyExists($name, 'default')) {
                return $this->__attributes[$name]['default'];
            }
            return (null === $default) ? null : $default;
        }, (array) $name);
        return count($map) > 1 ? $map : $map[0];
    }
    /**
     * `__get` is an alias of `get`
     *
     * @param   string  $name     attribute name
     *
     * @return  mixed
     */
    public function __get($attribute) {
        return $this->get($attribute);
    }
    /**
     * `set` attributes value
     *
     * @param   string  $attribute  attribute name
     * @param   mixed   $value      attribute value
     *
     * @return  $this
     */
    public function set($attribute, $value = null) {
        $attributes = is_array($attribute)
            ? $attribute
            : [$attribute => $value];
        foreach ($attributes as $attr => $val) {
            if ($this->getPropertyFor($attr, 'accepts')) {
                $acceptsConstraint = new Attributes\Constraint\Accepts($this->getPropertyFor($attr, 'accepts'));
                if (!$acceptsConstraint->isValid($val)) {
                    throw new InvalidArgumentException(__CLASS__ . " does not accept {$val} as value for the property {$attr}");
                }
            }
            if ($this->getPropertyFor($attr, 'minimum')) {
                $minimumConstraint = new Attributes\Constraint\Minimum($this->getPropertyFor($attr, 'minimum'));
                if (!$minimumConstraint->isValid($val)) {
                    throw new InvalidArgumentException(__CLASS__ . " does not accept {$val} as value for the property {$attr}");
                }
            }
            if ($this->getPropertyFor($attr, 'maximum')) {
                $maximumConstraint = new Attributes\Constraint\Maximum($this->getPropertyFor($attr, 'maximum'));
                if (!$maximumConstraint->isValid($val)) {
                    throw new InvalidArgumentException(__CLASS__ . " does not accept {$val} as value for the property {$attr}");
                }
            }
            $this->setPropertyFor($attr, 'value', $val, 'set');
        }
        return $this;
    }
    /**
     * `push` a new value onto end of array
     *
     * @param   string  $attribute  attribute name
     * @param   mixed   $value      attribute value
     *
     * @return  $this
     */
    public function push($attribute, $value) {
        if (! $this->has($attribute)) {
            return $this;
        }
        $new   = $this->get($attribute);
        if (! is_array($new) && !$new instanceof Traversable) {
            return $this;
        }
        $new[] = $value;
        $this->setPropertyFor($attribute, 'value', $new, 'push');
        return $this;
    }
    /**
     * `__set` is an alias of `set`
     *
     * @param   string  $attribute  attribute name
     * @param   mixed   $value      attribute value
     *
     * @return  $this->set()
     */
    public function __set($attribute, $value) {
        return $this->set($attribute, $value);
    }
    /**
     * `__unset` clears an attribute's value
     *
     * @param   string  $attribute  attribute name
     *
     * @return  $this
     */
    public function __unset($attribute) {
        $this->setPropertyFor($attribute, 'value', null, 'unset');
        return $this;
    }
    /**
     * `getPropertyFor` returns an attribute property by name
     *
     * @param   string  $attribute  attribute name
     * @param   string  $property   property name
     *
     * @return  boolean
     */
    private function getPropertyFor($attribute, $property) {
        return $this->propertyExists($attribute, $property)
            ? $this->__attributes[$attribute][$property]
            : null;
    }
    /**
     * `setPropertyFor` sets an attribute property value
     *
     * @param   string  $attribute  attribute name
     * @param   string  $property   property name
     * @param   mixed   $value      property value
     *
     * @return  boolean
     */
    private function setPropertyFor($attribute, $property, $value, $action, $changeTracking = true) {
        if (! $this->has($attribute)) {return;}
        if ($changeTracking) {
            $this->__attributes[$attribute]['changes'][] = [
                'action' => $action,
                'from'   => $this->get($attribute),
                'to'     => $value,
                'when'   => new DateTime('now', new DateTimezone('UTC'))
            ];
        }
        $this->__attributes[$attribute][$property]     = $value;
    }
}