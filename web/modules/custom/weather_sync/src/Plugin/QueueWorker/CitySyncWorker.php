<?php

namespace Drupal\weather_sync\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CitySyncWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Se ejecuta una vez por cada ciudad encolada por el hook_cron.
   */
  public function processItem($data) {
    // asuminos que $data es un array devuelto por la API, ej: ['name' => 'Medellin']
    $city_name = $data['name'];
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

    $existing = $term_storage->loadByProperties([
      'name' => $city_name,
      'vid' => 'weather_city',
    ]);

    if (empty($existing)) {
      $term = $term_storage->create([
        'name' => $city_name,
        'vid' => 'weather_city',
      ]);
      $term->save();
    }
  }
}
