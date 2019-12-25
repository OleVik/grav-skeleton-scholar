<?php

declare(strict_types=1);

/**
 * @package    Grav\Common\Flex
 *
 * @copyright  Copyright (C) 2015 - 2019 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Common\Flex\Users;

use Grav\Common\User\Interfaces\UserCollectionInterface;
use Grav\Common\User\Interfaces\UserInterface;
use Grav\Framework\Flex\FlexCollection;

class UserCollection extends FlexCollection implements UserCollectionInterface
{
    /**
     * Load user account.
     *
     * Always creates user object. To check if user exists, use $this->exists().
     *
     * @param string $username
     * @return UserObject
     */
    public function load($username): UserInterface
    {
        if ($username !== '') {
            $key = mb_strtolower($username);
            $user = $this->get($key);
            if ($user) {
                return $user;
            }
        } else {
            $key = '';
        }

        $directory = $this->getFlexDirectory();

        /** @var UserObject $object */
        $object = $directory->createObject(
            [
                'username' => $username,
                'state' => 'enabled'
            ],
            $key
        );

        return $object;
    }

    /**
     * Find a user by username, email, etc
     *
     * @param string $query the query to search for
     * @param string|string[] $fields the fields to search
     * @return UserObject
     */
    public function find($query, $fields = ['username', 'email']): UserInterface
    {
        if (is_string($query) && $query !== '') {
            foreach ((array)$fields as $field) {
                if ($field === 'key') {
                    $user = $this->get($query);
                } elseif ($field === 'storage_key') {
                    $user = $this->withKeyField('storage_key')->get($query);
                } elseif ($field === 'flex_key') {
                    $user = $this->withKeyField('flex_key')->get($query);
                } elseif ($field === 'username') {
                    $user = $this->get(mb_strtolower($query));
                } else {
                    $user = parent::find($query, $field);
                }
                if ($user) {
                    return $user;
                }
            }
        }

        return $this->load('');
    }

    /**
     * Delete user account.
     *
     * @param string $username
     * @return bool True if user account was found and was deleted.
     */
    public function delete($username): bool
    {
        $user = $this->load($username);

        $exists = $user->exists();
        if ($exists) {
            $user->delete();
        }

        return $exists;
    }
}
