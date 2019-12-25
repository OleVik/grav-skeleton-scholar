<?php

/**
 * @package    Grav\Framework\Flex
 *
 * @copyright  Copyright (C) 2015 - 2019 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Framework\Flex;

use Grav\Common\Cache;
use Grav\Common\Config\Config;
use Grav\Common\Data\Blueprint;
use Grav\Common\Debugger;
use Grav\Common\Grav;
use Grav\Common\Page\Interfaces\PageInterface;
use Grav\Common\User\Interfaces\UserInterface;
use Grav\Common\Utils;
use Grav\Framework\Cache\Adapter\DoctrineCache;
use Grav\Framework\Cache\Adapter\MemoryCache;
use Grav\Framework\Cache\CacheInterface;
use Grav\Framework\Flex\Interfaces\FlexAuthorizeInterface;
use Grav\Framework\Flex\Interfaces\FlexCollectionInterface;
use Grav\Framework\Flex\Interfaces\FlexIndexInterface;
use Grav\Framework\Flex\Interfaces\FlexObjectInterface;
use Grav\Framework\Flex\Interfaces\FlexStorageInterface;
use Grav\Framework\Flex\Storage\SimpleStorage;
use Grav\Framework\Flex\Traits\FlexAuthorizeTrait;
use Psr\SimpleCache\InvalidArgumentException;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;
use RuntimeException;

/**
 * Class FlexDirectory
 * @package Grav\Framework\Flex
 */
class FlexDirectory implements FlexAuthorizeInterface
{
    use FlexAuthorizeTrait;

    /** @var string */
    protected $type;
    /** @var string */
    protected $blueprint_file;
    /** @var Blueprint[] */
    protected $blueprints;
    /** @var FlexIndexInterface[] */
    protected $indexes = [];
    /** @var FlexCollectionInterface|null */
    protected $collection;
    /** @var bool */
    protected $enabled;
    /** @var array */
    protected $defaults;
    /** @var Config */
    protected $config;
    /** @var FlexStorageInterface */
    protected $storage;
    /** @var CacheInterface */
    protected $cache;
    /** @var FlexObjectInterface[] */
    protected $objects;
    /** @var string */
    protected $objectClassName;
    /** @var string */
    protected $collectionClassName;
    /** @var string */
    protected $indexClassName;

    /**
     * FlexDirectory constructor.
     * @param string $type
     * @param string $blueprint_file
     * @param array $defaults
     */
    public function __construct(string $type, string $blueprint_file, array $defaults = [])
    {
        $this->type = $type;
        $this->blueprints = [];
        $this->blueprint_file = $blueprint_file;
        $this->defaults = $defaults;
        $this->enabled = !empty($defaults['enabled']);
        $this->objects = [];
    }

    public function isListed(): bool
    {
        $grav = Grav::instance();

        /** @var Flex|null $flex */
        $flex = $grav['flex_objects'] ?? null;
        $directory = $flex ? $flex->getDirectory($this->type) : null;

        return null !== $directory;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return string
     * @deprecated 1.6 Use ->getFlexType() method instead.
     */
    public function getType(): string
    {
        user_error(__CLASS__ . '::' . __FUNCTION__ . '() is deprecated since Grav 1.6, use ->getFlexType() method instead', E_USER_DEPRECATED);

        return $this->type;
    }

    /**
     * @return string
     */
    public function getFlexType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->getBlueprintInternal()->get('title', ucfirst($this->getFlexType()));
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->getBlueprintInternal()->get('description', '');
    }

    /**
     * @param string|null $name
     * @param mixed $default
     * @return mixed
     */
    public function getConfig(string $name = null, $default = null)
    {
        if (null === $this->config) {
            $config = $this->getBlueprintInternal()->get('config', []);
            $config = is_array($config) ? array_replace_recursive($config, $this->defaults) : null;
            if (!is_array($config)) {
                throw new \RuntimeException('Bad configuration');
            }

            $this->config = new Config($config);
        }

        return null === $name ? $this->config : $this->config->get($name, $default);
    }

