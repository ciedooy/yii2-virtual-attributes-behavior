<?php

namespace CieDooy\VirtualAttributes;

use yii\db\ActiveRecord;
use yii\base\Behavior;
use yii\base\InvalidConfigException;

class VirtualAttributesBehavior extends Behavior
{
    public $attribute;
    public $attributes = [];

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'decode',
            ActiveRecord::EVENT_AFTER_INSERT => 'decode',
            ActiveRecord::EVENT_AFTER_UPDATE => 'decode',
            ActiveRecord::EVENT_BEFORE_INSERT => 'encode',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'encode',
        ];
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!$this->attribute) {
            throw new InvalidConfigException('The "attribute" property must be set.');
        }

        if (!is_string($this->attribute)) {
            throw new InvalidConfigException('The "attribute" property must be an string.');
        }

        if (!is_array($this->attributes)) {
            throw new InvalidConfigException('The "attributes" property must be an array');
        }
    }

    public function encode()
    {
        $data = [];

        foreach ($this->attributes as $name) {
            $data[$name] = array_key_exists($name, $this->owner) ? $this->owner->{$name} : null;
        }

        $this->owner->{$this->attribute} = $this->serialize($data);
    }

    private function serialize($data)
    {
        $data = json_encode($data);

        if (JSON_ERROR_NONE !== json_last_error()) {
            return [];
        }

        return $data;
    }

    public function decode()
    {
        $attributes = [];

        foreach ($this->attributes as $name) {
            $attributes[$name] = null;
        }

        $attributes = array_merge($attributes, $this->unserialize($this->owner->{$this->attribute}));

        foreach ($attributes as $name => $value) {
            if (in_array($name, $this->attributes, true)) {
                $this->owner->{$name} = $value;
            }
        }
    }

    private function unserialize($data)
    {
        $data = json_decode($data, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            return [];
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function canGetProperty($name = null, $checkVars = true)
    {
        return in_array($name, $this->attributes, true) ? true : parent::canGetProperty($name, $checkVars);
    }

    /**
     * @inheritdoc
     */
    public function canSetProperty($name = null, $checkVars = true)
    {
        return in_array($name, $this->attributes, true) ? true : parent::canSetProperty($name, $checkVars);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if (!in_array($name, $this->attributes, true)) {
            return parent::__get($name);
        }

        return $this->owner->{$name};
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if (in_array($name, $this->attributes, true)) {
            $this->owner->{$name} = $value;
        } else {
            parent::__set($name, $value);
        }
    }
}
