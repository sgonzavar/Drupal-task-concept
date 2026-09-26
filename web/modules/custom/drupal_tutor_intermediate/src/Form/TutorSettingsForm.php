<?php

namespace Drupal\drupal_tutor_intermediate\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class TutorSettingsForm extends ConfigFormBase {

  // 01. Especificamos que archivo de configuracion vamos a modificar
  protected function getEditableConfigNames() {
    return ['drupal_tutor_intermediate.settings'];
  }

  public function getFormId() {
    return 'drupal_tutor_intermediate_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('drupal_tutor_intermediate.settings');

    $form['batch_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit node (Batch)'),
      '#description' => $this->t('Cuantos nodos se procesaran por cada peticion AJAX'),
      '#default_value' => $config->get('batch_limit') ?? 50,
      '#min' => 10,
      '#max' => 500,
    ];

    $form['vip_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domain VIP'),
      '#description' => $this->t('Solo los correos con este dominio podran acceder a la ruta vip'),
      '#default_value' => $config->get('vip_domain') ?? '@mipropio.com',
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory()->getEditable('drupal_tutor_intermediate.settings')
      ->set('batch_limit', $form_state->getValue('batch_limit'))
      ->set('vip_domain', $form_state->getValue('vip_domain'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
