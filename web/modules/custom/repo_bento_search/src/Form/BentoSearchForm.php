<?php

namespace Drupal\repo_bento_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Search form for the bento page itself.
 */
class BentoSearchForm extends FormBase {

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->requestStack = $container->get('request_stack');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'repo_bento_search.bentosettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bento_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $search_term = $this->requestStack->getCurrentRequest()->query->get('q');
    $form['#method'] = 'get';
    $form['#attributes']['class'][] = 'form-inline';
    $form['q'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#maxlength' => 255,
      '#size' => 80,
      '#value' => $search_term,
      '#attributes' => ['class' => ['col-md-8']],
      '#title_display' => 'invisible',
    ];
    // Group submit handlers in an actions element with a key of "actions" so
    // that it gets styled correctly, and so that other modules may add actions
    // to the form. This is not required, but is convention.
    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#title' => $this->t('Search'),
      '#weight' => '0',
      '#value' => 'Search',
      '#attributes' => ['class' => ['col-md-4', 'form--inline']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This should just display the same form at the top and any blocks
    // if there is a "q" parameter populated.
  }

}
