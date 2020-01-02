<?php

namespace Drupal\config_patch\Controller;

use Drupal\config_patch\ConfigCompare;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigPatchToolbar.
 *
 * @package Drupal\config_patch\Controller
 */
class ConfigPatchToolbar extends ControllerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * Config compare service.
   *
   * @var \Drupal\config_patch\ConfigCompare
   */
  protected $configCompare;

  /**
   * ToolbarHandler constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   * @param \Drupal\config_patch\ConfigCompare $configCompare
   *   Config comparer.
   */
  public function __construct(AccountProxyInterface $account, ConfigCompare $configCompare) {
    $this->account = $account;
    $this->configCompare = $configCompare;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('config_patch.config_compare')
    );
  }

  /**
   * Get config changes with ajax.
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   An ajax response with configuration changes.
   */
  public function toolbarAjax() {
    $response = new AjaxResponse();
    $changes = $this->configCompare->getChangelist();
    $numbers = [];
    $counter = 0;
    foreach ($changes as $collection_name => $collection) {
      foreach ($collection as $config_name => $data) {
        if (!isset($numbers[$data['type']])) {
          $numbers[$data['type']] = 0;
        }
        $numbers[$data['type']]++;
        $counter++;
      }
    }
    if ($counter) {
      $counter = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => ['class' => ['config-changes-counter']],
        '#value' => $counter,
      ];
      $command = new AppendCommand('.toolbar-icon-config-patch', $counter);
      $response->addCommand($command);
    }

    return $response;
  }

  /**
   * Toolbar implementation.
   *
   * @return array
   *   Render array for items appended to toolbar.
   */
  public function toolbar() {
    $items = [];
    $items['config_patch'] = [
      '#cache' => [
        'contexts' => ['user.permissions'],
      ],
    ];
    if ($this->account->hasPermission('export configuration')) {
      $items['config_patch'] += [
        '#type' => 'toolbar_item',
        'tab' => [
          '#type' => 'link',
          '#title' => $this->t('Config changes'),
          '#url' => Url::fromRoute('config.patch'),
          '#options' => [
            'attributes' => [
              'title' => $this->t('Export configuration changes'),
              'class' => [
                'trigger',
                'toolbar-item',
                'toolbar-icon',
                'toolbar-icon-config-patch',
              ],
            ],
          ],
        ],
        '#weight' => 150,
        '#attached' => [
          'library' => ['config_patch/toolbar'],
          'drupalSettings' => [
            'config_patch' => [
              'toolbar' => [
                'url' => Url::fromRoute('config_patch.toolbar')->toString(),
              ],
            ],
          ],
        ],
      ];
    }
    return $items;
  }

}
