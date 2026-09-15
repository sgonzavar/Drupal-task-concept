<?php

namespace Drupal\drupal_tutor_intermediate\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;

class VipAccessCheck  {

  protected $currentUser;
  protected $configFactory;

  // Inyeccion de elementos
  public function __construct(AccountInterface $currentUser, ConfigFactoryInterface $configFactory) {
    $this->currentUser = $currentUser;
    $this->configFactory = $configFactory;
  }

  // Metodo que drupal llamara automaticamente.
  // IMPORTANTE: Debe devolver un objeto AccesResult.

  public function access() {
    if ($this->currentUser->isAnonymous()) {
      return AccessResult::forbidden('Back to Login');
    }

    $email = $this->currentUser->getEmail();

    // Obtenemos la configuracion dinamica
    $config = $this->configFactory->get('drupal_tutor_intermediate.settings');
    $required_domain = $config->get('vip_domain');

    // Verificamos si el correo termina en el dominio VIP
    if(str_ends_with($email, $required_domain)) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden('Dont have access to this domain');
  }
}
