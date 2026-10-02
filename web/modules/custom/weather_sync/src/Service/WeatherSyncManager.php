<?php

namespace Drupal\weather_sync\Service;

use Drupal\weather_sync\Client\WeatherApiClient;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\weather_sync\Event\WeatherUpdatedEvent;

/**
 * Orquestador principal que maneja las reglas de negocio para OpenWeatherMap.
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
   * Obtiene el clima de una ciudad.
   */
  public function pullWeather(string $cityName) {
    // 1. REVISAR CACHÉ
    $cid = 'weather_data_' . md5($cityName);
    if ($cache = $this->cacheBackend->get($cid)) {
      return $this->entityTypeManager->getStorage('node')->load($cache->data);
    }

    // 2. SOLICITAR A LA API (OpenWeatherMap usa el parámetro 'q' para la ciudad)
    $data = $this->apiClient->get('/weather', ['q' => $cityName]);

    // 3. FALLBACK: Si la API falló (timeout o error) o devolvió NULL
    if (!$data || !isset($data['main'])) {
      $this->logger->warning("API inaccesible o ciudad no encontrada. Fallback para: $cityName");
      $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties([
        'type' => 'weather_new',
        'field_city.entity.name' => $cityName,
      ]);
      return !empty($nodes) ? reset($nodes) : FALSE;
    }

    // 4. MAPEO DE DATOS DE OPENWEATHERMAP
    // $data['main']['temp'] = 24.5
    // $data['weather'][0]['description'] = "lluvia ligera"
    // $data['name'] = "Medellín"

    // Capitalizamos la primera letra (ej. "Lluvia ligera") para que la Taxonomía se vea bien
    $condition = ucfirst($data['weather'][0]['description']);
    $temp = $data['main']['temp'];
    $apiCityName = $data['name'];

    $term_id = $this->getOrCreateTaxonomyTerm($condition);
    $node_storage = $this->entityTypeManager->getStorage('node');

    $nodes = $node_storage->loadByProperties([
      'type' => 'weather_new',
      'title' => 'Clima en ' . $apiCityName,
    ]);

    $node = reset($nodes);

    // 5. GUARDAR O ACTUALIZAR NODO
    // Usar el $cityName original (del formulario/taxonomía) para field_city,
    // no $apiCityName (de la API), para mantener consistencia con el dropdown.
    $city_term_id = $this->getOrCreateCityTerm($cityName);

    if ($node) {
      $node->set('field_temperature', $temp);
      $node->set('field_weather_type', $term_id);
      $node->set('field_city', $city_term_id);
      $node->save();
    } else {
      $node = $node_storage->create([
        'type' => 'weather_new',
        'title' => 'Clima en ' . $apiCityName,
        'field_temperature' => $temp,
        'field_weather_type' => $term_id,
        'field_city' => $city_term_id,
        'status' => 1,
        'uid' => 1, // Usuario admin para evitar error en template_preprocess_node
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

  /**
   * Helper: Crea o recupera una taxonomía de Ciudad (weather_city) al vuelo.
   */
  protected function getOrCreateCityTerm(string $cityName): int {
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $term_storage->loadByProperties([
      'name' => $cityName,
      'vid' => 'weather_city',
    ]);

    if (!empty($terms)) {
      return reset($terms)->id();
    }

    $term = $term_storage->create([
      'name' => $cityName,
      'vid' => 'weather_city',
    ]);
    $term->save();

    return $term->id();
  }
}
