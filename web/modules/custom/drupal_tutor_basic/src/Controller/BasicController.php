<?php

namespace Drupal\drupal_tutor_basic\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\drupal_tutor_basic\Services\ContentManagerService;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Xss;

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
    $nodes = $this->contentManagerService->getLatestArticles(10);
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


  // Ejemplo 02. Parametros + logica, tiempo de lectura de articulos
  public function readTime(NodeInterface $node) {
    // Verificar que el nodo tenga campo body
    if (!$node->hasField('body') || $node->get('body')->isEmpty()) {
      return [
        '#markup' => $this->t('This node does not have a body field.'),
      ];
    }

    $body = $node->get('body')->value ?? '';
    $reading_time = $this->contentManagerService->calculateReadingTime($body);

    // Filtrar HTML para evitar XSS
    $safe_body = Xss::filter($body, ['p', 'br', 'strong', 'em', 'ul', 'ol', 'li']);

    return [
      '#markup' => '<p><strong>' . $this->t('Title:') . '</strong> ' . $node->getTitle() . '<br>' . $safe_body . '</p><p>' . $this->t('Read time: @minutes minutes', ['@minutes' => $reading_time]) . '</p>',
    ];
  }

  // Ejemplo 03. Estadisticas del sitio
  public function getSiteStatistics() {
    $stats = $this->contentManagerService->getSiteStats();
    return [
      '#markup' => '<p>' . $this->t('Users: @total_users', ['@total_users' => $stats['total_users']]) . '</p><p>' . $this->t('Pages (Devel Gen): @total_pages', ['@total_pages' => $stats['total_pages']]) . '</p>'
    ];
  }
}