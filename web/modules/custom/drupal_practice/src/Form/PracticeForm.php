<?php

namespace Drupal\drupal_practice\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


class PracticeForm extends FormBase {


  public function getFormId(): string {
    return 'drupal_practice_form';
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
      '#title' => $this->t('Tech fav'),
      '#options' => [
        'drupal' => 'Drupal',
        'lit' => 'Lit',
        'angular' => 'Angular',
        'nestjs' => 'NestJS',
				'react' => 'React',
      ],
      '#required' => TRUE,
    ];

    $form['age'] = [
      '#type' => 'number',
      '#title' => $this->t('Age'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
    ];

    return $form;
  }


  public function validateForm(array &$form, FormStateInterface $form_state): void {

    $formSubmit = $form_state->getValues();
		// dump($form_state->getValues()); extraer todos los datos en array 

    if ($formSubmit['age'] < 18) {
      $form_state->setErrorByName(
        'age',
        $this->t('More to 18 age')
      );
    }

  }


  public function submitForm(array &$form, FormStateInterface $form_state,): void {

    $name = $form_state->getValue('name');
    $technology = $form_state->getValue('technology');

    $this->messenger()->addStatus(
      $this->t(
        'Hi @name. you tech fav is @technology.',
        [
          '@name' => $name,
          '@technology' => $technology,
        ]
      )
    );
  }
}