<?php

namespace Drupal\config_patch\Form;

use Drupal\config_patch\ConfigCompare;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Construct the storage changes in a configuration patch form.
 */
class ConfigPatch extends FormBase {

  /**
   * @var \Drupal\config_patch\ConfigCompare
   *   The config comparison service.
   */
  protected $configCompare;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigCompare $configCompare) {
    $this->configCompare = $configCompare;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config_patch.config_compare')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_patch_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->configCompare->getOutputPlugin()->getAction(),
    ];
    $changes = $this->configCompare->getChangelist();
    if (empty($changes)) {
      $form['no_changes'] = [
        '#type' => 'table',
        '#header' => [$this->t('Name'), $this->t('Change Type')],
        '#rows' => [],
        '#empty' => $this->t('There are no configuration changes to patch.'),
      ];
      $form['actions']['#access'] = FALSE;
      return $form;
    }

    $form_state->set('collections', $changes);

    $form['info'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Select config items to add to patch.'),
    ];

    foreach ($changes as $collection_name => $collection) {
      $list_key = $this->getListKey($collection_name);
      if ($collection_name != StorageInterface::DEFAULT_COLLECTION) {
        $form[$collection_name]['collection_heading'] = [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => $this->t('@collection configuration collection', ['@collection' => $collection_name]),
        ];
      }
      $form[$collection_name][$list_key] = [
        '#type' => 'tableselect',
        '#header' => [
          'name' => $this->t('Name'),
          'type' => $this->t('Change Type'),
        ],
      ];

      foreach ($collection as $config_name => $data) {
        $form[$collection_name][$list_key]['#options'][$config_name] = [
          'name' => $data['name'],
          'type' => $data['type'],
        ];
      }
    }

    // Let the plugin modify the form.
    $form = $this->configCompare->getOutputPlugin()->alterForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config_for_patch = FALSE;
    foreach ($form_state->get('collections') as $collection_name => $collection) {
      $list_key = $this->getListKey($collection_name);
      $list = $form_state->getValue($list_key);

      if (count(array_filter($list)) > 0) {
        $config_for_patch = TRUE;
        break;
      }
    }

    if (!$config_for_patch) {
      $form_state
        ->setErrorByName('collections', $this->t('No config selected to patch.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $list_to_export = [];
    foreach ($form_state->get('collections') as $collection_name => $collection) {
      $list_key = $this->getListKey($collection_name);
      $list_to_export[$collection_name] = $form_state->getValue($list_key);
    }
    $collection_patches = $this->configCompare->collectPatches($list_to_export);
    $this->configCompare->getOutputPlugin()->output($collection_patches, $form_state);
  }

  /**
   * Gets the list form element key according to collection name.
   *
   * @param string $collection_name
   *   Collection name.
   *
   * @return string
   *   The key to be used in the form element.
   */
  private function getListKey($collection_name) {
    $list_key = 'list';
    if ($collection_name != StorageInterface::DEFAULT_COLLECTION) {
      $list_key .= '_' . preg_replace('/[^a-z0-9_]+/', '_', $collection_name);
    }
    return $list_key;
  }

}
