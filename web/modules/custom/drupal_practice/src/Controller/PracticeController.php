<?php

namespace Drupal\drupal_practice\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class PracticeController extends ControllerBase {

  public function hello(string $name): array {
    return [
      '#markup' => $this->t(
        'Hello @name, this message comes from a Drupal Controller.',
        [
          '@name' => $name,
        ]
      ),
    ];
  }

  public function renderArray(): array {
    return [
      'intro' => [
        '#type' => 'container',

        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => $this->t('Technologies I study or use'),
        ],

        'description' => [
          '#markup' => $this->t('This content is built with render arrays')
        ]
      ],

      'technologies' => [
        '#theme' => 'item_list',
        '#title' => $this->t('Technologies'),
        '#items' => [
          $this->t('Drupal'),
          $this->t('Lit'),
          $this->t('Angular'),
          $this->t('NestJS'),
        ],
      ],

      'link' => [
        '#type' => 'link',
        '#title' => $this->t('Go to Drupal.org'),
        '#url' => Url::fromUri('https://www.drupal.org'),
        '#attributes' => [
          'target' => '_blank',
        ],
      ],
    ];
  }
}