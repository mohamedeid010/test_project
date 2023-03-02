<?php

namespace Drupal\api_expose\FetchServices\Listing;

use Drupal\node\Entity\Node;
use Drupal\api_expose\Utils\Helper;

/**
 * Class FetchNode
 * @package Drupal\api_expose\FetchServices\Listing
 */
class FetchListing
{

  /** @var string $contentType */
  protected $contentType;

  /** @var int $offset */
  protected $offset;

  /** @var int $itemsPerPage */
  protected $itemsPerPage;

  /** @var array $conditions */
  protected $conditions;

  /**
   * constructor.
   */
  public function __construct($contentType, $offset, $itemsPerPage, array $conditions = NULL)
  {
    $this->contentType = $contentType;
    $this->offset = $offset;
    $this->itemsPerPage = $itemsPerPage;
    $this->conditions = $conditions;
  }

  /**
   * Get listing data
   */
  public function getData()
  {

    $nidsQuery = \Drupal::entityQuery("node")
      ->condition('type', $this->contentType)
      ->condition('status', 1);

    if (!empty($this->conditions)) {
      foreach ($this->conditions as $key => $value) {
        $fieldInfo = explode('|', $key);
        $fieldName = $fieldInfo[0] ?? $key;
        $op = $fieldInfo[1] ?? '=';
        $nidsQuery->condition($fieldName, $value, $op);
      }
    }
    $nidsQuery->range($this->offset, $this->itemsPerPage);
    $nidsQuery->sort('created', 'DESC');
    $nids = $nidsQuery->execute();
    $listingData = [];

    if (!empty($nids)) {
      $nodes = Node::loadMultiple($nids);
      $language = Helper::getLanguage();

      foreach ($nodes as $node) {
        $node = $node->hasTranslation($language) ? $node->getTranslation($language) : $node;
        //Basic fields
        $responseItem['id'] = (int)$node->id();
        $responseItem['title'] = $node->getTitle();
        $responseItem['type'] = $node->type->entity->label();
        $responseItem['typeKey'] = $node->getType();

        //Other fiels
        $listingData[] = array_merge($responseItem, Helper::fetchEntityFields($node));
      }
    }
    return [
      'items' => $listingData,
      'totalCount' => $this->getResultTotalCount(),
    ];
  }


  /**
   * Get the total count of original total results.
   */
  protected function getResultTotalCount()
  {
    $nidsQuery = \Drupal::entityQuery("node")
      ->condition('type', $this->contentType)
      ->condition('status', 1);

    if (!empty($this->conditions)) {
      foreach ($this->conditions as $key => $value) {
        $fieldInfo = explode('|', $key);
        $fieldName = $fieldInfo[0] ?? $key;
        $op = $fieldInfo[1] ?? '=';
        $nidsQuery->condition($fieldName, $value, $op);
      }
    }
    return (int)$nidsQuery->count()->execute();
  }

}
