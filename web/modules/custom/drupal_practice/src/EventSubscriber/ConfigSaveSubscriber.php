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
    $this->logger->notice(
      'save config: @config',
      [
        '@config' => $config->getName(),
      ]
    );
  }
}