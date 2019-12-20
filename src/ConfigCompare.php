<?php

namespace Drupal\config_patch;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Tmp\Diff\Differ;
use Tmp\Diff\Output\StrictUnifiedDiffOutputBuilder;

/**
 * Compare configuration sets.
 *
 * @package Drupal\config_patch
 */
class ConfigCompare {

  use StringTranslationTrait;

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
   * Module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The output plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $outputPluginManager;

  /**
   * @var \Drupal\Core\Config\StorageComparerInterface
   */
  protected $storageComparer;

  /**
   * @var \Drupal\config_patch\Plugin\config_patch\output\OutputPluginInterface
   */
  protected $outputPlugin;

  /**
   * {@inheritdoc}
   */
  public function __construct(StorageInterface $sync_storage, StorageInterface $active_storage, ConfigManagerInterface $config_manager, ConfigFactoryInterface $config_factory, PluginManagerInterface $output_plugin_manager) {
    $this->syncStorage = $sync_storage;
    $this->activeStorage = $active_storage;
    $this->configManager = $config_manager;
    $this->config = $config_factory->get('config_patch.settings');
    $this->outputPluginManager = $output_plugin_manager;
    $this->outputPlugin = $this->outputPluginManager->createInstance($this->config->get('output_plugin'));
    $this->storageComparer = new StorageComparer($this->activeStorage, $this->syncStorage, $this->configManager);
  }

  /**
   * Get the text of the two configs.
   *
   * @param \Drupal\Core\Config\StorageInterface $source_storage
   * @param \Drupal\Core\Config\StorageInterface $target_storage
   * @param $source_name
   * @param null $target_name
   * @param string $collection
   *
   * @return array
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
   *
   * @param $source
   * @param $target
   * @param $from_file
   * @param $to_file
   *
   * @return string|string[]|null
   */
  protected function diff($source, $target, $from_file, $to_file) {
    $builder = new StrictUnifiedDiffOutputBuilder([
      'collapseRanges' => FALSE,
      // Ranges of length one are rendered with the trailing `,1`.
      'commonLineThreshold' => 6,
      // Number of same lines before ending a new hunk and creating a new one (if needed).
      'contextLines' => 3,
      // Like `diff:  -u, -U NUM, --unified[=NUM]`, for patch/git apply compatibility best to keep at least @ 3.
      'fromFile' => $from_file,
      'fromFileDate' => NULL,
      'toFile' => $to_file,
      'toFileDate' => NULL,
    ]);

    $differ = new Differ($builder);
    $patch = $differ->diff($source, $target);

    // Fix for create/delete file create header.
    $patch = preg_replace('/' . preg_quote('@@ -1,0') . '/s', '@@ -0,0', $patch);
    $patch = preg_replace('/' . preg_quote('+1,0 @@') . '/s', '+0,0 @@', $patch);

    return $patch;
  }

  /**
   * Get a git-style hash.
   *
   * @param $text
   *
   * @return false|string
   */
  protected function getHash($text) {
    if (empty($text)) {
      return "0000000";
    }
    $sha = sha1($text);
    return substr($sha, 0, 7);
  }

  /**
   * Gets the changes in one array.
   *
   * @return array
   *   Changes to the configuration following the structure:
   *   [
   *     'collection_name' => [
   *       'configuration_name' => [
   *            'name' => 'configuration_name',
   *            'type' => 'type of change' (update, create, delete, rename)
   *         ]
   *     ]
   *   ]
   */
  public function getChangelist() {
    $changes = [];
    if ($this->storageComparer->createChangelist()->hasChanges()) {
      $collections = $this->storageComparer->getAllCollectionNames();
      foreach ($collections as $collection) {
        foreach ($this->storageComparer->getChangelist(NULL, $collection) as $config_change_type => $config_names) {
          if (empty($config_names)) {
            continue;
          }
          foreach ($config_names as $config_name) {
            if ($config_change_type == 'rename') {
              $names = $this->storageComparer->extractRenameNames($config_name);
              $config_name = $this->t('@source_name to @target_name', [
                '@source_name' => $names['old_name'],
                '@target_name' => $names['new_name'],
              ]);
            }
            $changes[$collection][$config_name] = [
              'name' => $config_name,
              'type' => $config_change_type,
            ];
          }
        }
      }
    }
    return $changes;
  }

  /**
   * Collects the patches for selected config items.
   *
   * @param array $list_to_export
   *   The list of the configuration items to export.
   *   The array is two-dimensional: first level is collection name, second
   *   level the list of the config items. Example:
   *     $list_to_export = [
   *       '' => [
   *           'core.extensions.yml',
   *           'site.settings.yml',
   *        ]
   *     ]
   *   If array is empty all changes will be exported.
   *
   * @return array
   *   Array of the patches per file.
   */
  public function collectPatches(array $list_to_export = []) {
    $changes = $this->getChangelist();
    $collection_patches = [];

    foreach ($changes as $collection_name => $collection) {
      $list = !empty($list_to_export[$collection_name]) ? $list_to_export[$collection_name] : array_keys($collection);

      foreach (array_filter($list) as $config_name) {
        $config_change_type = $changes[$collection_name][$config_name]['type'];

        $source_name = $config_name;
        $target_name = $config_name;
        if ($config_change_type == 'rename') {
          $names = $this->storageComparer->extractRenameNames($config_name);
          $source_name = $names['old_name'];
          $target_name = $names['new_name'];
        }

        list($source, $target) = $this->getTexts($this->syncStorage, $this->activeStorage, $source_name, $target_name, $collection_name);

        $base_dir = trim($this->config->get('config_base_path') ?? '', '/');
        if ($collection_name != StorageInterface::DEFAULT_COLLECTION) {
          $base_dir .= '/' . str_replace('.', '/', $collection_name);
        }
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

        $patch_key = empty($collection_name) ? 0 : $collection_name;
        $collection_patches[$patch_key][$config_name] = $formatted;
      }
    }
    return $collection_patches;
  }

  /**
   * Retrieve the selected output plugin.
   */
  public function getOutputPlugin() {
    return $this->outputPlugin;
  }

  /**
   * Sets output plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function setOutputPlugin($plugin_id) {
    $this->outputPlugin = $this->outputPluginManager->createInstance($plugin_id);
  }

}
