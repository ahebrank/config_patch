use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
   * The export configuration object.
   *
   * See https://www.drupal.org/node/3037022.
  protected $exportStorage;
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * ConfigCompare constructor.
   *
   * @param \Drupal\Core\Config\StorageInterface $sync_storage
   * @param \Drupal\Core\Config\StorageInterface $export_storage
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Component\Plugin\PluginManagerInterface $output_plugin_manager
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
  public function __construct(StorageInterface $sync_storage, StorageInterface $export_storage, ConfigManagerInterface $config_manager, ConfigFactoryInterface $config_factory, PluginManagerInterface $output_plugin_manager, CacheBackendInterface $cacheBackend) {
    $this->exportStorage = $export_storage;
    $this->storageComparer = new StorageComparer($this->exportStorage, $this->syncStorage, $this->configManager);
    $this->cache = $cacheBackend;
    $cached_changes = $this->cache->get('config_patch_changes');
    if (!empty($cached_changes)) {
      $changes = $cached_changes->data;
    }
    else {
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
      $this->cache->set('config_patch_changes', $changes, Cache::PERMANENT, ['config_patch']);
        list($source, $target) = $this->getTexts($this->syncStorage, $this->exportStorage, $source_name, $target_name, $collection_name);
        $patch_key = empty($collection_name) ? 'default' : $collection_name;