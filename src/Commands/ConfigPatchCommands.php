<?php

namespace Drupal\config_patch\Commands;

use Drupal\config_patch\ConfigCompare;
use Drupal\config_patch\Plugin\config_patch\output\CliOutputPluginInterface;
use Drush\Commands\DrushCommands;

/**
 * Class ConfigPatchCommands.
 *
 * @package Drupal\config_patch\Commands
 */
class ConfigPatchCommands extends DrushCommands {

  /**
   * @var \Drupal\config_patch\ConfigCompare
   */
  protected $configCompare;

  /**
   * ConfigPatchCommands constructor.
   *
   * @param \Drupal\config_patch\ConfigCompare $configCompare
   */
  public function __construct(ConfigCompare $configCompare) {
    $this->configCompare = $configCompare;
  }

  /**
   * Create a configuration patch.
   *
   * @command config-patch-output
   * @param $plugin_id Id of the output plugin.
   * @option filename Filename where to output the patch.
   * @option collections A comma separated list of included collections.
   * @aliases cpo
   */
  public function outputPatch($plugin_id, $options = ['filename' => '', 'collections' => '']) {
    $this->configCompare->setOutputPlugin($plugin_id);
    $change_list = $this->configCompare->getChangelist();
    $patches = $this->configCompare->collectPatches();
    if (!empty($options['collections'])) {
      $collections = explode(',', $options['collections']);
      foreach ($patches as $collection_name => $collection) {
        if (!in_array($collection_name, $collections)) {
          unset($patches[$collection_name]);
        }
      }
    }
    if ($this->configCompare->getOutputPlugin() instanceof CliOutputPluginInterface) {
      $output = $this->configCompare->getOutputPlugin()
        ->outputCli($patches, $change_list);
      if (!empty($options['filename'])) {
        file_put_contents($options['filename'], $output);
      }
      else {
        $this->output()->write($output);
      }
    }
    else {
      $this->output()->writeln('This plugin does not support cli output');
    }
  }

}
