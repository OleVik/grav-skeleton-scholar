<?php

/**
 * @package    Grav\Framework\Flex
 *
 * @copyright  Copyright (C) 2015 - 2019 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Framework\Flex;

use Grav\Common\Data\Blueprint;
use Grav\Common\Debugger;
use Grav\Common\Grav;
use Grav\Common\Inflector;
use Grav\Common\Twig\Twig;
use Grav\Common\User\Interfaces\UserInterface;
use Grav\Common\Utils;
use Grav\Framework\Cache\CacheInterface;
use Grav\Framework\ContentBlock\HtmlBlock;
use Grav\Framework\Flex\Interfaces\FlexAuthorizeInterface;
use Grav\Framework\Flex\Interfaces\FlexCollectionInterface;
use Grav\Framework\Flex\Interfaces\FlexFormInterface;
use Grav\Framework\Flex\Traits\FlexAuthorizeTrait;
use Grav\Framework\Object\Access\NestedArrayAccessTrait;
use Grav\Framework\Object\Access\NestedPropertyTrait;
use Grav\Framework\Object\Access\OverloadedPropertyTrait;
use Grav\Framework\Object\Base\ObjectTrait;
use Grav\Framework\Flex\Interfaces\FlexObjectInterface;
use Grav\Framework\Object\Interfaces\ObjectInterface;
use Grav\Framework\Object\Property\LazyPropertyTrait;
use Psr\SimpleCache\InvalidArgumentException;
use RocketTheme\Toolbox\Event\Event;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Twig\Template;
use Twig\TemplateWrapper;

/**
 * Class FlexObject
 * @package Grav\Framework\Flex
 */
class FlexObject implements FlexObjectInterface, FlexAuthorizeInterface
{
    use ObjectTrait;
    use LazyPropertyTrait {
        LazyPropertyTrait::__construct as private objectConstruct;
    }
    use NestedPropertyTrait;
    use OverloadedPropertyTrait;
    use NestedArrayAccessTrait;
    use FlexAuthorizeTrait;

    /** @var FlexDirectory */
    private $_flexDirectory;
    /** @var FlexFormInterface[] */
    private $_forms = [];
    /** @var array */
    private $_meta;
    /** @var array */
    protected $_changes;
    /** @var string */
    protected $storage_key;
    /** @var int */
    protected $storage_timestamp;

    /**
     * @return array
     */
    public static function getCachedMethods(): array
    {
        return [
            'getTypePrefix' => true,
            'getType' => true,
            'getFlexType' => true,
            'getFlexDirectory' => true,
            'hasFlexFeature' => true,
            'getFlexFeatures' => true,
            'getCacheKey' => true,
            'getCacheChecksum' => false,
            'getTimestamp' => true,
            'value' => true,
            'exists' => true,
            'hasProperty' => true,
            'getProperty' => true,

            // FlexAclTrait
            'isAuthorized' => 'session',
        ];
    }

    public static function createFromStorage(array $elements, array $storage, FlexDirectory $directory, bool $validate = false)
    {
        $instance = new static($elements, $storage['key'], $directory, $validate);
        $instance->setStorage($storage);

        return $instance;
    }

    /**
     * {@inheritdoc}
     * @see FlexObjectInterface::__construct()
     */
    public function __construct(array $elements, $key, FlexDirectory $directory, bool $validate = false)
    {
        $this->_flexDirectory = $directory;

        if (isset($elements['__META'])) {
            $this->setStorage($elements['__META']);
            unset($elements['__META']);
        }

        if ($validate) {
            $blueprint = $this->getFlexDirectory()->getBlueprint();

            $blueprint->validate($elements);

            $elements = $blueprint->filter($elements);
        }

        $this->filterElements($elements);

        $this->objectConstruct($elements, $key);
    }

    /**
     * {@inheritdoc}
     * @see FlexCommonInterface::hasFlexFeature()
     */
    public function hasFlexFeature(string $name): bool
    {
        return in_array($name, $this->getFlexFeatures(), true);
    }

    /**
     * {@inheritdoc}
     * @see FlexCommonInterface::hasFlexFeature()
     */
    public function getFlexFeatures(): array
    {
        $implements = class_implements($this);

        $list = [];
        foreach ($implements as $interface) {
            if ($pos = strrpos($interface, '\\')) {
                $interface = substr($interface, $pos+1);
            }

            $list[] = Inflector::hyphenize(str_replace('Interface', '', $interface));
        }

        return $list;
    }

