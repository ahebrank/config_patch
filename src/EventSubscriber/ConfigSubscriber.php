<?php


namespace Drupal\config_patch\EventSubscriber;


use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ConfigSubscriber
 *
 * @package Drupal\config_patch\EventSubscriber
 */
class ConfigSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagInvalidator;

  /**
   * ConfigSubscriber constructor.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   */
  public function __construct(CacheTagsInvalidatorInterface $cacheTagsInvalidator) {
    $this->cacheTagInvalidator = $cacheTagsInvalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ConfigEvents::DELETE => 'onConfigChange',
      ConfigEvents::SAVE => 'onConfigChange',
      ConfigEvents::RENAME => 'onConfigChange',
    ];
  }

  /**
   * @param \Symfony\Component\EventDispatcher\Event $event
   */
  public function onConfigChange(Event $event) {
    $this->cacheTagInvalidator->invalidateTags(['config_patch']);
  }

}