    /**
     * Returns a new uninitialized instance of blueprint.
     *
     * Always use $object->getBlueprint() or $object->getForm()->getBlueprint() instead.
     *
     * @param string $type
     * @param string $context
     * @return Blueprint
     */
    public function getBlueprint(string $type = '', string $context = '')
    {
        return clone $this->getBlueprintInternal($type, $context);
    }

    /**
     * @param string $view
     * @return string
     */
    public function getBlueprintFile(string $view = ''): string
    {
        $file = $this->blueprint_file;
        if ($view !== '') {
            $file = preg_replace('/\.yaml/', "/{$view}.yaml", $file);
        }

        return (string)$file;
    }

    /**
     * Get collection. In the site this will be filtered by the default filters (published etc).
     *
     * Use $directory->getIndex() if you want unfiltered collection.
     *
     * @param array|null $keys  Array of keys.
     * @param string|null $keyField  Field to be used as the key.
     * @return FlexCollectionInterface
     */
    public function getCollection(array $keys = null, string $keyField = null): FlexCollectionInterface
    {
        // Get all selected entries.
        $index = $this->getIndex($keys, $keyField);

        if (!Utils::isAdminPlugin()) {
            // If not in admin, filter the list by using default filters.
            $filters = (array)$this->getConfig('site.filter', []);

            foreach ($filters as $filter) {
                $index = $index->{$filter}();
            }
        }

        return $index;
    }

    /**
     * Get the full collection of all stored objects.
     *
     * Use $directory->getCollection() if you want a filtered collection.
     *
     * @param array|null $keys  Array of keys.
     * @param string|null $keyField  Field to be used as the key.
     * @return FlexIndexInterface
     */
    public function getIndex(array $keys = null, string $keyField = null): FlexIndexInterface
    {
        $keyField = $keyField ?? '';
        $index = $this->indexes[$keyField] ?? $this->loadIndex($keyField);
        $index = clone $index;

        if (null !== $keys) {
            /** @var FlexCollectionInterface $index */
            $index = $index->select($keys);
        }

        return $index->getIndex();
    }

    /**
     * Returns an object if it exists. If no arguments are passed (or both of them are null), method creates a new empty object.
     *
     * Note: It is not safe to use the object without checking if the user can access it.
     *
     * @param string $key
     * @param string|null $keyField  Field to be used as the key.
     * @return FlexObjectInterface|null
     */
    public function getObject($key = null, string $keyField = null): ?FlexObjectInterface
    {
        if (null === $key) {
            return $this->createObject([], '');
        }

        $keyField = $keyField ?? '';
        $index = $this->indexes[$keyField] ?? $this->loadIndex($keyField);

        return $index->get($key);
    }

    /**
     * @param array $data
     * @param string|null $key
     * @return FlexObjectInterface
     * @deprecated 1.7 Use $object->update()->save() instead.
     */
    public function update(array $data, string $key = null): FlexObjectInterface
    {
        user_error(__CLASS__ . '::' . __FUNCTION__ . '() should not be used anymore: use $object->update()->save() instead.', E_USER_DEPRECATED);

        $object = null !== $key ? $this->getIndex()->get($key): null;

        $storage = $this->getStorage();

        if (null === $object) {
            $object = $this->createObject($data, $key ?? '', true);
            $key = $object->getStorageKey();

            if ($key) {
                $rows = $storage->replaceRows([$key => $object->prepareStorage()]);
            } else {
                $rows = $storage->createRows([$object->prepareStorage()]);
            }
        } else {
            $oldKey = $object->getStorageKey();
            $object->update($data);
            $newKey = $object->getStorageKey();

            if ($oldKey !== $newKey) {
                $object->triggerEvent('move');
                $storage->renameRow($oldKey, $newKey);
                // TODO: media support.
            }

            $object->save();
        }

        try {
            $this->clearCache();
        } catch (InvalidArgumentException $e) {
            /** @var Debugger $debugger */
            $debugger = Grav::instance()['debugger'];
            $debugger->addException($e);

            // Caching failed, but we can ignore that for now.
        }

        return $object;
    }

