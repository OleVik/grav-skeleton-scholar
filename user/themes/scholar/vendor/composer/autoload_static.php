<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit785cb74a939a47c9971b9fe8f0e7ca59
{
    public static $prefixLengthsPsr4 = array (
        'h' => 
        array (
            'hanneskod\\classtools\\' => 21,
        ),
        'S' => 
        array (
            'Symfony\\Component\\Finder\\' => 25,
            'Spatie\\SchemaOrg\\' => 17,
        ),
        'P' => 
        array (
            'PhpParser\\' => 10,
        ),
        'H' => 
        array (
            'HaydenPierce\\ClassFinder\\UnitTest\\' => 34,
            'HaydenPierce\\ClassFinder\\' => 25,
        ),
        'G' => 
        array (
            'Grav\\Theme\\Scholar\\' => 19,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'hanneskod\\classtools\\' => 
        array (
            0 => __DIR__ . '/..' . '/hanneskod/classtools/src',
        ),
        'Symfony\\Component\\Finder\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/finder',
        ),
        'Spatie\\SchemaOrg\\' => 
        array (
            0 => __DIR__ . '/..' . '/spatie/schema-org/src',
        ),
        'PhpParser\\' => 
        array (
            0 => __DIR__ . '/..' . '/nikic/php-parser/lib/PhpParser',
        ),
        'HaydenPierce\\ClassFinder\\UnitTest\\' => 
        array (
            0 => __DIR__ . '/..' . '/haydenpierce/class-finder/test/unit',
        ),
        'HaydenPierce\\ClassFinder\\' => 
        array (
            0 => __DIR__ . '/..' . '/haydenpierce/class-finder/src',
        ),
        'Grav\\Theme\\Scholar\\' => 
        array (
            0 => __DIR__ . '/../..' . '/classes',
        ),
    );

    public static $prefixesPsr0 = array (
        'P' => 
        array (
            'PHPExtra\\Sorter' => 
            array (
                0 => __DIR__ . '/..' . '/phpextra/sorter/src',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit785cb74a939a47c9971b9fe8f0e7ca59::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit785cb74a939a47c9971b9fe8f0e7ca59::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit785cb74a939a47c9971b9fe8f0e7ca59::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}