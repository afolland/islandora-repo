<?php

namespace Drupal\repo_bento_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\repo_bento_search\ThisI8ApiService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a 'Search results from this Islandora 8' Block.
 *
 * @Block(
 *   id = "bento_this_i8_results_block",
 *   admin_label = @Translation("Bento this Islandora 8 search results block"),
 *   category = @Translation("Views"),
 * )
 */
class BentoThisI8 extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * This i8 Repo API service.
   *
   * @var \Drupal\repo_bento_search\ThisI8ApiService
   */
  protected $t8Service;

  /**
   * The requestStack definition.
   *
   * @var requestStack
   */
  protected $requestStack;

  /**
   * Constructs a This i8 Repo block.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\repo_bento_search\ThisI8ApiService $t8_service
   *   Drupal core renderer.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    ThisI8ApiService $t8_service,
    RequestStack $request_stack
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config_factory->get('repo_bento_search.bentosettings');
    $this->t8Service = $t8_service;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('repo_bento_search.this_i8'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Read the configuration to see how many results we need to display.
    $service_api_url = $this->config->get('this_i8_api_url');
    $search_term = $this->requestStack->getCurrentRequest()->query->get('q');
    if (!trim(($service_api_url))) {
      $total_results_found = 0;
      $result_items = [];
      $service_url = '';
    }
    else {
      $num_results = $this->config->get('num_results') ?: 10;
      $parsed_url = parse_url($service_api_url);
      $service_url = $parsed_url['scheme'] . '://' .
        $parsed_url['host'] .
        (array_key_exists('port', $parsed_url) ? ":" . $parsed_url['port'] : "");
      // Get the search parameter from the GET url.
      // the url parameter is q as in q=cat.
      $results_json = ($search_term) ?
        $this->t8Service->getSearchResults($search_term) : '';
      $results_arr = json_decode($results_json, TRUE);
      if (is_null($results_arr)) {
        $result_items = [];
        $total_results_found = 0;
      }
      else {
        $total_results_found = $results_arr['pager']['count'];
        if (!empty($results_arr['search_results']) && count($results_arr['search_results']) > $num_results) {
          for ($p = count($results_arr['search_results']) - 1; $p >= $num_results; $p--) {
            unset($results_arr['search_results'][$p]);
          }
        }
        // API does not allow for a "how many" parameter, remove extra items.
        $result_items = (array_key_exists('search_results', $results_arr) &&
          is_array($results_arr['search_results'])) ?
            $results_arr['search_results'] : [];
      }
    }
    return [
      '#cache' => ['max-age' => 0],
      'lib' => [
        '#attached' => [
          'library' => [
            'repo_bento_search/style',
          ],
        ],
      ],
      '#attributes' => ['class' => ['bento_box']],
      [
        '#theme' => 'this_i8_results',
        '#service_url' => $service_url,
        '#items' => $result_items,
        '#total_results_found' => $total_results_found,
        '#search_term' => $search_term,
      ],
    ];
  }

}