    /**
     * @param string $key
     * @return FlexObjectInterface|null
     * @deprecated 1.7 Use $object->delete() instead.
     */
    public function remove(string $key): ?FlexObjectInterface
    {
        user_error(__CLASS__ . '::' . __FUNCTION__ . '() should not be used anymore: use $object->delete() instead.', E_USER_DEPRECATED);

        $object = $this->getIndex()->get($key);
        if (!$object) {
            return null;
        }

        $object->delete();

        return $object;
    }

    /**
     * @param string|null $namespace
     * @return CacheInterface
     */
    public function getCache(string $namespace = null)
    {
        $namespace = $namespace ?: 'index';
        $cache = $this->cache[$namespace] ?? null;

        if (null === $cache) {
            try {
                $grav = Grav::instance();

                /** @var Cache $gravCache */
                $gravCache = $grav['cache'];
                $config = $this->getConfig('cache.' . $namespace);
                if (empty($config['enabled'])) {
                    $cache = new MemoryCache('flex-objects-' . $this->getFlexType());
                } else {
                    $timeout = $config['timeout'] ?? 60;

                    $key = $gravCache->getKey();
                    if (Utils::isAdminPlugin()) {
                        $key = substr($key, 0, -1);
                    }
                    $cache = new DoctrineCache($gravCache->getCacheDriver(), 'flex-objects-' . $this->getFlexType() . $key, $timeout);
                }
            } catch (\Exception $e) {
                /** @var Debugger $debugger */
                $debugger = Grav::instance()['debugger'];
                $debugger->addException($e);

                $cache = new MemoryCache('flex-objects-' . $this->getFlexType());
            }

            // Disable cache key validation.
            $cache->setValidation(false);
            $this->cache[$namespace] = $cache;
        }

        return $cache;
    }

    /**
     * @return $this
     */
    public function clearCache()
    {
        $grav = Grav::instance();

        /** @var Debugger $debugger */
        $debugger = $grav['debugger'];
        $debugger->addMessage(sprintf('Flex: Clearing all %s cache', $this->type), 'debug');

        /** @var UniformResourceLocator $locator */
        $locator = $grav['locator'];
        $locator->clearCache();

        $this->getCache('index')->clear();
        $this->getCache('object')->clear();
        $this->getCache('render')->clear();

        $this->indexes = [];

        return $this;
    }

    /**
     * @param string|null $key
     * @return string|null
     */
    public function getStorageFolder(string $key = null): ?string
    {
        return $this->getStorage()->getStoragePath($key);
    }

    /**
     * @param string|null $key
     * @return string|null
     */
    public function getMediaFolder(string $key = null): ?string
    {
        return $this->getStorage()->getMediaPath($key);
    }

    /**
     * @return FlexStorageInterface
     */
    public function getStorage(): FlexStorageInterface
    {
        if (null === $this->storage) {
            $this->storage = $this->createStorage();
        }

        return $this->storage;
    }

    /**
     * @param array $data
     * @param string $key
     * @param bool $validate
     * @return FlexObjectInterface
     */
    public function createObject(array $data, string $key = '', bool $validate = false): FlexObjectInterface
    {
        /** @var string|FlexObjectInterface $className */
        $className = $this->objectClassName ?: $this->getObjectClass();

        return new $className($data, $key, $this, $validate);
    }

    /**
     * @param array $entries
     * @param string|null $keyField
     * @return FlexCollectionInterface
     */
    public function createCollection(array $entries, string $keyField = null): FlexCollectionInterface
    {
        /** @var string|FlexCollectionInterface $className */
        $className = $this->collectionClassName ?: $this->getCollectionClass();

        return $className::createFromArray($entries, $this, $keyField);
    }

