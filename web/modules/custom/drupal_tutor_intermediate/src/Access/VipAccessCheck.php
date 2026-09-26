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

  // Drupal pasa $account automáticamente al usar _custom_access con servicio
  public function access(AccountInterface $account) {
    if ($account->isAnonymous()) {
      return AccessResult::forbidden()->addCacheableDependency($account);
    }

    $email = $account->getEmail();

    // Obtenemos la configuracion dinamica
    $config = $this->configFactory->get('drupal_tutor_intermediate.settings');
    $required_domain = $config->get('vip_domain');

    // Verificamos si el correo termina en el dominio VIP
    if(empty($required_domain)) {
      return AccessResult::forbidden()->addCacheableDependency($config);
    }

    if(str_ends_with($email, $required_domain)) {
      return AccessResult::allowed()->addCacheableDependency($config);
    }

    return AccessResult::forbidden('Dont have access to this domain')->addCacheableDependency($config);
  }
}
