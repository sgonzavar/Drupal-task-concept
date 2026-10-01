<?php

namespace Drupal\weather_sync\Event;

use Drupal\node\Entity\Node;
use Symfony\Contracts\EventDispatcher\Event;

//Define el evento que se dispara al actualizar el clima de una ciudad.
class WeatherUpdatedEvent extends  Event {

  const EVENT_NAME = 'weather_sync.weather_updated';
  protected Node $node;

  public function __construct(Node $node) {
    $this->node = $node;
  }

  // permite a los suscriptores obtener el nodo con los datos actualizados
  public function getNode() {
    return $this->node;
  }
}