    /**
     * @param array $entries
     * @param string|null $keyField
     * @return FlexIndexInterface
     */
    public function createIndex(array $entries, string $keyField = null): FlexIndexInterface
    {
        /** @var string|FlexIndexInterface $className */
        $className = $this->indexClassName ?: $this->getIndexClass();

        return $className::createFromArray($entries, $this, $keyField);
    }

    /**
     * @return string
     */
    public function getObjectClass(): string
    {
        if (!$this->objectClassName) {
            $this->objectClassName = $this->getConfig('data.object', 'Grav\\Framework\\Flex\\FlexObject');
        }

        return $this->objectClassName;
    }

    /**
     * @return string
     */
    public function getCollectionClass(): string
    {
        if (!$this->collectionClassName) {
            $this->collectionClassName = $this->getConfig('data.collection', 'Grav\\Framework\\Flex\\FlexCollection');
        }

        return $this->collectionClassName;
    }


    /**
     * @return string
     */
    public function getIndexClass(): string
    {
        if (!$this->indexClassName) {
            $this->indexClassName = $this->getConfig('data.index', 'Grav\\Framework\\Flex\\FlexIndex');
        }

        return $this->indexClassName;
    }

    /**
     * @param array $entries
     * @param string|null $keyField
     * @return FlexCollectionInterface
     */
    public function loadCollection(array $entries, string $keyField = null): FlexCollectionInterface
    {
        return $this->createCollection($this->loadObjects($entries), $keyField);
    }

    /**
     * @param array $entries
     * @return FlexObjectInterface[]
     * @internal
     */
    public function loadObjects(array $entries): array
    {
        /** @var Debugger $debugger */
        $debugger = Grav::instance()['debugger'];

        $cache = $this->getCache('object');

        $keys = [];
        $rows = [];
        $fetch = [];

        // Build lookup arrays with storage keys for the objects.
        foreach ($entries as $key => $value) {
            $k = $value['storage_key'] ?? null;
            if (null === $k) {
                continue;
            }
            $v = $this->objects[$k] ?? null;
            $keys[$k] = $key;
            $rows[$k] = $v;
            if (!$v) {
                $fetch[] = $k;
            }
        }

        $loading = \count($fetch);

        // Attempt to fetch missing rows from the cache.
        if ($fetch) {
            try {
                $debugger->startTimer('flex-objects', sprintf('Flex: Loading %d %s', $loading, $this->type));

                $fetched = (array)$cache->getMultiple($fetch);
                if ($fetched) {
                    $index = $this->loadIndex('storage_key');

                    // Make sure cached objects are up to date: compare against index checksum/timestamp.
                    foreach ($fetched as $key => $value) {
                        if ($value instanceof FlexObjectInterface) {
                            $objectMeta = $value->getMetaData();
                        } else {
                            $objectMeta = $value['__META'] ?? [];
                        }
                        $indexMeta = $index->getMetaData($key);

                        $indexChecksum = $indexMeta['checksum'] ?? $indexMeta['storage_timestamp'] ?? null;
                        $objectChecksum = $objectMeta['checksum'] ?? $objectMeta['storage_timestamp'] ?? null;
                        if ($indexChecksum !== $objectChecksum) {
                            unset($fetched[$key]);
                        }
                    }
                }

                // Update cached rows.
                $rows = (array)array_replace($rows, $fetched);
            } catch (InvalidArgumentException $e) {
                $debugger->addException($e);
            }
        }

        // Read missing rows from the storage.
        $storage = $this->getStorage();
        $updated = [];
        $rows = $storage->readRows($rows, $updated);

        // Create objects from the rows.
        $isListed = $this->isListed();
        $list = [];
        foreach ($rows as $storageKey => $row) {
            $usedKey = $keys[$storageKey];

            if ($row instanceof FlexObjectInterface) {
                $object = $row;
            } else {
                if ($row === null) {
                    $debugger->addMessage(sprintf('Flex: Object %s was not found from %s storage', $storageKey, $this->type), 'debug');
                    continue;
                }

                if (isset($row['__ERROR'])) {
                    $message = sprintf('Flex: Object %s is broken in %s storage: %s', $storageKey, $this->type, $row['__ERROR']);
                    $debugger->addException(new \RuntimeException($message));
                    $debugger->addMessage($message, 'error');
                    continue;
                }

                if (!isset($row['__META'])) {
                    $row['__META'] = [
                        'storage_key' => $storageKey,
                        'storage_timestamp' => $entries[$usedKey]['storage_timestamp'] ?? 0,
                    ];
                }

                $key = $row['__META']['key'] ?? $entries[$usedKey]['key'] ?? $usedKey;
                $object = $this->createObject($row, $key, false);
                $this->objects[$storageKey] = $object;
                if ($isListed) {
                    // If unserialize works for the object, serialize the object to speed up the loading.
                    $updated[$storageKey] = $object;
                }
            }

            $list[$usedKey] = $object;
        }

        // Store updated rows to the cache.
        if ($updated) {
            if (!$cache instanceof MemoryCache) {
                ///** @var Debugger $debugger */
                //$debugger = Grav::instance()['debugger'];
                //$debugger->addMessage(sprintf('Flex: Caching %d %s', \count($entries), $this->type), 'debug');
            }
            try {
                $cache->setMultiple($updated);
            } catch (InvalidArgumentException $e) {
                $debugger->addException($e);
                // TODO: log about the issue.
            }
        }

        if ($fetch) {
            $debugger->stopTimer('flex-objects');
        }

        return $list;
    }

