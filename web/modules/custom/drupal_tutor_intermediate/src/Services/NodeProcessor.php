<?php

namespace Drupal\drupal_tutor_intermediate\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

class NodeProcessor {

  protected $entityTypeManager;
  protected $logger;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $logger_factory) {
    $this->entityTypeManager = $entityTypeManager;
    // IMPORTANTE: get() para obtener el canal de log específico
    $this->logger = $logger_factory->get('drupal_tutor_intermediate');
  }

  //EJERCICIO: Entity Query Avanzado
  public function getNodesToProcess(): array {
    $storage = $this->entityTypeManager->getStorage('node');

    $query = $storage->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      // Los corchetes [] son especiales en LIKE, los escapamos con [[] y []]
      ->condition('title', '%[[]ACTUALIZADO[]]%', 'NOT LIKE')
      ->accessCheck(FALSE);
    return $query->execute();
  }

  //EJERCICIO: Callback de las Batch API
  //Arquitecura: Los callback de Batch a menudo necesitan ser estaticos
  //Drupal reconstruye el estado en cada peticion AJAX

  public static function processBatchItem($nid, $context) {
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $node = $storage->load($nid);

    if ($node) {
      $old_title = $node->getTitle();
      $node->setTitle('[ACTUALIZADO] ' . $old_title);
      $node->save();

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