    /**
     * {@inheritdoc}
     * @see FlexObjectInterface::getFlexType()
     */
    public function getFlexType(): string
    {
        return $this->_flexDirectory->getFlexType();
    }

    /**
     * {@inheritdoc}
     * @see FlexObjectInterface::getFlexDirectory()
     */
    public function getFlexDirectory(): FlexDirectory
    {
        return $this->_flexDirectory;
    }

    /**
     * {@inheritdoc}
     * @see FlexObjectInterface::getTimestamp()
     */
    public function getTimestamp(): int
    {
        return $this->_meta['storage_timestamp'] ?? 0;
    }

    /**
     * {@inheritdoc}
     * @see FlexObjectInterface::getCacheKey()
     */
    public function getCacheKey(): string
    {
        return $this->hasKey() ? $this->getTypePrefix() . $this->getFlexType() . '.' . $this->getKey() : '';
    }

    /**
     * {@inheritdoc}
     * @see FlexObjectInterface::getCacheChecksum()
     */
    public function getCacheChecksum(): string
    {
        return (string)($this->_meta['checksum'] ?? $this->getTimestamp());
    }

    /**
     * {@inheritdoc}
     * @see FlexObjectInterface::search()
     */
    public function search(string $search, $properties = null, array $options = null): float
    {
        $properties = (array)($properties ?? $this->getFlexDirectory()->getConfig('data.search.fields'));
        if (!$properties) {
            foreach ($this->getFlexDirectory()->getConfig('admin.list.fields', []) as $property => $value) {
                if (!empty($value['link'])) {
                    $properties[] = $property;
                }
            }
        }

        $options = $options ?? (array)$this->getFlexDirectory()->getConfig('data.search.options');

        $weight = 0;
        foreach ($properties as $property) {
            $weight += $this->searchNestedProperty($property, $search, $options);
        }

        return $weight > 0 ? min($weight, 1) : 0;
    }

    /**
     * {@inheritdoc}
     * @see ObjectInterface::getFlexKey()
     */
    public function getKey()
    {
        return (string)$this->_key;
    }

    /**
     * {@inheritdoc}
     * @see FlexObjectInterface::getFlexKey()
     */
    public function getFlexKey(): string
    {
        $key = $this->_meta['flex_key'] ?? null;

        if (!$key && $key = $this->getStorageKey()) {
            $key = $this->_flexDirectory->getFlexType() . '.obj:' . $key;
        }

        return (string)$key;
    }

    /**
     * {@inheritdoc}
     * @see FlexObjectInterface::getStorageKey()
     */
    public function getStorageKey(): string
    {
        return (string)($this->storage_key ?? $this->_meta['storage_key'] ?? null);
    }

    /**
     * {@inheritdoc}
     * @see FlexObjectInterface::getMetaData()
     */
    public function getMetaData(): array
    {
        return $this->_meta ?? [];
    }

    /**
     * {@inheritdoc}
     * @see FlexObjectInterface::exists()
     */
    public function exists(): bool
    {
        $key = $this->getStorageKey();

        return $key && $this->getFlexDirectory()->getStorage()->hasKey($key);
    }

    /**
     * @param string $property
     * @param string $search
     * @param array|null $options
     * @return float
     */
    public function searchProperty(string $property, string $search, array $options = null): float
    {
        $options = $options ?? $this->getFlexDirectory()->getConfig('data.search.options', []);
        $value = $this->getProperty($property);

        return $this->searchValue($property, $value, $search, $options);
    }