    public function reloadIndex(): void
    {
        $cache = $this->getCache('index');
        $cache->delete('__keys');
    }

    /**
     * @param string $type_view
     * @param string $context
     * @return Blueprint
     */
    protected function getBlueprintInternal(string $type_view = '', string $context = '')
    {
        if (!isset($this->blueprints[$type_view])) {
            if (!file_exists($this->blueprint_file)) {
                throw new RuntimeException(sprintf('Flex: Blueprint file for %s is missing', $this->type));
            }

            $parts = explode('.', rtrim($type_view, '.'), 2);
            $type = array_shift($parts);
            $view = array_shift($parts) ?: '';

            $blueprint = new Blueprint($this->getBlueprintFile($view));
            $blueprint->addDynamicHandler('data', function (array &$field, $property, array &$call) {
                $this->dynamicDataField($field, $property, $call);
            });
            $blueprint->addDynamicHandler('flex', function (array &$field, $property, array &$call) {
                $this->dynamicFlexField($field, $property, $call);
            });

            if ($context) {
                $blueprint->setContext($context);
            }

            $blueprint->load($type ?: null);
            if ($blueprint->get('type') === 'flex-objects' && isset(Grav::instance()['admin'])) {
                $blueprintBase = (new Blueprint('plugin://flex-objects/blueprints/flex-objects.yaml'))->load();
                $blueprint->extend($blueprintBase, true);
            }

            $this->blueprints[$type_view] = $blueprint;
        }

        return $this->blueprints[$type_view];
    }

    /**
     * @param array $field
     * @param string $property
     * @param array $call
     */
    protected function dynamicDataField(array &$field, $property, array &$call)
    {
        $params = $call['params'];
        if (\is_array($params)) {
            $function = array_shift($params);
        } else {
            $function = $params;
            $params = [];
        }

        $object = $call['object'];
        if ($function === '\Grav\Common\Page\Pages::pageTypes') {
            $params = [$object instanceof PageInterface && $object->isModule() ? 'modular' : 'standard'];
        }

        $data = null;
        if (\is_callable($function)) {
            $data = \call_user_func_array($function, $params);
        }

        // If function returns a value,
        if (null !== $data) {
            if (\is_array($data) && isset($field[$property]) && \is_array($field[$property])) {
                // Combine field and @data-field together.
                $field[$property] += $data;
            } else {
                // Or create/replace field with @data-field.
                $field[$property] = $data;
            }
        }
    }

