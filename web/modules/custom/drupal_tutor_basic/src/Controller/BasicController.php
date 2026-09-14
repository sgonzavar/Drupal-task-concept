<?php

namespace Drupal\drupal_tutor_basic\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\drupal_tutor_basic\Services\ContentManagerService;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BasicController extends ControllerBase {

  protected ContentManagerService $contentManagerService;

  public function __construct(ContentManagerService $contentManagerService) {
    $this->contentManagerService = $contentManagerService;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('drupal_tutor_basic.content_manager')
    );
  }

  public function listArticles() {
    $nodes = $this->contentManagerService->getLatesrArticles(10);
    $items = [];
    foreach ($nodes as $item_node) {
      $items[] = $item_node->toLink()->toString();
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#title' => $this->t('Latest Articles'),
      '#empty' => $this->t('No articles found.'),
    ];
  }


  // ejemplo 02. Parametros + logica, lectura de articulos
  public function Read_time(NodeInterface $node) {
    $body = $node->get('body')->value ?? '';
    $reading_time = $this->contentManagerService->calculateReadingTime($body);

    return [
      '#markup' => "<p><strong>title:</strong> {$node->getTitle()} <br> {$body}</p> <p>Read time: {$reading_time} minutos</p>",
    ];
  }

  // ejemplo 03. Estadisticas del sitio
  public function getSiteStatistics() {
    $stats = $this->contentManagerService->gestSiteStats();
    return [
      '#markup' => "<p>Users: {$stats['total_users']}</p>, <p>Page Devel Gen: {$stats['total_pages']}</p>"
    ];
  }
}
