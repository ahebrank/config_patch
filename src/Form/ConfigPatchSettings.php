<?php

namespace Drupal\config_patch\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for module.
 */
class ConfigPatchSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_patch_settings_form';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['config_patch.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('config_patch.settings');
    $form['config_base_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Config base path for patch'),
      '#default_value' => $config->get('config_base_path') ?? '',
      '#size' => 60,
      '#maxlength' => 60,
    ];
    
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config_values = $form_state->getValues();
    $config_fields = [
      'config_base_path',
    ];
    $config = $this->config('config_patch.settings');
    foreach ($config_fields as $config_field) {
      $config->set($config_field, $config_values[$config_field])
        ->save();
    }
    parent::submitForm($form, $form_state);
  }

}
