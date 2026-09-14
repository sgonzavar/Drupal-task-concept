<?php

namespace Drupal\drupal_tutor_basic\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FastPageCreateForm extends FormBase {

  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  // ID unico del formulario
  public function getFormId() {
    return 'drupal_tutor_basic_fast_page_create_form';
  }

  // Creacion de los campos (Estructura visual)
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page Title'),
      '#required' => TRUE,
    ];

    $form['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Page Body'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create and publish'),
    ];

    dump($form);
    return $form;
  }

  //Validacion (Antes submit)
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $title = $form_state->getValue('title');
    if (strlen($title) < 5) {
      $form_state->setErrorByName('title', $this->t('Page title must be at least 5 characters long'));
    }
  }

  //Ejecucion (POST Real)
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $node = $this->entityTypeManager->getStorage('node')->create([
      'type' => 'page',
      'title' => $form_state->getValue('title'),
      'body' => [
        'value' => $form_state->getValue('body'),
        'format' => 'basic_html',
      ],
      'status' => 1, //publish
    ]);

    //ejecucion del guardado en DB
    $node->save();

    //Mensaje exito nativo
    $this->messenger()->addStatus($this->t('page "@title" has been created. (ID: @nid)', [
      '@title' => $node->getTitle(),
      '@nid' => $node->id(),
    ]));

    $form_state->setRedirect('entity.node.canonical', ['node' => $node->id()]);
  }
}
