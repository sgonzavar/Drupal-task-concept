<?php

namespace Drupal\weather_sync\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\weather_sync\Client\WeatherApiClient;
use Drupal\weather_sync\Event\WeatherUpdatedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Orquestador principal que maneja las reglas de negocio, caché y entidades.
 */
class WeatherSyncManager {

  protected WeatherApiClient $apiClient;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected CacheBackendInterface $cacheBackend;
  protected EventDispatcherInterface $eventDispatcher;
  protected $logger;

  public function __construct(
    WeatherApiClient $api_client,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory,
    CacheBackendInterface $cache_backend,
    EventDispatcherInterface $event_dispatcher
  ) {
    $this->apiClient = $api_client;
    $this->entityTypeManager = $entity_type_manager;
    $this->cacheBackend = $cache_backend;
    $this->eventDispatcher = $event_dispatcher;
    $this->logger = $logger_factory->get('weather_sync_manager');
  }

  /**
   * Obtiene el clima de una ciudad (con Caché y Fallback mode).
   */
  public function pullWeather(string $cityName) {
    // 1. verificar cache
    $cid = 'weather_data:' . md5($cityName);
    if ($cache = $this->cacheBackend->get($cid)) {
      return $this->entityTypeManager->getStorage('node')->load($cache->data);
    }

    // 2. llamar a la API
    $data = $this->apiClient->get('/weather/' . urlencode($cityName));

    // 3. FALLBACK. si la API no responde
    if (!$data) {
      $this->logger->warning("API inaccesible. Activando Fallback para: $cityName");
      $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties([
        'type' => 'weather_news',
        'field_city.entity.name' => $cityName,
      ]);
      return !empty($nodes) ? reset($nodes) : FALSE;
    }

    // 4. PREPARAR DATOS Y TAXONOMIAS
    // asumir que la API retorna: ['city_name' => 'Bogota', 'temp' => 15, 'condition_name' => 'Lluvioso']
    $term_id = $this->getOrCreateTaxonomyTerm($data['condition_name']);
    $node_storage = $this->entityTypeManager->getStorage('node');

    $nodes = $node_storage->loadByProperties([
      'type' => 'weather_news',
      'title' => 'Weather to ' . $data['city_name'],
    ]);

    $node = reset($nodes);

    // 5. GUARDAR O ACTUALIZAR NODO
    if ($node) {
      $node->set('field_temperature', $data['temp']);
      $node->set('field_weather_type', $term_id);
      $node->save();
    }
    else {
      $node = $node_storage->create([
        'type' => 'weather_news',
        'title' => 'Weather to ' . $data['city_name'],
        // se asume que el nombre de la ciudad coincide con un termino en 'weather_city'
        'field_temperature' => $data['temp'],
        'field_weather_type' => $term_id,
        'status' => 1,
      ]);
      $node->save();
    }

    // 6. DISPARAR EVENTO
    $event = new WeatherUpdatedEvent($node);
    $this->eventDispatcher->dispatch($event, WeatherUpdatedEvent::EVENT_NAME);

    // 7. GUARDAR EN CACHÉ POR 15 MINUTOS (900 SEGUNDOS)
    $this->cacheBackend->set($cid, $node->id(), time() + 900);

    return $node;
  }

  /**
   * Helper: Crea o recupera una taxonomía de Tipo de Clima al vuelo.
   */
  protected function getOrCreateTaxonomyTerm(string $termName): int {
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $term_storage->loadByProperties([
      'name' => $termName,
      'vid' => 'weather_type',
    ]);

    if (!empty($terms)) {
      return reset($terms)->id();
    }

    $term = $term_storage->create([
      'name' => $termName,
      'vid' => 'weather_type',
    ]);
    $term->save();

    return $term->id();
  }

}
