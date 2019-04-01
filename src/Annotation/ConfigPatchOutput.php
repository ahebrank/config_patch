<?php

namespace Drupal\config_patch\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a config_patch Output annotation object.
 *
 * @Annotation
 *
 * @ingroup config_patch
 */
class ConfigPatchOutput extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the output type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The name of the field formatter class.
   *
   * @var string
   *
   * This is not provided manually, it will be added by the discovery mechanism.
   */
  public $class;

}