    /**
     * @param string $property
     * @param string $search
     * @param array|null $options
     * @return float
     */
    public function searchNestedProperty(string $property, string $search, array $options = null): float
    {
        $options = $options ?? $this->getFlexDirectory()->getConfig('data.search.options', []);
        if ($property === 'key') {
            $value = $this->getKey();
        } else {
            $value = $this->getNestedProperty($property);
        }

        return $this->searchValue($property, $value, $search, $options);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param string $search
     * @param array|null $options
     * @return float
     */
    protected function searchValue(string $name, $value, string $search, array $options = null): float
    {
        // Ignore empty search strings.
        $search = trim($search);
        if ($search === '') {
            return 0;
        }

        // Search only non-empty string values.
        if (!\is_string($value) || $value === '') {
            return 0;
        }

        $tested = false;
        if (($tested |= !empty($options['starts_with'])) && Utils::startsWith($value, $search, $options['case_sensitive'] ?? false)) {
            return (float)$options['starts_with'];
        }
        if (($tested |= !empty($options['ends_with'])) && Utils::endsWith($value, $search, $options['case_sensitive'] ?? false)) {
            return (float)$options['ends_with'];
        }
        if ((!$tested || !empty($options['contains'])) && Utils::contains($value, $search, $options['case_sensitive'] ?? false)) {
            return (float)($options['contains'] ?? 1);
        }

        return 0;
    }

    /**
     * Get any changes based on data sent to update
     *
     * @return array
     */
    public function getChanges(): array
    {
        return $this->_changes ?? [];
    }

    /**
     * @return string
     */
    protected function getTypePrefix(): string
    {
        return 'o.';
    }

    /**
     * @param bool $prefix
     * @return string
     * @deprecated 1.6 Use `->getFlexType()` instead.
     */
    public function getType($prefix = false)
    {
        user_error(__CLASS__ . '::' . __FUNCTION__ . '() is deprecated since Grav 1.6, use ->getFlexType() method instead', E_USER_DEPRECATED);

        $type = $prefix ? $this->getTypePrefix() : '';

        return $type . $this->getFlexType();
    }

    /**
     * Alias of getBlueprint()
     *
     * @return Blueprint
     * @deprecated 1.6 Admin compatibility
     */
    public function blueprints()
    {
        return $this->getBlueprint();
    }

    /**
     * @param string|null $namespace
     * @return CacheInterface
     */
    public function getCache(string $namespace = null)
    {
        return $this->_flexDirectory->getCache($namespace);
    }

    /**
     * @param string|null $key
     * @return $this
     */
    public function setStorageKey($key = null)
    {
        $this->storage_key = $key ?? '';

        return $this;
    }

    /**
     * @param int $timestamp
     * @return $this
     */
    public function setTimestamp($timestamp = null)
    {
        $this->storage_timestamp = $timestamp ?? time();

        return $this;
    }

    /**
     * {@inheritdoc}
     * @see FlexObjectInterface::render()
     */
    public function render(string $layout = null, array $context = [])
    {
        if (!$layout) {
            $config = $this->getTemplateConfig();
            $layout = $config['object']['defaults']['layout'] ?? 'default';
        }

        $type = $this->getFlexType();

        $grav = Grav::instance();

        /** @var Debugger $debugger */
        $debugger = $grav['debugger'];
        $debugger->startTimer('flex-object-' . ($debugKey =  uniqid($type, false)), 'Render Object ' . $type . ' (' . $layout . ')');

        $key = $this->getCacheKey();

        // Disable caching if context isn't all scalars.
        if ($key) {
            foreach ($context as $value) {
                if (!\is_scalar($value)) {
                    $key = '';
                    break;
                }
            }
        }

        if ($key) {
            // Create a new key which includes layout and context.
            $key = md5($key . '.' . $layout . json_encode($context));
            $cache = $this->getCache('render');
        } else {
            $cache = null;
        }

        try {
            $data = $cache ? $cache->get($key) : null;

            $block = $data ? HtmlBlock::fromArray($data) : null;
        } catch (InvalidArgumentException $e) {
            $debugger->addException($e);

            $block = null;
        } catch (\InvalidArgumentException $e) {
            $debugger->addException($e);

            $block = null;
        }

        $checksum = $this->getCacheChecksum();
        if ($block && $checksum !== $block->getChecksum()) {
            $block = null;
        }

        if (!$block) {
            $block = HtmlBlock::create($key ?: null);
            $block->setChecksum($checksum);
            if (!$cache) {
                $block->disableCache();
            }

            $grav->fireEvent('onFlexObjectRender', new Event([
                'object' => $this,
                'layout' => &$layout,
                'context' => &$context
            ]));

            $output = $this->getTemplate($layout)->render(
                [
                    'grav' => $grav,
                    'config' => $grav['config'],
                    'block' => $block,
                    'directory' => $this->getFlexDirectory(),
                    'object' => $this,
                    'layout' => $layout
                ] + $context
            );

            if ($debugger->enabled()) {
                $name = $this->getKey() . ' (' . $type . ')';
                $output = "\n<!–– START {$name} object ––>\n{$output}\n<!–– END {$name} object ––>\n";
            }

            $block->setContent($output);

            try {
                $cache && $block->isCached() && $cache->set($key, $block->toArray());
            } catch (InvalidArgumentException $e) {
                $debugger->addException($e);
            }
        }

        $debugger->stopTimer('flex-object-' . $debugKey);

        return $block;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->getElements();
    }

    /**
     * {@inheritdoc}
     * @see FlexObjectInterface::prepareStorage()
     */
    public function prepareStorage(): array
    {
        return $this->getElements();
    }

    /**
     * @param string $name
     * @return $this
     */
    public function triggerEvent($name)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     * @see FlexObjectInterface::update()
     */
    public function update(array $data, array $files = [])
    {
        if ($data) {
            $blueprint = $this->getBlueprint();

            // Process updated data through the object filters.
            $this->filterElements($data);

            // Get currently stored data.
            $elements = $this->getElements();

            // Merge existing object to the test data to be validated.
            $test = $blueprint->mergeData($elements, $data);

            // Validate and filter elements and throw an error if any issues were found.
            $blueprint->validate($test + ['storage_key' => $this->getStorageKey(), 'timestamp' => $this->getTimestamp()]);
            $data = $blueprint->filter($data, false, true);

            // Finally update the object.
            foreach ($blueprint->flattenData($data) as $key => $value) {
                if ($value === null) {
                    $this->unsetNestedProperty($key);
                } else {
                    $this->setNestedProperty($key, $value);
                }
            }

            // Store the changes
            $this->_changes = Utils::arrayDiffMultidimensional($this->getElements(), $elements);
        }

        if ($files && method_exists($this, 'setUpdatedMedia')) {
            $this->setUpdatedMedia($files);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     * @see FlexObjectInterface::create()
     */
    public function create(string $key = null)
    {
        if ($key) {
            $this->setStorageKey($key);
        }

        if ($this->exists()) {
            throw new \RuntimeException('Cannot create new object (Already exists)');
        }

        return $this->save();
    }

    /**
     * @param string|null $key
     * @return FlexObject|FlexObjectInterface
     */
    public function createCopy(string $key = null)
    {
        $this->markAsCopy();

        return $this->create($key);
    }

    protected function markAsCopy()
    {
        $meta = $this->getMetaData();
        $meta['copy'] = true;
        $this->_meta = $meta;
    }

    /**
     * {@inheritdoc}
     * @see FlexObjectInterface::save()
     */
    public function save()
    {
        $this->triggerEvent('onBeforeSave');

        $storage = $this->getFlexDirectory()->getStorage();

        $storageKey = $this->getStorageKey() ?:  '@@' . spl_object_hash($this);

        $result = $storage->replaceRows([$storageKey => $this->prepareStorage()]);

        $value = reset($result);
        $meta = $value['__META'] ?? null;
        if ($meta) {
            $this->_meta = $meta;
        }

        if ($value) {
            $storageKey = $meta['storage_key'] ?? (string)key($result);
            if ($storageKey !== '') {
                $this->setStorageKey($storageKey);
            }

            $newKey = $meta['key'] ?? ($this->hasKey() ? $this->getKey() : null);
            $this->setKey($newKey ?? $storageKey);
        }

        // FIXME: For some reason locator caching isn't cleared for the file, investigate!
        $locator = Grav::instance()['locator'];
        $locator->clearCache();

        if (method_exists($this, 'saveUpdatedMedia')) {
            $this->saveUpdatedMedia();
        }

        try {
            $this->getFlexDirectory()->reloadIndex();
            if (method_exists($this, 'clearMediaCache')) {
                $this->clearMediaCache();
            }
        } catch (\Exception $e) {
            /** @var Debugger $debugger */
            $debugger = Grav::instance()['debugger'];
            $debugger->addException($e);

            // Caching failed, but we can ignore that for now.
        }

        $this->triggerEvent('onAfterSave');

        return $this;
    }

    /**
     * {@inheritdoc}
     * @see FlexObjectInterface::delete()
     */
    public function delete()
    {
        if (!$this->exists()) {
            return $this;
        }

        $this->triggerEvent('onBeforeDelete');

        $this->getFlexDirectory()->getStorage()->deleteRows([$this->getStorageKey() => $this->prepareStorage()]);

        try {
            $this->getFlexDirectory()->reloadIndex();
            if (method_exists($this, 'clearMediaCache')) {
                $this->clearMediaCache();
            }
        } catch (\Exception $e) {
            /** @var Debugger $debugger */
            $debugger = Grav::instance()['debugger'];
            $debugger->addException($e);

            // Caching failed, but we can ignore that for now.
        }

        $this->triggerEvent('onAfterDelete');

        return $this;
    }

    /**
     * {@inheritdoc}
     * @see FlexObjectInterface::getBlueprint()
     */
    public function getBlueprint(string $name = '')
    {
        $blueprint = $this->doGetBlueprint($name);
        $blueprint->setScope('object');
        $blueprint->setObject($this);

        return $blueprint->init();
    }

    /**
     * @param string $name
     * @return Blueprint
     */
    protected function doGetBlueprint(string $name = ''): Blueprint
    {
        return $this->_flexDirectory->getBlueprint($name ? '.' . $name : $name);
    }

    /**
     * {@inheritdoc}
     * @see FlexObjectInterface::getForm()
     */
    public function getForm(string $name = '', array $options = null)
    {
        if (!isset($this->_forms[$name])) {
            $this->_forms[$name] = $this->createFormObject($name, $options);
        }

        return $this->_forms[$name];
    }

    /**
     * {@inheritdoc}
     * @see FlexObjectInterface::getDefaultValue()
     */
    public function getDefaultValue(string $name, string $separator = null)
    {
        $separator = $separator ?: '.';
        $path = explode($separator, $name) ?: [];
        $offset = array_shift($path) ?? '';

        $current = $this->getDefaultValues();

        if (!isset($current[$offset])) {
            return null;
        }

        $current = $current[$offset];

        while ($path) {
            $offset = array_shift($path);

            if ((\is_array($current) || $current instanceof \ArrayAccess) && isset($current[$offset])) {
                $current = $current[$offset];
            } elseif (\is_object($current) && isset($current->{$offset})) {
                $current = $current->{$offset};
            } else {
                return null;
            }
        };

        return $current;
    }

    /**
     * @return array
     */
    public function getDefaultValues(): array
    {
        return $this->getBlueprint()->getDefaults();
    }

    /**
     * {@inheritdoc}
     * @see FlexObjectInterface::getFormValue()
     */
    public function getFormValue(string $name, $default = null, string $separator = null)
    {
        if ($name === 'storage_key') {
            return $this->getStorageKey();
        }
        if ($name === 'storage_timestamp') {
            return $this->getTimestamp();
        }

        return $this->getNestedProperty($name, $default, $separator);
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @param string|null $separator
     * @return mixed
     *
     * @deprecated 1.6 Use ->getFormValue() method instead.
     */
    public function value($name, $default = null, $separator = null)
    {
        return $this->getFormValue($name, $default, $separator);
    }

    /**
     * Returns a string representation of this object.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getFlexKey();
    }

    public function __debugInfo()
    {
        return [
            'type:private' => $this->getFlexType(),
            'storage_key:protected' => $this->getStorageKey(),
            'storage_timestamp:protected' => $this->getTimestamp(),
            'key:private' => $this->getKey(),
            'elements:private' => $this->getElements(),
            'storage:private' => $this->getMetaData()
        ];
    }

    /**
     * @return array
     */
    protected function doSerialize(): array
    {
        return [
            'type' => $this->getFlexType(),
            'key' => $this->getKey(),
            'elements' => $this->getElements(),
            'storage' => $this->getMetaData()
        ];
    }

    /**
     * @param array $serialized
     */
    protected function doUnserialize(array $serialized): void
    {
        $type = $serialized['type'] ?? 'unknown';

        if (!isset($serialized['key'], $serialized['type'], $serialized['elements'])) {
            throw new \InvalidArgumentException("Cannot unserialize '{$type}': Bad data");
        }

        $grav = Grav::instance();
        /** @var Flex|null $flex */
        $flex = $grav['flex_objects'] ?? null;
        $directory = $flex ? $flex->getDirectory($type) : null;
        if (!$directory) {
            throw new \InvalidArgumentException("Cannot unserialize '{$type}': Not found");
        }
        $this->setFlexDirectory($directory);
        $this->setStorage($serialized['storage']);
        $this->setKey($serialized['key']);
        $this->setElements($serialized['elements']);
    }

    /**
     * @param FlexDirectory $directory
     */
    public function setFlexDirectory(FlexDirectory $directory): void
    {
        $this->_flexDirectory = $directory;
    }

    /**
     * @param array $storage
     */
    protected function setStorage(array $storage): void
    {
        $this->_meta = $storage;
    }

    /**
     * @return array
     * @deprecated 1.7 Use `->getMetaData()` instead.
     */
    protected function getStorage(): array
    {
        return $this->getMetaData();
    }

    /**
     * @param string $type
     * @param string $property
     * @return FlexCollectionInterface
     */
    protected function getCollectionByProperty($type, $property)
    {
        $directory = $this->getRelatedDirectory($type);
        $collection = $directory->getCollection();
        $list = $this->getNestedProperty($property) ?: [];

        /** @var FlexCollection $collection */
        $collection = $collection->filter(function ($object) use ($list) {
            return \in_array($object->id, $list, true);
        });

        return $collection;
    }

    /**
     * @param string $type
     * @return FlexDirectory
     * @throws \RuntimeException
     */
    protected function getRelatedDirectory($type): FlexDirectory
    {
        /** @var Flex $flex */
        $flex = Grav::instance()['flex_objects'];
        $directory = $flex->getDirectory($type);
        if (!$directory) {
            throw new \RuntimeException(ucfirst($type). ' directory does not exist!');
        }

        return $directory;
    }

    /**
     * @return array
     */
    protected function getTemplateConfig()
    {
        $config = $this->getFlexDirectory()->getConfig('site.templates', []);
        $defaults = array_replace($config['defaults'] ?? [], $config['object']['defaults'] ?? []);
        $config['object']['defaults'] = $defaults;

        return $config;
    }

    /**
     * @param string $layout
     * @return array
     */
    protected function getTemplatePaths(string $layout): array
    {
        $config = $this->getTemplateConfig();
        $type = $this->getFlexType();
        $defaults = $config['object']['defaults'] ?? [];

        $ext = $defaults['ext'] ?? '.html.twig';
        $types = array_unique(array_merge([$type], (array)($defaults['type'] ?? null)));
        $paths = $config['object']['paths'] ?? [
                'flex/{TYPE}/object/{LAYOUT}{EXT}',
                'flex-objects/layouts/{TYPE}/object/{LAYOUT}{EXT}'
            ];
        $table = ['TYPE' => '%1$s', 'LAYOUT' => '%2$s', 'EXT' => '%3$s'];

        $lookups = [];
        foreach ($paths as $path) {
            $path = Utils::simpleTemplate($path, $table);
            foreach ($types as $type) {
                $lookups[] = sprintf($path, $type, $layout, $ext);
            }
        }

        return array_unique($lookups);
    }

    /**
     * @param string $layout
     * @return Template|TemplateWrapper
     * @throws LoaderError
     * @throws SyntaxError
     */
    protected function getTemplate($layout)
    {
        $grav = Grav::instance();

        /** @var Twig $twig */
        $twig = $grav['twig'];

        try {
            return $twig->twig()->resolveTemplate($this->getTemplatePaths($layout));
        } catch (LoaderError $e) {
            /** @var Debugger $debugger */
            $debugger = Grav::instance()['debugger'];
            $debugger->addException($e);

            return $twig->twig()->resolveTemplate(['flex/404.html.twig']);
        }
    }

    /**
     * Filter data coming to constructor or $this->update() request.
     *
     * NOTE: The incoming data can be an arbitrary array so do not assume anything from its content.
     *
     * @param array $elements
     */
    protected function filterElements(array &$elements): void
    {
        if (isset($elements['storage_key'])) {
            $elements['storage_key'] = trim($elements['storage_key']);
        }
        if (isset($elements['storage_timestamp'])) {
            $elements['storage_timestamp'] = (int)$elements['storage_timestamp'];
        }

        unset($elements['_post_entries_save']);
    }

    /**
     * This methods allows you to override form objects in child classes.
     *
     * @param string $name Form name
     * @param array $options Form optiosn
     * @return FlexFormInterface
     */
    protected function createFormObject(string $name, array $options = null)
    {
        return new FlexForm($name, $this, $options);
    }

    /**
     * @param string $action
     * @return string
     */
    protected function getAuthorizeAction(string $action): string
    {
        // Handle special action save, which can mean either update or create.
        if ($action === 'save') {
            $action = $this->exists() ? 'update' : 'create';
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
