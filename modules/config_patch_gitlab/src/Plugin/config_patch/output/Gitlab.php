<?php

namespace Drupal\config_patch_gitlab\Plugin\config_patch\output;

use Drupal\config_patch\Plugin\config_patch\output\OutputPluginInterface;
use Drupal\config_patch\Plugin\config_patch\output\OutputPluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Simple text output of the patches.
 *
 * @ConfigPatchOutput(
 *  id = "config_patch_output_gitlab",
 *  label = @Translation("Create Gitlab MR by email")
 * )
 */
class Gitlab extends OutputPluginBase implements OutputPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Inject dependencies.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, MailManagerInterface $mail_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function output(array $patches, array $config_names) {
    $config = $this->configFactory->get('config_patch_gitlab.settings');
    $to = $config->get('email');
    if (!$to) {
      throw new NotFoundHttpException();
    }

    $module = 'config_patch_gitlab';
    $key = 'send_patch';
    $params['message'] = "Alters config: \n\n" . implode("\n", $config_names);
    $params['subject'] = "config-patch-" . md5(implode(',', $config_names));

    $params['attachments'] = [];
    // Save out and attach each patch.
    foreach ($patches as $patch) {
      $fn = file_unmanaged_save_data($patch);
      $file = new \stdClass();
      $file->uri = $fn;
      $file->filename = 'config.patch';
      $file->filemime = 'text/plain';
      $params['attachments'][] = $file;
    }

    $result = $this->mailManager->mail($module, $key, $to, NULL, $params, NULL, TRUE);
    $messenger = \Drupal::messenger();
    $messenger->addStatus('Sent patch to Gitlab.');
  }

}
