<?php

namespace Drupal\drupal_tutor_basic\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SimpleContactForm extends FormBase {

  protected $logger;

  // Inyectar servicio logger de drupal
  public function __construct(LoggerChannelFactoryInterface $loggerFactory) {
    $this->logger = $loggerFactory->get('drupal_tutor_basic');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')
    );
  }

  public function getFormId() {
    return 'drupal_tutor_basic_contact_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your Name'),
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#required' => TRUE,
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message or Suggestion'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Message'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  // Validar formato de email
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $form_state->setErrorByName('email', $this->t('Invalid email format'));
    }
  }

  // Registrar el mensaje en los logs del sistema
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('name');
    $email = $form_state->getValue('email');

    // Registrar en la DB de Logs (admin/reports/dblog)
    $this->logger->info('New contact message from @name (@email)', [
      '@name' => $name,
      '@email' => $email,
    ]);

    $this->messenger()->addStatus($this->t('Thanks @name for your message', [
      '@name' => $name,
    ]));
  }
}