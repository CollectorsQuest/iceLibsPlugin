<?php

require_once __DIR__ .'/../vendor/Symfony/Component/ClassLoader/UniversalClassLoader.php';
require_once __DIR__ .'/../vendor/Symfony/Component/ClassLoader/ApcUniversalClassLoader.php';

use Symfony\Component\ClassLoader\UniversalClassLoader;
use Symfony\Component\ClassLoader\ApcUniversalClassLoader;

class IceClassLoader
{

  /**
   * @var UniversalClassLoader
   */
  static protected $loader = null;

  /**
   * @param bool $cache
   * @param string $prefix
   */
  static public function initialize($cache = false, $prefix = 'IceClassLoader.')
  {
    if (self::$loader) {
      return;
    }

    if ($cache) {
      self::$loader = new ApcUniversalClassLoader($prefix);
    } else {
      self::$loader = new UniversalClassLoader();
    }

    IceCoreAutoload::register();
    self::$loader->register();
  }

  /**
   *
   * @return UniversalClassLoader
   */
  static public function getLoader()
  {
    if (!self::$loader) {
      throw new \RuntimeException('IceClassLoader has not been initialized.');
    }

    return self::$loader;
  }

}
