<?php

namespace Drupal\weather_sync\Client;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Cliente HTTP para OpenWeatherMap API.
 * Inyecta parámetros obligatorios (appid, units, lang) automáticamente.
 */
class WeatherApiClient {

  protected ClientInterface $httpClient;
  protected $logger;
  protected ConfigFactoryInterface $configFactory;
  protected string $baseUrl;

  public function __construct(
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory
  ) {
    $this->httpClient = $http_client;
    $this->logger = $logger_factory->get('weather_sync_api');
    $this->configFactory = $config_factory;

    // Configurable vía settings.php/.env
    $this->baseUrl = $this->configFactory->get('weather_sync.settings')
      ->get('api_base_url') ?? 'https://api.openweathermap.org/data/2.5';
  }

  /**
   * GET optimizado para OpenWeatherMap.
   * Inyecta appid, units=metric, lang=es automáticamente.
   */
  public function get(string $endpoint, array $query = []): ?array {
    $config = $this->configFactory->get('weather_sync.settings');
    $apiKey = $config->get('api_key');

    if (!$apiKey) {
      $this->logger->error('Weather API key no configurada en weather_sync.settings');
      return NULL;
    }

    // Parámetros obligatorios OWM + los que pase el caller
    $query = array_merge([
      'appid' => $apiKey,
      'units' => 'metric',
      'lang'  => 'es',
    ], $query);

    $url = $this->baseUrl . $endpoint;

    try {
      $response = $this->httpClient->request('GET', $url, [
        'query' => $query,
        'timeout' => 5,
        'headers' => ['Accept' => 'application/json'],
      ]);
      return json_decode($response->getBody()->getContents(), TRUE);
    }
    catch (RequestException $e) {
      $this->logger->error("Error GET a $url: " . $e->getMessage());
      return NULL;
    }
  }

  // OpenWeatherMap es de solo lectura; no se necesitan POST/PUT/DELETE.
  public function post(string $endpoint, array $data): ?array { return NULL; }
  public function put(string $endpoint, array $data): ?array { return NULL; }
  public function delete(string $endpoint): bool { return FALSE; }
}
