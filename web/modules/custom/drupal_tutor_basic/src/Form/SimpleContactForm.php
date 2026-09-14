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
      '#title' => $this->t('Tu Nombre'),
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Correo Electrónico'),
      '#required' => TRUE,
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Mensaje o Sugerencia'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Enviar Mensaje'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  // Verificar que el correo pertenezca a un dominio especifico
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $form_state->setErrorByName('email', $this->t('format email incorrect'));
    }
  }

  // registrar el mensaje en los logs del sistema
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('name');
    $email = $form_state->getValue('email');

    // registrar en la db de Logs (admin/reports/dblog)
    $this->logger->info('New message to contact from @name (@email)', [
      '@name' => $name,
      '@email' => $email,
    ]);

    $this->messenger()->addStatus($this->t('Tks @name add your message', [
      '@name' => $name,
    ]));
  }
}
