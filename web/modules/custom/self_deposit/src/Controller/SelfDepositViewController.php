<?php

namespace Drupal\self_deposit\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\Controller\WebformSubmissionViewController;

/**
 * Defines a controller to render a single webform submission.
 */
class SelfDepositViewController extends WebformSubmissionViewController {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $webform_submission, $view_mode = 'default', $langcode = NULL) {
    $webform = $this->requestHandler->getCurrentWebform();
    #\Drupal::logger('self_deposit')->notice('Webform title: '.$webform->label());


    #$build = parent::view($webform_submission, $view_mode, $langcode);
    $build = \Drupal::formBuilder()->getForm('Drupal\self_deposit\Form\SelfDepositCreateForm', $webform_submission);
    return $build;
    #return 
  }
}
