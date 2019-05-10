<?php

namespace Drupal\config_patch\Plugin\config_patch\output;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

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
   * Return the action (of this plugin).
   */
  public function getAction() {
    return $this->pluginDefinition['action'];
  }

  /**
   * Output text to the browser.
   *
   * Override this.
   */
  public function output(array $patches, FormStateInterface $form_state) {
  }

  /**
   * Alter the patch form.
   */
  public function alterForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

}
