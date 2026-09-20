<?php

namespace Drupal\weather_sync\client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Servicio encargado ÚNICAMENTE de la comunicación HTTP externa.
 */
class WeatherApiClient {

  protected ClientInterface $httpClient;

  protected $logger;

  //ULR base de la API (ficticia)
  protected string $base_ulr = 'https://api.tu-servidor-clima.com/v1';

  public function __construct(ClientInterface $http_client, LoggerInterface $logger_factory) {
    $this->httpClient = $http_client;
    $this->logger = $logger_factory->get('weather_sync_api');
  }

  /**
   * Petición base centralizada.
   */
  protected function request(string $method, string $endoint, array $options = []): ?array {
    $url = $this->base_ulr . $endoint;
    $options['timeout'] = 10;
    $options['headers']['Content-Type'] = 'application/json';

    try {
      $response = $this->httpClient->request($method, $url, $options);
      return json_decode($response->getBody()->getContents(), TRUE);
    }
    catch (RequestException $e) {
      $this->logger->error('Error call $method to $url: ' . $e->getMessage());
      return NULL;
    }
  }

  public function get(string $endpoint, array $query = []): ?array {
    return $this->request('GET', $endpoint, ['query' => $query]);
  }

  public function post(string $endpoint, array $data): ?array {
    return $this->request('POST', $endpoint, ['json' => $data]);
  }

  public function put(string $endpoint, array $data): ?array {
    return $this->request('PUT', $endpoint, ['json' => $data]);
  }

  public function delete(string $endpoint): bool {
    $result = $this->request('DELETE', $endpoint);
    return $result !== NULL;
  }

}
