<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit569cc34ce4649bd669b208ce872b538e
{
    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit569cc34ce4649bd669b208ce872b538e::$classMap;

        }, null, ClassLoader::class);
    }
}
