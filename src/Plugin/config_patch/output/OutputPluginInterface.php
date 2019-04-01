<?php

namespace Drupal\config_patch\Plugin\config_patch\output;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Patch output specification.
 */
interface OutputPluginInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Return ID.
   */
  public function getId();

  /**
   * Return label.
   */
  public function getLabel();

  /**
   * Do something with the patches.
   *
   * @param array $patches
   *   The array of patches (per collection).
   * @param array $config_names
   *   The names of all the config items affected by the patches.
   */
  public function output(array $patches, array $config_names);

}
