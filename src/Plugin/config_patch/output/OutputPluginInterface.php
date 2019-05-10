<?php

namespace Drupal\config_patch\Plugin\config_patch\output;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

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
   * Return the action (of this plugin).
   */
  public function getAction();

  /**
   * Do something with the patches.
   *
   * @param array $patches
   *   The array of patches (per collection).
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the Config Patch form at submission.
   */
  public function output(array $patches, FormStateInterface $form_state);

  /**
   * Modify the patch form.
   *
   * @param array $form
   *   The form build.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the Config Patch form at submission.
   */
  public function alterForm(array $form, FormStateInterface $form_state);

}
