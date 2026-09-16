<?php

namespace Drupal\drupal_tutor_intermediate\Controller;

use Drupal\Core\Controller\ControllerBase;

class VipController extends ControllerBase {

  /**
   * Simple page callback for the VIP area used by the route.
   */
  public function content() {
    return [
      '#markup' => $this->t('Zona VIP - acceso permitido'),
    ];
  }
}
