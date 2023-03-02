<?php

namespace Drupal\api_base_handler\Response;

use Drupal\api_base_handler\Resources\ResourceInterface;
use Drupal\rest\ModifiedResourceResponse;

/**
 * Class APIResponse
 * @package Drupal\api_base_handler\Response
 */
class APIResponse extends ModifiedResourceResponse {
  /**
   * APIResponse constructor.
   *
   * @param null $data
   * @param int $status
   * @param array $headers
   */
  public function __construct($data = NULL, $status = 200, $headers = []) {
    if ($data instanceof ResourceInterface) {
      $data = $data->toArray();
    }
    if (is_array($data)) {
      foreach ($data as $key => $value) {
        if ($value instanceof ResourceInterface) {
          $data[$key] = $value->toArray();
        }
      }
    }
    parent::__construct($data, $status, $headers);
  }
}
