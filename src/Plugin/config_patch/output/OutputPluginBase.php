<?php

namespace Drupal\config_patch\Plugin\config_patch\output;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base output plugin.
 */
class OutputPluginBase extends PluginBase implements OutputPluginInterface {

  /**
   * Return the id.
   */
  public function getId() {
    return $this->pluginId;
  }

  /**
   * Return the label.
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * Output text to the browser.
   *
   * Override this.
   */
  public function output(array $patches, array $config_names) {
    $output = implode("\n", $patches);
    header("Content-Type: text/plain");
    echo $output;
    exit();
  }

}
