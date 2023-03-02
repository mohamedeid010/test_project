<?php

namespace Drupal\api_expose\Utils;

use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\path_alias\Entity\PathAlias;

/**
 * Class Helper
 * @package Drupal\api_expose\Utils
 */
class Helper {

    /**
     * Fetch Entity Fields
     */
    public static function fetchEntityFields($entity) {
        $response = [];
        $fields = self::getBundleDefinitions($entity->getEntityTypeId(), $entity->bundle());
        if (!empty($fields)) {
            foreach ($fields as $field) {
                //Convert machine name to camel case
                $fieldCamelCase = self::camelCase($field['name']);

                $fieldMachineName = $field['name'];
                $fieldType = $field['type'];

                //Created and updated Date
                if ($entity->getEntityTypeId() != 'taxonomy_term') {
                    $response['createdDate'] = \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'short_date');
                    $response['lastUpdate'] = \Drupal::service('date.formatter')->format($entity->getChangedTime(), 'medium');
                }

                switch ($fieldType) {
                    case 'text_with_summary':
                    case 'string':
                    case 'string_long':
                    case 'float':
                    case 'text_long':
                    case 'email':
                    case 'telephone':
                    case 'integer':
                    case 'yearonly':
                        $response[$fieldCamelCase] = self::loadFieldValue($entity, $fieldMachineName);
                        break;

                    //Image and file field
                    case 'image':
                    case 'file':
                        $response[$fieldCamelCase] = !$entity->get($fieldMachineName)->isEmpty() ?
                          file_url_transform_relative(file_create_url($entity->{$fieldMachineName}->entity->getFileUri())) : NULL;
                        break;

                    //List field
                    case 'list_string':
                        $response[$fieldCamelCase] = !$entity->get($fieldMachineName)->isEmpty() ? $entity->{$fieldMachineName}->view()[0]['#markup'] : NULL;
                        $response[$fieldCamelCase . 'Key'] = !$entity->get($fieldMachineName)->isEmpty() ? $entity->{$fieldMachineName}->value : NULL;
                        break;

                    //List field
                    case 'link':
                        $response[$fieldCamelCase] = ['uri' => $entity->{$fieldMachineName}->uri, 'title' => $entity->{$fieldMachineName}->title] ?? NULL;
                        break;

                    //Date time
                    case 'datetime':
                        $response[$fieldCamelCase] = !$entity->get($fieldMachineName)->isEmpty() && ($entity->{$fieldMachineName}->date) ?
                          \Drupal::service('date.formatter')->format($entity->{$fieldMachineName}->date->getTimestamp(), 'default', '', NULL, self::getLanguage()) : NULL;
                        break;

                    //Date range field
                    case 'daterange':
                        $response[$fieldCamelCase] = !$entity->get($fieldMachineName)->isEmpty() ?
                          [
                          'start' => date('d/m/Y', strtotime($entity->get($fieldMachineName)->value)),
                          'end' => date('d/m/Y', strtotime($entity->get($fieldMachineName)->end_value)),
                          ] : NULL;
                        break;

                    //Date range field
                    case 'time_range':
                        $response[$fieldCamelCase] = !$entity->get($fieldMachineName)->isEmpty() ?
                          [
                          'start' => date('H:i', $entity->get($fieldMachineName)->from),
                          'end' => date('H:i', $entity->get($fieldMachineName)->to),
                          ] : NULL;
                        break;

                    //Media field
                    case($fieldType == 'entity_reference' && $field['target_type'] == 'media'):
                        $response[$fieldCamelCase] = self::loadMediaField($entity, $fieldMachineName);
                        break;

                    //Node and term reference field
                    case($fieldType == 'entity_reference' && $field['target_type'] == 'node'):
                        $response[$fieldCamelCase] = self::loadNodeReferenceField($entity, $fieldMachineName);
                        break;

                    case($fieldType == 'entity_reference' && $field['target_type'] == 'taxonomy_term'):
                        $response[$fieldCamelCase] = self::loadTermReferenceField($entity, $fieldMachineName);
                        break;

                    default:
                        break;
                }
            }
        }

