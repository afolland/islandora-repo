<?php

namespace Drupal\asu_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\EntityLookup;
use Drupal\taxonomy\Entity\Term;

/**
 * Check if term exists and create new if doesn't.
 *
 * @MigrateProcessPlugin(
 *   id = "multi_entity_lookup"
 * )
 *
 * @code
 *   field_member_of:
 *      plugin: multi_entity_lookup
 *      source:
 *        - Parent Item
 *        - Collection Title
 *      entity_type: node
 */
class MultiEntityLookup extends EntityLookup {
  /** @inheritdoc */
  public function transform($arr, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $item_parent = $arr[0];
    $collection_parent = $arr[1];
    if ($item_parent) {
      $this->configuration['bundle'] = 'asu_repository_item';
      $this->configuration['value_key'] = 'title';
      $par = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['field_pid' => $item_parent]);
      $par = array_keys($par)[0];
    }
    else {
      \Drupal::logger("multi_entity_lookup")->info('has a collection parent');
      $this->configuration['bundle'] = 'collection';
      $this->configuration['value_key'] = 'title';
      $par = parent::transform($collection_parent, $migrate_executable, $row, $destination_property);
    }
    return $par;
  }
}