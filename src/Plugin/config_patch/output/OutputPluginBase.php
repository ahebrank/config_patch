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
  public function output(array $patches) {
    $output = "";
    foreach ($patches as $collection_patches) {
      foreach ($collection_patches as $config_name => $patch) {
        $output .= $patch;
      }
    }
    header("Content-Type: text/plain");
    echo $output;
    exit();
  }

}
