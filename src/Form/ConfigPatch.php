<?php

namespace Drupal\config_patch\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\ConfigManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Construct the storage changes in a configuration patch form.
 */
class ConfigPatch extends FormBase {

  /**
   * The sync configuration object.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $syncStorage;

  /**
   * The active configuration object.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeStorage;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(StorageInterface $sync_storage, StorageInterface $active_storage, ConfigManagerInterface $config_manager) {
    $this->syncStorage = $sync_storage;
    $this->activeStorage = $active_storage;
    $this->configManager = $config_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.storage.sync'),
      $container->get('config.storage'),
      $container->get('config.manager')
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
      '#value' => $this->t('Create patch'),
    ];
    $source_list = $this->syncStorage->listAll();
    $storage_comparer = new StorageComparer($this->activeStorage, $this->syncStorage, $this->configManager);
    if (empty($source_list) || !$storage_comparer->createChangelist()->hasChanges()) {
      $form['no_changes'] = [
        '#type' => 'table',
        '#header' => [$this->t('Name'), $this->t('Operations')],
        '#rows' => [],
        '#empty' => $this->t('There are no configuration changes to import.'),
      ];
      $form['actions']['#access'] = FALSE;
      return $form;
    }
    elseif (!$storage_comparer->validateSiteUuid()) {
      $this->messenger()->addError($this->t('The staged configuration cannot be imported, because it originates from a different site than this site. You can only synchronize configuration between cloned instances of this site.'));
      $form['actions']['#access'] = FALSE;
      return $form;
    }

    $form['info'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Select config items to add to patch.'),
    ];

    // Store the comparer for use in the submit.
    $form_state->set('storage_comparer', $storage_comparer);

    $collections = $storage_comparer->getAllCollectionNames();
    $form_state->set('collections', $collections);

    foreach ($collections as $collection) {
      if ($collection != StorageInterface::DEFAULT_COLLECTION) {
        $form[$collection]['collection_heading'] = [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => $this->t('@collection configuration collection', ['@collection' => $collection]),
        ];
      }

      $form[$collection]['list'] = [
        '#type' => 'tableselect',
        '#header' => [
          'name' => $this->t('Name'),
          'type' => $this->t('Change Type'),
        ],
      ];

      foreach ($storage_comparer->getChangelist(NULL, $collection) as $config_change_type => $config_names) {
        if (empty($config_names)) {
          continue;
        }

        foreach ($config_names as $config_name) {
          if ($config_change_type == 'rename') {
            $names = $storage_comparer->extractRenameNames($config_name);
            $config_name = $this->t('@source_name to @target_name', ['@source_name' => $names['old_name'], '@target_name' => $names['new_name']]);
          }

          $form[$collection]['list']['#options'][$config_name] = [
            'name' => $config_name,
            'type' => $config_change_type,
          ];
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('collections') as $collection) {
      $list = $form_state->getValue($collection);
    }
  }

}
