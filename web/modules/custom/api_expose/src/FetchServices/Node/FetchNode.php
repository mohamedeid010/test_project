<?php

namespace Drupal\api_expose\FetchServices\Node;

use Drupal\node\Entity\Node;
use Drupal\api_expose\Utils\Helper;

/**
 * Class FetchNode
 * @package Drupal\api_expose\FetchServices\Node
 */
class FetchNode {

    /** @var string $contentType */
    protected $contentType;

    /** @var int $nid */
    protected $nid;

    /**
     * constructor.
     */
    public function __construct($nid, $contentType) {
        $this->nid = $nid;
        $this->contentType = $contentType;
    }

    /**
     * Get node data
     */
    public function getData() {
        $language = Helper::getLanguage();
        $node = Node::load($this->nid);
        $node = $node->hasTranslation($language) ? $node->getTranslation($language) : $node;

        //Basic fields
        $basicFields = [
            'id' => (int) $node->id(),
            'alias' => \Drupal::service('path_alias.manager')->getAliasByPath('/node/'.$node->id()),
            'title' => $node->getTitle(),
            'type' => $node->type->entity->label(),
            'typeKey' => $node->getType(),
        ];

        //Other fiels
        $otherFields = Helper::fetchEntityFields($node);
        return array_merge($basicFields, $otherFields);
    }

}
