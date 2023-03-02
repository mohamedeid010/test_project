<?php

namespace Drupal\api_base_handler\Encoder;

use \Symfony\Component\Serializer\Encoder\JsonEncoder as SymfonyEncoder;

/**
 * Class JsonEncoder
 *   Encode response data to json.
 * @package Drupal\api_base_handler\Encoder
 */
class JsonEncoder extends SymfonyEncoder {

    /**
     * The formats that this Encoder supports.
     *
     * @var array
     */
    protected static $format = 'json';

    /**
     * {@inheritdoc}
     */
    public function supportsEncoding($format) {
        return $format == static::$format;
    }

    /**
     * {@inheritdoc}
     */
    public function encode($data, $format, array $context = []) {
        if (array_key_exists('message', $data)) {
            $response['message'] = $data['message'];
            unset($data['message']);
        }
        $tempstore = \Drupal::service('tempstore.shared')->get('api_validation');
        $errors = $tempstore->get('validation_errors');
        if ($errors) {
            $tempstore->delete('validation_errors');
            $errorsResult = [
                'code' => $errors[0] ?? '',
                'errorMessage' => $errors[1] ?? '',
            ];
            $response['errors'] = $errorsResult;
        } else {
            $response['data'] = $data;
        }

        return parent::encode($response, $format, $context);
    }

}
