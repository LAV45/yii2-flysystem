<?php

namespace airani\flysystem;

use Yii;
use yii\base\Configurable;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;
use yii\helpers\ArrayHelper;

/**
 * Flysystem MountManager as Yii2 component
 * @author Ali Irani <ali@irani.im>
 */
class MountManager extends \League\Flysystem\MountManager implements Configurable
{
    private $_filesystems = [];

    /**
     * Compatible with configurable Yii2 object
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        if (!empty($config)) {
            Yii::configure($this, $config);
        }

        $this->mountFilesystems($this->_filesystems);
    }

    /**
     * Create and config filesystem adapter
     */
    public function __set($name, $config)
    {
        $this->_filesystems[$name] = $this->createObject([
            'class' => 'League\Flysystem\Filesystem',
            'adapter' => $config
        ]);
    }

    /**
     * @param array $config
     * @return object
     * @throws InvalidConfigException
     */
    private function createObject(array $config)
    {
        $class = ArrayHelper::getValue($config, 'class');
        if ($class === null) {
            throw new InvalidConfigException('[class] must be set for an adapter of Filesystem');
        }

        $constructParams = [];
        $reflection = new \ReflectionClass($class);

        if ($constructor = $reflection->getConstructor()) {
            foreach ($constructor->getParameters() as $param) {
                $defaultValue = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
                $value = ArrayHelper::remove($config, $param->name, $defaultValue);

                if (is_array($value)) {
                    if (isset($value['class'])) {
                        $value = self::createObject($value);
                    }
                } elseif (is_string($value)) {
                    if (class_exists($value)) {
                        $value = self::createObject(['class' => $value]);
                    }
                }

                $constructParams[] = $value;
            }
        }

        return Yii::createObject($config, $constructParams);
    }

    /**
     * Getting filesystem object with mount manager prefix name
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->_filesystems) === false) {
            throw new UnknownPropertyException('Getting unknown property: ' . get_class($this) . '::' . $name);
        }

        return $this->_filesystems[$name];
    }
}
