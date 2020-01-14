<?php


namespace Drupal\config_patch\Plugin\Derivative;


use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class OutputLocalTasks.
 *
 * @package Drupal\config_patch\Plugin\Derivative
 */
class OutputLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * OutputLocalTasks constructor.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(PluginManagerInterface $pluginManager, ConfigFactoryInterface $config_factory) {
    $this->pluginManager = $pluginManager;
    $this->config = $config_factory->get('config_patch.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->pluginManager->getDefinitions() as $plugin_id => $definition) {
      // Implement dynamic logic to provide values for the same keys as in example.links.task.yml.
      $this->derivatives[$plugin_id . '.task_id'] = $base_plugin_definition;
      $this->derivatives[$plugin_id . '.task_id']['title'] = $definition['label'];
      $this->derivatives[$plugin_id . '.task_id']['route_parameters'] = ['plugin_id' => $plugin_id];
      if ($plugin_id == $this->config->get('output_plugin')) {
        $this->derivatives[$plugin_id . '.task_id']['route_name'] = $base_plugin_definition['parent_id'];
        // Emulate default logic because without the base plugin id we can't
        // change the base_route.
        $this->derivatives[$plugin_id . '.task_id']['weight'] = -10;

        unset($this->derivatives[$plugin_id . '.task_id']['route_parameters']);
      }
    }
    return $this->derivatives;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.config_patch.output'),
      $container->get('config.factory')
    );
  }

}
