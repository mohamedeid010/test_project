<?php


namespace Drupal\api_base_handler\Resources;


interface ResourceInterface {

  /**
   * Implement to Array.
   *
   * @return array
   */
  public function toArray(): array;
}
