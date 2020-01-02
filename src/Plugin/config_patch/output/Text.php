<?php

namespace Drupal\config_patch\Plugin\config_patch\output;

use Drupal\Core\Form\FormStateInterface;

/**
 * Simple text output of the patches.
 *
 * @ConfigPatchOutput(
 *  id = "config_patch_output_text",
 *  label = @Translation("Plain text output to browser"),
 *  action = @Translation("Create text patch")
 * )
 */
class Text extends OutputPluginBase implements OutputPluginInterface, CliOutputPluginInterface {

  /**
   * Prepare the output.
   *
   * @param $patches
   *   Array of the diff of files.
   *
   * @return string
   */
  private function prepareOutput($patches) {
    $output = "";
    foreach ($patches as $collection_patches) {
      foreach ($collection_patches as $config_name => $patch) {
        $output .= $patch;
      }
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function output(array $patches, FormStateInterface $form_state) {
    $output = $this->prepareOutput($patches);
    header("Content-Type: text/plain");
    echo $output;
    exit();
  }

  /**
   * {@inheritdoc}
   */
  public function outputCli(array $patches, array $config_changes = [], array $params = []) {
    return $this->prepareOutput($patches);
  }

}
