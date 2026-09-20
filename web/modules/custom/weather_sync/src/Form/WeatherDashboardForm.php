<?php

namespace Drupal\weather_sync\client\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\weather_sync\client\Service\WeatherSyncManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario interactivo renderizado vía AJAX.
 */
class WeatherDashboardForm extends FormBase {

  protected WeatherSyncManager $weatherManager;

  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(WeatherSyncManager $weather_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->weatherManager = $weather_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('weather_sync.manager'),
      $container->get('entity_type.manager')
    );
  }

  public function getFormId() {
    return 'weather_dashboard_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadTree('weather_city');

    foreach ($terms as $term) {
      $options[$term->tid()] = $term->name;
    }

    $form['city_selector'] = [
      '#type' => 'select',
      '#title' => $this->t('Selecciona una ciudad'),
      '#empty_option' => $this->t('- seleccione -'),
      '#options' => $options,
      '#ajax' => [
        'callback' => '::updateDashboardAjax',
        'wrapper' => 'weather-results-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Loading...'),
        ],
      ],
    ];

    $form['results_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'weather-results-wrapper'],
    ];
  }

  public function updateDashboardAjax(array $form, FormStateInterface $form_state) {
    $tid = $form_state->getValue('city_selector');

    if (empty($tid)) {
      $form['results_container']['#markup'] = '';
      return $form['results_container'];
    }

    // Obtener el nombre de la ciudad apartir del ID de la taxonomia
    $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
    $city_name = $term->getName();

    // consultar el manager
    $node = $this->weatherManager->pullWeather($city_name);

    if ($node) {
      $view_builder = $this->entityTypeManager->getViewBuilder('node');
      $form['results_container']['content'] = $view_builder->view($node, 'teaser');
    }
    else {
      $form['results_container']['#markup'] = '<div class="messages messages--error">'
        . $this->t('Error to call external API') . '</div>';
    }

    return $form['results_container'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // formulario administrado 100% AJAX
  }

}
