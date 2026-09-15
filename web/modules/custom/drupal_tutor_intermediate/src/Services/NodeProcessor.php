<?php

namespace Drupal\drupal_tutor_intermediate\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

class NodeProcessor {

  protected $entityTypeManager;
  protected $logger;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $logger_factory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger_factory;
  }

  //EJERCICIO: Entity Query Avanzado
  public function getNodesToProcess(): array {
    $storage = $this->entityTypeManager->getStorage('node'); //Traemos nodos

    //Consulta avanzada: Articulos publicados, cuyo titulo  no contenga "[ACTUALIZADO]"
    $quey = $storage->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      ->condition('title', '[ACTUALIZADO]', 'NOT LIKE')
      ->accessCheck(FALSE);
    return $quey->execute();
  }

  //EJERCICIO: Callback de las Batch API
  //Arquitecura: Los callback de Batch a menudo necesitan ser estaticos
  //Drupal reconstruye el estado en cada peticion AJAX

  public static function processBatchItem($nid, $context) {
    // Como es estatico, usamos entityTypeManager() para acceder al servicio
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $node = $storage->load($nid);

    if ($node) {
      $old_title = $node->getTitle();
      $node->setTitle('[ACTUALIZADO] ' . $old_title);
      $node->save();

      //Guardar mensajes para mostrarlos al usuario mientras carga la barra
      $context['message'] = 'Actualizando nodo ID: ' . $nid;
      $context['results'][] = $nid;
    }
  }

  public static function finishBatch($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      $count = count($results);
      $messenger->addStatus('Exito! se actualizaron ' . $count . ' resultados.');
    } else {
      $messenger->addError('Ha ocurrido un error actualizando los resultados.');
    }
  }
}