    /**
     * @param array $field
     * @param string $property
     * @param array $call
     */
    protected function dynamicFlexField(array &$field, $property, array &$call)
    {
        $params = (array)$call['params'];
        $object = $call['object'] ?? null;
        $method = array_shift($params);

        if ($object && method_exists($object, $method)) {
            $value = $object->{$method}(...$params);
            if (\is_array($value) && isset($field[$property]) && \is_array($field[$property])) {
                $field[$property] = array_merge_recursive($field[$property], $value);
            } else {
                $field[$property] = $value;
            }
        }
    }

    /**
     * @return FlexStorageInterface
     */
    protected function createStorage(): FlexStorageInterface
    {
        $this->collection = $this->createCollection([]);

        $storage = $this->getConfig('data.storage');

        if (!\is_array($storage)) {
            $storage = ['options' => ['folder' => $storage]];
        }

        $className = $storage['class'] ?? SimpleStorage::class;
        $options = $storage['options'] ?? [];

        return new $className($options);
    }

    /**
     * @param string $keyField
     * @return FlexIndexInterface
     */
    protected function loadIndex(string $keyField): FlexIndexInterface
    {
        static $i = 0;

        $index = $this->indexes[$keyField] ?? null;
        if (null !== $index) {
            return $index;
        }

        $index = $this->indexes['storage_key'] ?? null;
        if (null === $index) {
            $i++;
            $j = $i;
            /** @var Debugger $debugger */
            $debugger = Grav::instance()['debugger'];
            $debugger->startTimer('flex-keys-' . $this->type . $j, "Flex: Loading {$this->type} index");

            $storage = $this->getStorage();
            $cache = $this->getCache('index');

            try {
                $keys = $cache->get('__keys');
            } catch (InvalidArgumentException $e) {
                $debugger->addException($e);
                $keys = null;
            }

            if (null === $keys) {
                /** @var string|FlexIndexInterface $className */
                $className = $this->getIndexClass();
                $keys = $className::loadEntriesFromStorage($storage);
                if (!$cache instanceof MemoryCache) {
                    $debugger->addMessage(
                        sprintf('Flex: Caching %s index of %d objects', $this->type, \count($keys)),
                        'debug'
                    );
                }
                try {
                    $cache->set('__keys', $keys);
                } catch (InvalidArgumentException $e) {
                    $debugger->addException($e);
                    // TODO: log about the issue.
                }
            }

            $ordering = $this->getConfig('data.ordering', []);

            // We need to do this in two steps as orderBy() calls loadIndex() again and we do not want infinite loop.
            $this->indexes['storage_key'] = $index = $this->createIndex($keys, 'storage_key');
            if ($ordering) {
                /** @var FlexCollectionInterface $collection */
                $collection = $this->indexes['storage_key']->orderBy($ordering);
                $this->indexes['storage_key'] = $index = $collection->getIndex();
            }

            $debugger->stopTimer('flex-keys-' . $this->type . $j);
        }

        if ($keyField !== 'storage_key') {
            $this->indexes[$keyField] = $index = $index->withKeyField($keyField ?: null);
        }

        return $index;
    }

    /**
     * @param string $action
     * @return string
     */
    protected function getAuthorizeAction(string $action): string
    {
        // Handle special action save, which can mean either update or create.
        if ($action === 'save') {
            $action = 'create';
        }

        return $action;
    }
    /**
     * @return UserInterface|null
     */
    protected function getActiveUser(): ?UserInterface
    {
        /** @var UserInterface|null $user */
        $user = Grav::instance()['user'] ?? null;

        return $user;
    }

    /**
     * @return string
     */
    protected function getAuthorizeScope(): string
    {
        return isset(Grav::instance()['admin']) ? 'admin' : 'site';
    }
}
