<?php

namespace Drupal\config_patch\Plugin\config_patch\output;

/**
 * Simple text output of the patches.
 *
 * @ConfigPatchOutput(
 *  id = "config_patch_output_text",
 *  label = @Translation("Plain text output to browser")
 * )
 */
class Text extends OutputPluginBase implements OutputPluginInterface {}
