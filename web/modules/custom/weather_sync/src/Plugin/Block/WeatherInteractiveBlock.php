<?php

namespace Drupal\weather_sync\Plugin\Block;


use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provee el bloque con el Dashboard de clima interactivo.
 *
 * @Block(
 *   id = "weather_interactive_block",
 *   admin_label = @Translation("Dashboard Interactivo de Clima (API)"),
 *   category = @Translation("Custom")
 * )
 */
class WeatherInteractiveBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected FormBuilderInterfacer  $formBuilder;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  public function build() {
    return $this->formBuilder->getForm('Drupal\weather_sync\Form\WeatherInteractiveForm');
  }
}
