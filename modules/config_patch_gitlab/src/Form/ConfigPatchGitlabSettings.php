<?php

namespace Drupal\config_patch_gitlab\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for module.
 */
class ConfigPatchGitlabSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_patch_gitlab_settings_form';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['config_patch_gitlab.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('config_patch_gitlab.settings');
    $form['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gitlab MR patch email'),
      '#description' => $this->t('See https://docs.gitlab.com/ee/user/project/merge_requests/#create-new-merge-requests-by-email'),
      '#default_value' => $config->get('email') ?? '',
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
      'email',
    ];
    $config = $this->config('config_patch_gitlab.settings');
    foreach ($config_fields as $config_field) {
      $config->set($config_field, $config_values[$config_field])
        ->save();
    }
    parent::submitForm($form, $form_state);
  }

}
