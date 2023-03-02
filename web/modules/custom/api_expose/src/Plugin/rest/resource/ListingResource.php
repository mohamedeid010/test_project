<?php

namespace Drupal\api_expose\Plugin\rest\resource;

use Drupal\api_base_handler\Response\APIResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\Core\Cache\Cache;
use Drupal\api_expose\Utils\Helper;

/**
 * Represents listing content responses as a resource.
 *
 * @RestResource(
 *   id = "api_expose_listing_resource",
 *   label = @Translation("API Expose Listing Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/listing/{type}",
 *     "https://www.drupal.org/link-relations/create" = "/api/listing/{type}"
 *   }
 * )
 */
class ListingResource extends ResourceBase {

    /**
     * Represents GET
     */
    public function get($type) {

        //Pager handling
        $page = \Drupal::request()->query->get('page') ?? 0;
        $itemsPerPage = \Drupal::request()->query->get('items_per_page') ?? 12;
        $offset = ($itemsPerPage * ($page + 1)) - $itemsPerPage;

        $language = Helper::getLanguage();

        /**
         * Check if the data already exists on the cache,
         * then get it from the cache,
         *  else load and save it on the cache
         */
        $queryParams = Helper::queryParamsAsStrings() ?? '';
        $cid = 'api-ListingResource:' . $language . ':' . $page . ':' . $itemsPerPage . ':' . $type. ":".$queryParams;

        $noCache = \Drupal::request()->query->get('noCache') == 'true' ? TRUE : FALSE;

        if (($cachedData = \Drupal::cache()->get($cid)) && !$noCache) {
            return new APIResponse($cachedData->data);
        }

        //Tempstore intialize
        $tempstore = \Drupal::service('tempstore.shared')->get('api_validation');

        //validate access
        if (!\Drupal::currentUser()->hasPermission('access content')) {
            $tempstore->set('validation_errors', ['ACCESS_DENIED']);
            return new APIResponse([], 403);
        }

        //Check if the content type existis
        if (!$this->isValidType($type)) {
            $tempstore->set('validation_errors', ['TYPE_NOT_FOUND']);
            return new APIResponse([], 404);
        }

        /**
         * 1- Getting class name from the content type machine name. For example: (machine_name => MachineName, article => Article,)
         * 2- Check if the class exits, then call it, else call the FetchNode class
         */
        $className = str_replace(' ', '', ucwords(str_replace('_', " ", $type)));
        $classNameSpace = '\Drupal\api_expose\FetchServices\Listing' . '\\' . $className;

        if (class_exists($classNameSpace)) {
            $response = new $classNameSpace($type, $offset, $itemsPerPage);
        } else {
            $response = new \Drupal\api_expose\FetchServices\Listing\FetchListing($type, $offset, $itemsPerPage);
        }

        $listingData = $response->getData();

        //Save data on cache
        \Drupal::cache()->set($cid, $listingData, Cache::PERMANENT);

        return new APIResponse($listingData);
    }

    /**
     * Check if the content type exists.
     * @param string $type content type machine name
     * @return boolean.
     */
    protected function isValidType($type) {
        $types = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
        foreach ($types as $cType) {
            if ($cType->id() == $type) {
                return TRUE;
            }
        }
        return FALSE;
    }
}
