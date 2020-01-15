<?php

namespace Drupal\config_patch\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for module.
 */
class ConfigPatchSettings extends ConfigFormBase {

  /**
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $outputPluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->outputPluginManager = $container->get('plugin.manager.config_patch.output');
    return $instance;
  }

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
      '#title' => $this->t('Config base path'),
      '#description' => $this->t('This path will be prefixed to config files when generating the patch. Depending on your needs, this path will usually be relative to the root of the Drupal installation or the root of the source repository.'),
      '#default_value' => $config->get('config_base_path') ?? '',
      '#size' => 60,
      '#maxlength' => 60,
    ];

    $output_opts = [];
    foreach ($this->outputPluginManager->getDefinitions() as $id => $def) {
      $output_opts[$id] = $def['label'];
    }
    $form['output_plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Default output plugin'),
      '#default_value' => $config->get('output_plugin') ?? 'text',
      '#options' => $output_opts,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Require a relative path for the config base path.
    $base_path = $form_state->getValue('config_base_path');
    if (strpos($base_path, '/') === 0) {
      $form_state
        ->setErrorByName('config_base_path', $this->t('Config base path must be relative (and not start with "/").'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config_values = $form_state->getValues();
    $config_fields = [
      'config_base_path',
      'output_plugin',
    ];
    $config = $this->config('config_patch.settings');
    foreach ($config_fields as $config_field) {
      $config->set($config_field, $config_values[$config_field])
        ->save();
    }
    parent::submitForm($form, $form_state);
  }

}
