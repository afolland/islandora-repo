<?php

namespace Drupal\asu_collection_extras\Plugin\Block;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\islandora_matomo\IslandoraMatomoService;
use Drupal\Core\Database\Connection;

/**
 * Provides a 'About this collection' Block.
 *
 * @Block(
 *   id = "about_this_collection_block",
 *   admin_label = @Translation("About this collection"),
 *   category = @Translation("Views"),
 * )
 */
class AboutThisCollectionBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The requestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The currentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The islandoraMatomo definition.
   *
   * @var \Drupal\islandora_matomo\IslandoraMatomoService
   */
  protected $islandoraMatomo;

  /**
   * The database connection definition.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Construct method.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entityTypeManager definition.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The currentRouteMatch definition.
   * @param \Drupal\islandora_matomo\IslandoraMatomoService $islandoraMatomo
   *   The islandoraMatomo service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RequestStack $request_stack,
    EntityTypeManager $entityTypeManager,
    CurrentRouteMatch $currentRouteMatch,
    IslandoraMatomoService $islandoraMatomo,
    Connection $connection
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->islandoraMatomo = $islandoraMatomo;
    $this->connection = $connection;
  }

  /**
   * Does the initialization of the block setting dependency injection vars.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The parent class object.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('islandora_matomo.default'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /*
     * The sections in this block should be statistics on the children of the
     * collection using the "Is Member Of" relationship -- not just the "Is
     * Primary Member Of" and these should link to a canned-search for the
     * specific statistic when possible:
     *
     *  - # of child items
     *  - # of resource types
     *  - # of titles

     *  - usage
     *  - collection created
     *  - collection last updated (by looking at all of the children)
     *
     * When needing to check a node's content type:
     *    $is_collection = ($node->bundle() == 'collection');
     */
    // Since this block should be set to display on node/[nid] pages that are
    // either "Repository Item", "ASU Repository Item", or "Collection",
    // the underlying node can be accessed via the path.
    $collection_node = $this->currentRouteMatch->getParameter('node');
    if ($collection_node) {
      $collection_created = $collection_node->get('revision_timestamp')->getString();
    }
    else {
      $collection_created = 0;
    }
    // Run a solr query first to get ALL the items under the collection using
    // the ancestors field.
    $children = asu_collection_extras_solr_get_collection_children($collection_node);
    // \Drupal::logger('asu_collection_extras')->info('Collection ' . $collection_node->id() .
    //   ' children:<pre><code>' . print_r($children, TRUE) . '</code></pre>');
    $items = $max_timestamp = 0;
    $islandora_models = $stat_box_row1 = [];

    $items = count($children);
    $files = $max_timestamp = 0;

    // The first $child_arr will have the most recent changed value.
    foreach ($children as $nid => $child_arr) {
      if ($nid) {
        $files += $child_arr['original_file_count'];
        if (!$max_timestamp) {
          $max_timestamp = strtotime($child_arr['changed']);
        }
        $model = $child_arr['field_model'];
        // Since it is possible that an asu_repository_item may be indexed w/o
        // having a field_model value, we must omit any that are set = 0.
        if ($model) {
          if (array_key_exists($model, $islandora_models)) {
            $islandora_models[$model]++;
          }
          else {
            $islandora_models[$model] = 1;
          }
        }
      }
    }

    $collection_views = $this->getCollectionViews($collection_node);
    // Calculate the "Items" box link.
    $items_url = Url::fromUri($this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/collections/' .
       (($collection_node) ? $collection_node->id() : 0) . '/search/?search_api_fulltext=');
    $stat_box_row1[] = $this->makeBox("<strong>" . $items . "</strong><br>items", $items_url);
    $stat_box_row1[] = $this->makeBox("<strong>" . $files . "</strong><br>files");
    $stat_box_row1[] = $this->makeBox("<strong>" . count($islandora_models) . "</strong><br>resource types");
    $stat_box_row2[] = $this->makeBox("<strong>" . $collection_views . "</strong><br>views");
    $stat_box_row2[] = $this->makeBox("<strong>" . (($collection_created) ? date('Y', $collection_created) : 'unknown') .
      "</strong><br>collection created");
    $stat_box_row2[] = $this->makeBox("<strong>" . (($max_timestamp) ? date('M d, Y', $max_timestamp) : 'unknown') .
      "</strong><br>last updated</div>");
    return [
      '#markup' =>
      (count($stat_box_row1) > 0) ?
        // ROW 1.
      '<div class="container"><div class="row">' .
      implode('', $stat_box_row1) .
      '</div>' .
        // ROW 2.
      '<div class="row">' .
      implode('', $stat_box_row2) .
      '</div>' :
      "",
      'lib' => [
        '#attached' => [
          'library' => [
            'asu_collection_extras/style',
          ],
        ],
      ],
    ];
  }

  /**
   * Loads the collection views from the summary table.
   *
   * @param mixed $collection_node
   *   This could be a node object or the integer id() value of a node.
   *
   * @return int
   *   The number of views for the collection.
   */
  private function getCollectionViews($collection_node) {
    $collection_node_id = (is_object($collection_node) ? $collection_node->id() : $collection_node);
    if (!$this->connection->schema()->tableExists('asu_collection_extras_collection_usage')) {
      \Drupal::logger('asu_collection_extras')->warning('asu_collection_extras_collection_usage table does not exist. Re-install asu_collection_extras module or run SQL:' .
      "<code>CREATE TABLE `asu_collection_extras_collection_usage` (
    `nid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The collection \"node\".nid this record affects.',
    `views` int(11) NOT NULL DEFAULT '0' COMMENT 'View total for all objects in the collection.',
    `downloads` int(11) NOT NULL DEFAULT '0' COMMENT 'Download total for all objects in the collection.',
    `modified` int(11) NOT NULL DEFAULT '0' COMMENT 'Timestamp for when the record is updated'
    PRIMARY KEY (`nid`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4</code>");
      return 0;
    }
    $collection_views = $this->connection
      ->query('SELECT views FROM asu_collection_extras_collection_usage WHERE nid = ' . $collection_node_id)
      ->fetchAll();
    $v = 0;
    foreach ($collection_views as $c_obj) {
      $v += $c_obj->views;
    }
    return $v;
  }

  /**
   * Makes the markup for a given item's box for the template.
   *
   * @param string $string
   *   The inside text string for the box that will be linked.
   * @param Drupal\Core\Url $link_url
   *   The URL object for the explore link.
   *
   * @return string
   *   Markup of the box for use in the template
   */
  private function makeBox($string, Url $link_url = NULL) {
    if ($link_url) {
      // Drupal's Link class is escaping the HTML, so this must be done
      // manually.
      return '<div class="stats_box col-4 stats_pointer_box"><a href="' . $link_url->toString() . '" title="Explore items">' .
        '<div class="stats_border_box">' . $string . '</div></a></div>';
    }
    else {
      return '<div class="stats_box col-4"><div class="stats_border_box">' . $string . '</div></div>';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if ($node = $this->currentRouteMatch->getParameter('node')) {
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    }
    else {
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
