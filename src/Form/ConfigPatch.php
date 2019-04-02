<?php

namespace Drupal\config_patch\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\config_patch\PluginManager;
use Tmp\Diff\Differ;
use Tmp\Diff\Output\StrictUnifiedDiffOutputBuilder;

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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The output plugin manager.
   *
   * @var \Drupal\config_manager\PluginManager
   */
  protected $outputPluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(StorageInterface $sync_storage, StorageInterface $active_storage, ConfigManagerInterface $config_manager, ConfigFactoryInterface $config_factory, PluginManager $output_plugin_manager) {
    $this->syncStorage = $sync_storage;
    $this->activeStorage = $active_storage;
    $this->configManager = $config_manager;
    $this->configFactory = $config_factory;
    $this->outputPluginManager = $output_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.storage.sync'),
      $container->get('config.storage'),
      $container->get('config.manager'),
      $container->get('config.factory'),
      $container->get('plugin.manager.config_patch.output')
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
      '#value' => $this->getOutputPlugin()->getAction(),
    ];
    $source_list = $this->syncStorage->listAll();
    $storage_comparer = new StorageComparer($this->activeStorage, $this->syncStorage, $this->configManager);
    if (empty($source_list) || !$storage_comparer->createChangelist()->hasChanges()) {
      $form['no_changes'] = [
        '#type' => 'table',
        '#header' => [$this->t('Name'), $this->t('Operations')],
        '#rows' => [],
        '#empty' => $this->t('There are no configuration changes to patch.'),
      ];
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
    $storage_comparer = new StorageComparer($this->activeStorage, $this->syncStorage, $this->configManager);
    $config = $this->configFactory->get('config_patch.settings');
    $collection_patches = [];

    foreach ($form_state->get('collections') as $collection) {
      if (empty($collection)) {
        $list = $form_state->getValue('list');
      }
      else {
        $collection = $form_state->getValue($collection);
        $list = $collection['list'];
      }

      foreach (array_filter($list) as $config_name) {
        $config_opts = $form[$collection]['list']['#options'][$config_name];
        $config_change_type = $config_opts['type'];

        $source_name = $config_name;
        $target_name = $config_name;
        if ($config_change_type == 'rename') {
          $names = $storage_comparer->extractRenameNames($config_name);
          $source_name = $names['old_name'];
          $target_name = $names['new_name'];
        }

        list($source, $target) = $this->getTexts($this->syncStorage, $this->activeStorage, $source_name, $target_name, $collection);

        $base_dir = trim($config->get('config_base_path') ?? '', '/');
        $from_file = 'a/' . ($base_dir ? $base_dir . '/' : '') . $source_name . '.yml';
        $to_file = 'b/' . ($base_dir ? $base_dir . '/' : '') . $target_name . '.yml';

        $diff_header = "diff --git " . $from_file . " " . $to_file;
        $index_header = "index " . $this->getHash($source) . ".." . $this->getHash($target) . " 100644";

        if ($config_change_type == 'create') {
          $from_file = '/dev/null';
          $index_header = "new file mode 100644\n" . $index_header;
        }
        if ($config_change_type == 'delete') {
          $to_file = '/dev/null';
          $index_header = "deleted file mode 100644\n" . $index_header;
        }

        $formatted = $this->diff($source, $target, $from_file, $to_file);

        // Add a diff header.
        $formatted = $diff_header . "\n" . $index_header . "\n" . $formatted;

        $patch_key = empty($collection) ? 0 : $collection;
        $collection_patches[$patch_key][$config_name] = $formatted;
      }
    }

    $this->getOutputPlugin()->output($collection_patches);
  }

  /**
   * Get the text of the two configs.
   */
  protected function getTexts(StorageInterface $source_storage, StorageInterface $target_storage, $source_name, $target_name = NULL, $collection = StorageInterface::DEFAULT_COLLECTION) {
    if ($collection != StorageInterface::DEFAULT_COLLECTION) {
      $source_storage = $source_storage->createCollection($collection);
      $target_storage = $target_storage->createCollection($collection);
    }
    if (!isset($target_name)) {
      $target_name = $source_name;
    }

    // The output should show configuration object differences formatted as YAML.
    // But the configuration is not necessarily stored in files. Therefore, they
    // need to be read and parsed, and lastly, dumped into YAML strings.
    $raw_source = $source_storage->read($source_name);
    $source_data = $raw_source ? Yaml::encode($raw_source) : NULL;
    $raw_target = $target_storage->read($target_name);
    $target_data = $raw_target ? Yaml::encode($raw_target) : NULL;

    return [
      $source_data,
      $target_data,
    ];
  }

  /**
   * Run diffs, create a patch.
   */
  protected function diff($source, $target, $from_file, $to_file) {
    $builder = new StrictUnifiedDiffOutputBuilder([
      'collapseRanges'      => FALSE, // ranges of length one are rendered with the trailing `,1`
      'commonLineThreshold' => 6,    // number of same lines before ending a new hunk and creating a new one (if needed)
      'contextLines'        => 3,    // like `diff:  -u, -U NUM, --unified[=NUM]`, for patch/git apply compatibility best to keep at least @ 3
      'fromFile'            => $from_file,
      'fromFileDate'        => NULL,
      'toFile'              => $to_file,
      'toFileDate'          => NULL,
    ]);

    $differ = new Differ($builder);
    $patch = $differ->diff($source, $target);

    // Fix for create/delete file create header.
    $patch = preg_replace('/' . preg_quote('@@ -1,0') . '/s', '@@ -0,0', $patch);
    $patch = preg_replace('/' . preg_quote('+1,0 @@') . '/s', '+0,0 @@', $patch);

    return $patch;
  }

  /**
   * Retrieve the selected output plugin.
   */
  protected function getOutputPlugin() {
    $config = $this->configFactory->get('config_patch.settings');
    return $this->outputPluginManager->createInstance($config->get('output_plugin') ?? 'config_patch_output_text');
  }

  /**
   * Get a git-style hash.
   */
  protected function getHash($text) {
    if (empty($text)) {
      return "0000000";
    }
    $sha = sha1($text);
    return substr($sha, 0, 7);
  }

}
