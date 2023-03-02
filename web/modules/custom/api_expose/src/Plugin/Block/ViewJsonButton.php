<?php

/**
 * @file
 * Contains \Drupal\api_expose\Plugin\Block\ViewJsonButton.
 */

namespace Drupal\api_expose\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;

/**
 * Provides a 'View Json Button'.
 *
 * @Block(
 * id = "view_json_button",
 * admin_label = @Translation("View Json Button"),
 * category = @Translation("custom")
 * )
 */
class ViewJsonButton extends BlockBase implements BlockPluginInterface {

    /**
     *
     * {@inheritdoc}
     */
    public function build() {

        $node = \Drupal::routeMatch()->getParameter('node');
        if ($node instanceof \Drupal\node\NodeInterface) {

            $nid = $node->id();
            return [
                '#theme' => 'view_json_button',
                '#nid' => $nid,
            ];
        }

        return [];
    }

}
