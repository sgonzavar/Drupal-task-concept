<?php 

namespace Drupal\drupal_practice\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class PracticeController extends ControllerBase {

  public function hello(string $name): array {
    return [
      '#markup' => $this->t(
        'Hola @name, este mensaje viene desde un Controller de Drupal.',
        [
          '@name' => $name,
        ]
      ),
    ];
  }

  public function renderArray(): array {
    return[
      'intro' => [
        '#type' => 'container',
        
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => $this->t('Technology im study or im use'),
        ],

        'description' => [
          '#markup' => $this->t('This content buinding with render arrays') 
        ]
      ],

      'technologies' => [
        '#theme' => 'item_list',
        '#title' => $this->t('Tecnologías'),
        '#items' => [
          'Drupal',
          'Lit',
          'Angular',
          'NestJS',
        ],
      ],

      'link' => [
        '#type' => 'link',
        '#title' => $this->t('Ir a Drupal.org'),
        '#url' => Url::fromUri('https://www.drupal.org'),
        '#attributes' => [
          'target' => '_blank',
        ],
      ],
    ];
  }
}

