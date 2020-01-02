<?php

namespace Drupal\config_patch\Plugin\config_patch\output;

/**
 * Interface CliOutputPluginInterface.
 *
 * @package Drupal\config_patch\Plugin\config_patch\output
 */
interface CliOutputPluginInterface {

  /**
   * Output in command line interface.
   *
   * @param array $patches
   *   The patches array of strings ready for output.
   * @param array $config_changes
   *   The array of changes with file name and action.
   * @param array $params
   *   Array of additional parameters.
   */
  public function outputCli(array $patches, array $config_changes = [], array $params = []);

}