        return $response;
    }

    /**
     * Load field value
     * @param type $entity
     * @param type $fieldMachineName
     */
    public static function loadFieldValue($entity, $fieldMachineName) {
        $response = NULL;
        if (count($entity->{$fieldMachineName}) > 1) {
            foreach ($entity->{$fieldMachineName} as $value) {
                $response[] = $value->value;
            }
        }
        else {
            $response = $entity->{$fieldMachineName}->value ?? NULL;
        }
        return $response;
    }

    /**
     * Load media field value
     * @param type $entity
     * @param type $fieldMachineName
     */
    public static function loadMediaField($entity, $fieldMachineName) {
        $response = NULL;

        if (!$entity->get($fieldMachineName)->isEmpty() && count($entity->{$fieldMachineName}) > 1) {
            foreach ($entity->{$fieldMachineName} as $value) {
                $response[] = self::loadMedia($value->target_id) ?? NULL;
            }
        }
        else {
            $response = !$entity->get($fieldMachineName)->isEmpty() ?
              self::loadMedia($entity->{$fieldMachineName}->target_id) : NULL;
        }

        return $response;
    }

    /**
     * Load Node reference field
     * @param type $entity
     * @param type $fieldMachineName
     */
    public static function loadNodeReferenceField($entity, $fieldMachineName) {
        $response = NULL;
        if (!$entity->get($fieldMachineName)->isEmpty()) {
            $refEntityField = $entity->get($fieldMachineName)->referencedEntities();
            if (count($refEntityField) > 1) {
                foreach ($refEntityField as $refEntity) {
                    $nodeData = new \Drupal\api_expose\FetchServices\Node\FetchNode($refEntity->id(), $refEntity->getType());
                    $response[] = $nodeData->getData();
                }
            }
            else {
                $refEntity = $refEntityField[0];
                if(isset($refEntity)){
                    $nodeData = new \Drupal\api_expose\FetchServices\Node\FetchNode($refEntity->id(), $refEntity->getType());
                    $response = $nodeData->getData();
                }
            }
        }
        return $response;
    }

    /**
     * Load media field value
     * @param type $entity
     * @param type $fieldMachineName
     */
    public static function loadTermReferenceField($entity, $fieldMachineName) {
        $response = NULL;
        if (!$entity->get($fieldMachineName)->isEmpty()) {
            $refEntityField = $entity->get($fieldMachineName)->referencedEntities();
            if (count($refEntityField) > 1) {
                foreach ($refEntityField as $refEntity) {
                    $termData = new \Drupal\api_expose\FetchServices\Term\FetchTerm($refEntity->id());
                    $response[] = $termData->getData();
                }
            }
            else {
                $refEntity = $refEntityField[0];
                $termData = new \Drupal\api_expose\FetchServices\Term\FetchTerm($refEntity->id());
                $response = $termData->getData();
            }
        }
        return $response;
    }

    /**
     * Get entity type fields definitions.
     */
    public static function getBundleDefinitions($entityType, $bundle) {
        $fields = array_filter(
          \Drupal::service('entity_field.manager')->getFieldDefinitions($entityType, $bundle),
          function ($fieldDefinition) {
              return $fieldDefinition instanceof \Drupal\field\FieldConfigInterface;
          }
        );

        $result = [];
        foreach ($fields as $key => $definition) {
            $result[$key] = [
              'name' => $key,
              'type' => $definition->getType(),
              'label' => $definition->label(),
            ];
            // if the field is a reference field get also the target entity type
            if (in_array($result[$key]['type'], ['entity_reference', 'entity_reference_revisions'])) {
                $result[$key]['target_type'] = $definition->getSettings()['target_type'];
            }
        }

        return $result;
    }

    /**
     * Get language parameter
     */
    public static function getLanguage() {
        return \Drupal::languageManager()->getCurrentLanguage()->getId();
    }

    /**
     * Convert field machine name to camel case
     * @param type $fieldName
     */
    public static function camelCase($fieldName) {
        $fieldNameFormatted = str_replace(' ', '', ucwords(strtr($fieldName, ['field_' => '', '_' => ' '])));
        return lcfirst($fieldNameFormatted);
    }

    /**
     * Load Media
     * @param type $mid
     * @param type $imageStyle
     */
    public static function loadMedia($mid, $imageStyle = NULL) {
        $mediaEntity = Media::load($mid);
        if ($mediaEntity && $mediaEntity instanceof \Drupal\media\MediaInterface) {
            if ($mediaEntity->bundle() == 'image') {
                $imgUri = File::load($mediaEntity->field_media_image->target_id)->getFileUri();

                if (isset($imageStyle)) {
                    return file_url_transform_relative(ImageStyle::load($imageStyle)->buildUrl($imgUri));
                }
                return file_url_transform_relative(file_create_url($imgUri));
            }
            elseif ($mediaEntity->bundle() == 'video') {
                $videoUri = File::load($mediaEntity->field_media_video_file->target_id)->getFileUri();
                return file_url_transform_relative(file_create_url($videoUri));
            }
            elseif ($mediaEntity->bundle() == 'remote_video') {
                return $mediaEntity->field_media_oembed_video->value;
            }
            elseif ($mediaEntity->bundle() == 'document') {
                $dcomentUri = File::load($mediaEntity->field_media_document->target_id)->getFileUri();
                return file_url_transform_relative(file_create_url($dcomentUri));
            }
        }

        return NULL;
    }

    /**
     * Clean string from specian chars
     * @param type $str
     * @return type
     */
    public static function cleanString($str) {
        $str = str_replace("&nbsp;", " ", $str);
        $str = preg_replace('/\s+/', ' ', $str);
        $str = trim($str);
        $str = strip_tags($str);
        return $str;
    }

    /**
     * ِAll query params
     */
    public static function queryParamsAsStrings() {
        $cidQueryParam = NULL;
        $queryParams = \Drupal::request()->query->all();
        $remove = ['lang', 'noCache'];
        $queryParams = array_diff_key($queryParams, array_flip($remove));

        foreach ($queryParams as $key => $value) {
            $cidQueryParam .= $key . ':' . $value;
        }
        return $cidQueryParam;
    }
}
