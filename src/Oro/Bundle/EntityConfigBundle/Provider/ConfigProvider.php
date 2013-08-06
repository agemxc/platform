<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Doctrine\ORM\PersistentCollection;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

use Oro\Bundle\EntityConfigBundle\ConfigManager;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\EntityConfigContainer;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var EntityConfigContainer
     */
    protected $configContainer;

    /**
     * @var string
     */
    protected $scope;

    /**
     * @param ConfigManager         $configManager
     * @param EntityConfigContainer $configContainer
     */
    public function __construct(ConfigManager $configManager, EntityConfigContainer $configContainer)
    {
        $this->configManager   = $configManager;
        $this->configContainer = $configContainer;
        $this->scope           = $configContainer->getScope();
    }

    /**
     * @return EntityConfigContainer
     */
    public function getConfigContainer()
    {
        return $this->configContainer;
    }

    /**
     * @param $className
     * @return bool
     */
    public function isConfigurable($className)
    {
        return $this->configManager->isConfigurable($this->getClassName($className));
    }

    /**
     * @param $className
     * @return FieldConfigId[]
     */
    public function getFieldConfigIds($className)
    {
        return $this->configManager->getFieldConfigIds($this->getClassName($className), $this->getScope());
    }

    /**
     * @param      $className
     * @param null $fieldName
     * @param null $fieldType
     * @return EntityConfigId|FieldConfigId
     */
    public function getConfigId($className, $fieldName = null, $fieldType = null)
    {
        if ($fieldName) {
            return new FieldConfigId($this->getClassName($className), $this->getScope(), $fieldName, $fieldType);
        } else {
            return new EntityConfigId($this->getClassName($className), $this->getScope());
        }
    }

    /**
     * @param      $className
     * @param null $fieldName
     * @return bool
     */
    public function hasConfig($className, $fieldName = null)
    {
        return $this->configManager->hasConfig($this->getConfigId($className, $fieldName));
    }

    /**
     * @param      $className
     * @param null $fieldName
     * @return null|Config|ConfigInterface
     */
    public function getConfig($className, $fieldName = null)
    {
        return $this->configManager->getConfig($this->getConfigId($className, $fieldName));
    }

    /**
     * @param ConfigIdInterface $configId
     * @return ConfigInterface
     */
    public function getConfigById(ConfigIdInterface $configId)
    {
        return $this->configManager->getConfig($configId);
    }

    /**
     * @param  ConfigIdInterface $configId
     * @param  array             $values
     * @return Config
     */
    public function createConfig(ConfigIdInterface $configId, array $values)
    {
        $config = new Config($configId);
        $type   = $configId instanceof FieldConfigId ? EntityConfigContainer::TYPE_FIELD : EntityConfigContainer::TYPE_ENTITY;
        $values = array_merge($this->getConfigContainer()->getDefaultValues($type), $values);

        foreach ($values as $key => $value) {
            $config->set($key, $value);
        }

        $this->persist($config);

        return $config;
    }

    /**
     * @param $entity
     * @return string
     * @throws RuntimeException
     */
    public function getClassName($entity)
    {
        $className = $entity;

        if ($entity instanceof PersistentCollection) {
            $className = $entity->getTypeClass()->getName();
        } elseif (is_object($entity)) {
            $className = get_class($entity);
        } elseif (is_array($entity) && count($entity) && is_object(reset($entity))) {
            $className = get_class(reset($entity));
        }

        if (!is_string($className)) {
            throw new RuntimeException('AbstractAdvancedConfigProvider::getClassName expects Object, PersistentCollection array of entities or string');
        }

        return $className;
    }

    /**
     * @param      $className
     * @param null $fieldName
     */
    public function clearCache($className, $fieldName = null)
    {
        $this->configManager->clearCache($this->getConfigId($className, $fieldName));
    }

    /**
     * @param ConfigInterface $config
     */
    public function persist(ConfigInterface $config)
    {
        $this->configManager->persist($config);
    }

    /**
     * Flush configs
     */
    public function flush()
    {
        $this->configManager->flush();
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }
}
