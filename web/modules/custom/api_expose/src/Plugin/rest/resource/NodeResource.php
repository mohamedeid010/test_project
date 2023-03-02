<?php

namespace Drupal\api_expose\Plugin\rest\resource;

use Drupal\api_base_handler\Response\APIResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\Core\Cache\Cache;
use Drupal\api_expose\Utils\Helper;

/**
 * Represents node fields responses as a resource.
 *
 * @RestResource(
 *   id = "api_expose_node_resource",
 *   label = @Translation("API Expose Node Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/node/{nid}",
 *     "https://www.drupal.org/link-relations/create" = "/api/node/{nid}"
 *   }
 * )
 */
class NodeResource extends ResourceBase {

    /**
     * Represents GET
     */
    public function get($nid) {

        /**
         * Check if the data already exists on the cache,
         * then get it from the cache,
         *  else load and save it on the cache
         */
        $queryParams = Helper::queryParamsAsStrings() ?? '';
        $cid = 'api-NodeResource:' . Helper::getLanguage() . ':' . $nid . ":" . $queryParams;
        $noCache = \Drupal::request()->query->get('noCache') == 'true' ? TRUE : FALSE; //querey parameter to prevent cache

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

        //Get node content type
        if (!$contentType = $this->getNodeBundle($nid)) {
            $tempstore->set('validation_errors', ['NODE_NOT_FOUND']);
            return new APIResponse([], 404);
        }

        /**
         * 1- Getting class name from the content type machine name. For example: (machine_name => MachineName, article => Article,)
         * 2- Check if the class exits, then call it, else call the FetchNode class
         */
        $className = str_replace(' ', '', ucwords(str_replace('_', " ", $contentType)));
        $classNameSpace = '\Drupal\api_expose\FetchServices\Node' . '\\' . $className;

        if (class_exists($classNameSpace)) {
            $response = new $classNameSpace($nid);
        }
        else {
            $response = new \Drupal\api_expose\FetchServices\Node\FetchNode($nid, $contentType);
        }

        $nodeData = $response->getData();

        //Save data on cache
        \Drupal::cache()->set($cid, $nodeData, Cache::PERMANENT);

        return new APIResponse($nodeData);
    }

    /**
     * A query to get the node type.
     * @param int $nid
     * @return string|boolean content type.
     */
    protected function getNodeBundle($nid) {
        if (is_numeric($nid)) {
            $query = \Drupal::database()->select('node_field_data', 'nfd');
            $query->addField('nfd', 'type');
            $query->condition('nfd.nid', $nid);
            $query->condition('nfd.status', 1); //Must be published
            $type = $query->execute()->fetchField();
            return !empty($type) ? $type : FALSE;
        }

        return FALSE;
    }

}
