<?php

namespace Drupal\config_patch;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Symfony\Component\DependencyInjection\Container;

/**
 * Provides a plugin manager for config_patch output.
 *
 * @see plugin_api
 */
class PluginManager extends DefaultPluginManager {

  /**
   * Constructs a OutputManager object.
   *
   * @param string $type
   *   Plugin type.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(string $type, \Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $camel_type = Container::camelize($type);

    parent::__construct(
      "Plugin/config_patch/$type",
      $namespaces,
      $module_handler,
      "Drupal\\config_patch\\Plugin\\config_patch\\$type\\${camel_type}PluginInterface",
      "Drupal\config_patch\Annotation\ConfigPatch" . $camel_type
    );

    $this->alterInfo('config_patch_info_' . $type);
    $this->setCacheBackend($cache_backend, 'config_patch:' . $type);
  }

}
