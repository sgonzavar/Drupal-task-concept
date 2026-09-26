<?php

namespace Drupal\drupal_practice\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PracticeForm extends FormBase {

  public function getFormId(): string {
    return 'drupal_practice_form';
  }

  public static function create(ContainerInterface $container) {
    return new static();
  }

  public function buildForm(
    array $form,
    FormStateInterface $form_state,
  ): array {

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
    ];

    $form['technology'] = [
      '#type' => 'select',
      '#title' => $this->t('Favorite technology'),
      '#options' => [
        'drupal' => $this->t('Drupal'),
        'lit' => $this->t('Lit'),
        'angular' => $this->t('Angular'),
        'nestjs' => $this->t('NestJS'),
        'react' => $this->t('React'),
      ],
      '#required' => TRUE,
    ];

    $form['age'] = [
      '#type' => 'number',
      '#title' => $this->t('Age'),
      '#required' => TRUE,
      '#min' => 18,
      '#max' => 120,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();

    if ($values['age'] < 18) {
      $form_state->setErrorByName(
        'age',
        $this->t('You must be at least 18 years old')
      );
    }

    $email = $values['email'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $form_state->setErrorByName(
        'email',
        $this->t('Invalid email format')
      );
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $name = $form_state->getValue('name');
    $technology = $form_state->getValue('technology');

    $this->messenger()->addStatus(
      $this->t(
        'Hi @name, your favorite technology is @technology.',
        [
          '@name' => $name,
          '@technology' => $technology,
        ]
      )
    );
  }
}