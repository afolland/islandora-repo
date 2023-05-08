<?php

namespace Drupal\self_deposit\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change path '/user/login' to '/login'.
    if ($route = $collection->get('entity.webform_submission.canonical')) {
      $route->setDefault('_controller', '\Drupal\self_deposit\Controller\SelfDepositViewController::view');
      $route->setRequirement('_permission', 'edit webform source');
      $route->setRequirement('_entity_access', 'webform_submission.view_any');
      \Drupal::logger('self_deposit')->notice('Route Altered');
    }
    // Always deny access to '/user/logout'.
    // Note that the second parameter of setRequirement() is a string.
    //if ($route = $collection->get('user.logout')) {
    //  $route->setRequirement('_access', 'FALSE');
    //}
  }

}