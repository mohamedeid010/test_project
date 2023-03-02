<?php

/**
 * @file
 * Contains \Drupal\api_expose\Plugin\Block\ViewJsonButton.
 */

namespace Drupal\pdf_print_button\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;

/**
 * Provides a 'Print PDF Button'.
 *
 * @Block(
 * id = "print_pdf_button",
 * admin_label = @Translation("Print PDF Button"),
 * category = @Translation("custom")
 * )
 */
class PrintPDFButton extends BlockBase implements BlockPluginInterface
{

  /**
   *
   * {@inheritdoc}
   */
  public function build()
  {
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof \Drupal\node\NodeInterface) {
      $nid = $node->id();
      return [
        '#theme' => 'print_pdf_button',
        '#nid' => $nid,
      ];
    }
    return [];
  }

  /**
   * @return int
   */
  public function getCacheMaxAge()
  {
    return 0;
  }

}
