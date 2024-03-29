<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb09a2b717d73bcbbb4f4383bf7069e09
{
    public static $prefixLengthsPsr4 = array (
        'E' => 
        array (
            'Enesisrl\\LaravelGoogleMerchantFeedParser\\' => 41,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Enesisrl\\LaravelGoogleMerchantFeedParser\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitb09a2b717d73bcbbb4f4383bf7069e09::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitb09a2b717d73bcbbb4f4383bf7069e09::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitb09a2b717d73bcbbb4f4383bf7069e09::$classMap;

        }, null, ClassLoader::class);
    }
}
