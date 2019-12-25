<?php

declare(strict_types=1);

/**
 * @package    Grav\Framework\Flex
 *
 * @copyright  Copyright (C) 2015 - 2019 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Framework\Flex\Storage;

use Grav\Framework\Flex\Interfaces\FlexStorageInterface;

/**
 * Class FileStorage
 * @package Grav\Framework\Flex\Storage
 */
class FileStorage extends FolderStorage
{
    /**
     * {@inheritdoc}
     * @see FlexStorageInterface::__construct()
     */
    public function __construct(array $options)
    {
        $this->dataPattern = '{FOLDER}/{KEY}{EXT}';

        if (!isset($options['formatter']) && isset($options['pattern'])) {
            $options['formatter'] = $this->detectDataFormatter($options['pattern']);
        }

        parent::__construct($options);
    }

    /**
     * {@inheritdoc}
     * @see FlexStorageInterface::getMediaPath()
     */
    public function getMediaPath(string $key = null): ?string
    {
        $path = $this->getStoragePath();
        if (!$path) {
            return null;
        }

        return $key ? "{$path}/{$key}" : $path;
    }

    /**
     * {@inheritdoc}
     */
    protected function getKeyFromPath(string $path): string
    {
        return basename($path, $this->dataFormatter->getDefaultFileExtension());
    }

    /**
     * {@inheritdoc}
     */
    protected function buildIndex(): array
    {
        $path = $this->getStoragePath();
        if (!$path || !file_exists($path)) {
            return [];
        }

        $flags = \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS;
        $iterator = new \FilesystemIterator($path, $flags);
        $list = [];
        /** @var \SplFileInfo $info */
        foreach ($iterator as $filename => $info) {
            if (!$info->isFile() || !($key = $this->getKeyFromPath($filename)) || strpos($info->getFilename(), '.') === 0) {
                continue;
            }

            $list[$key] = $this->getObjectMeta($key);
        }

        ksort($list, SORT_NATURAL);

        return $list;
    }
}
