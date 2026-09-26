<?php

namespace Drupal\drupal_tutor_basic\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

class ContentManagerService {

  protected $entityTypeManager;
  protected $currentUser;


  // Inyectar dependencias necesarias para el servicio, entidades y usuario actual
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
    AccountProxyInterface $currentUser) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
  }

  // Ejemplo 01. Obtener los ultimos N articulos publicados
  public function getLatestArticles(int $limit = 5): array {
    $storage = $this->entityTypeManager->getStorage('node');
    $nids = $storage->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, $limit)
      ->accessCheck(TRUE)
      ->execute();

    return $storage->loadMultiple($nids);
  }

  // Ejemplo 02. Calcular el tiempo de lectura de un nodo
  public function calculateReadingTime(string $text): int {
    $words = str_word_count(strip_tags($text));
    $minutes = ceil($words / 200); // Asumiendo una velocidad de lectura promedio de 200 palabras por minuto
    return $minutes;
  }

  // Ejemplo 03. Estadisticas basicas del sitio
  public function getSiteStats(): array {

    $total_users = $this->entityTypeManager->getStorage('user')->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $total_pages = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'page')
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    return [
      'total_users' => $total_users,
      'total_pages' => $total_pages
    ];
  }

}