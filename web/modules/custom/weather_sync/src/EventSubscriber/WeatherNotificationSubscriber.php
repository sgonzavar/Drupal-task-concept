<?php

namespace Drupal\weather_sync\EventSubscriber;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\weather_sync\Event\WeatherUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Escucha eventos de actualización de clima para disparar acciones de terceros.
 */
class WeatherNotificationSubscriber implements EventSubscriberInterface {

protected $logger;

  public function __construct(LoggerChannelInterface $logger_factory) {
    $this->logger = $logger_factory;
  }

  /**
   * Indica a qué eventos de Symfony / Drupal estamos suscritos.
   */
  public static function getSubscribedEvents() {
    return [
      WeatherUpdatedEvent::EVENT_NAME => 'onWeatherUpdated',
    ];
  }

  /**
   * Lógica ejecutada al capturar el evento de clima.
   */
  public function onWeatherUpdated(WeatherUpdatedEvent $event) {
    $node = $event->getNode();

    //Extraemos la temperatura del nodo recien actualizado
    $temp = $node->get('field_temperature')->value;

    //Aqui iria la logica de negocio real (Email, Apis externas, notificaciones)
    if ($temp > 35) {
      $this->logger->notice('!SOOOOO HOT, 35C, simulation is running! for SMS ');
    }
    elseif ($temp < 10) {
      $this->logger->notice('!SOOOOO COULD, 10C, simulation is running! for email ');
    }
  }

}
