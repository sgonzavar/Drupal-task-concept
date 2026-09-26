<?php

namespace Drupal\drupal_practice\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigSaveSubscriber implements EventSubscriberInterface {

  protected LoggerInterface $logger;

  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  public static function getSubscribedEvents(): array {
    return [
      ConfigEvents::SAVE => 'onConfigSave',
    ];
  }

  public function onConfigSave(ConfigCrudEvent $event): void {
    $config = $event->getConfig();
    $config_name = $config->getName();

    // Only log configuration changes for this module's config
    if (str_starts_with($config_name, 'drupal_practice.')) {
      $this->logger->notice(
        'Configuration saved: @config',
        [
          '@config' => $config_name,
        ]
      );
    }
  }
}