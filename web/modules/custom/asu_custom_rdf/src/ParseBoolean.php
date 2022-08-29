<?php

namespace Drupal\asu_custom_rdf;

use Drupal\rdf\CommonDataConverter;

/**
 * {@inheritdoc}
 */
class ParseBoolean extends CommonDataConverter {

  /**
   * Parses a boolean value into a string.
   *
   * @param mixed $data
   *   The array containing the 'target_id' element.
   * @param mixed $arguments
   *   The array containing the arguments.
   *
   * @return string
   *   Returns the string.
   */
  public static function tostring($data, $arguments) {
    if (is_array($data)) {
      $value = $data['value'];
    }
    else {
      // \Drupal::logger('asu parse boolean')->info("not a boolean");
    }
    $string = "";
    foreach ($arguments as $key => $val) {
      $value = intval($value);
      if ($value === $key) {
        $string = $val;
      }
    }
    return $string;
  }

  /**
   * Parses a boolean value into a URI.
   *
   * @param mixed $data
   *   The array containing the boolean value.
   * @param mixed $arguments
   *   The array containing the argumnents.
   */
  public static function touri($data, $arguments) {
    if (is_array($data)) {
      $value = $data['value'];
    }
    foreach ($arguments as $key => $val) {
      $value = intval($value);
      if ($value === $key) {
        return $val;
      }
    }
    return '';
  }

}
