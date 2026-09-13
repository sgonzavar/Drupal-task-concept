<?php

namespace Drupal\drupal_practice\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;


// necesario para drupal discovery
#[Block(
  id: 'drupal_practice_block',
  admin_label: new TranslatableMarkup('Drupal Practice Block'),
  category: new TranslatableMarkup('Custom')
)]
class PracticeBlock extends BlockBase {

  public function build(): array {

    return [
      '#type' => 'container',

      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Mi first plugin'),
      ],

      'content' => [
        '#markup' => $this->t(
          'content for block custom'
        ),
      ],
    ];
  }
}