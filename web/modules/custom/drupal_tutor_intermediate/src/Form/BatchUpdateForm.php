<?php

namespace Drupal\drupal_tutor_intermediate\Form;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drupal_tutor_intermediate\Services\NodeProcessor;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BatchUpdateForm extends FormBase {

  protected $nodeProcessor;

  public function __construct(NodeProcessor $node_processor) {
    $this->nodeProcessor = $node_processor;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('drupal_tutor_intermediate.node_processor')
    );
  }

  public function getFormId() {
    return 'tutor_intermediate_batch_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $nids = $this->nodeProcessor->getNodesToProcess();

    $form['info'] = [
      '#markup' => '<p>Hay <strong>' . count($nids) . '</strong> articulos listos para ser procesados.</p>',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Iniciar proceso masivo'),
      '#button_type' => 'primary',
      '#disabled' => empty($nids),
    ];

    return $form;
  }

  //EJERCICIO: Construir y lanzar el proceso Batch
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $nids = $this->nodeProcessor->getNodesToProcess();

    //Obtener el limite desde nuestra configuracion
    $limit = $this->config('drupal_tutor_intermediate.settings')->get('batch_limit') ?? 50;

    //Dividimos los NIDs en grupos (chunks) segun el limite configurado
    $chunks = array_chunk($nids, $limit);

    //Usamos BatchBuilder (el metodo moderno en Drupal 10/11)
    $batch = (new BatchBuilder())
      ->setTitle($this->t('Actualizando Articulos generados por Devel'))
      ->setFinishCallback([NodeProcessor::class, 'finishBatch'])
      ->setInitMessage($this->t('Iniciando...'))
      ->setProgressMessage($this->t('Procesando lote @current de @total.'));

    //Agregamos una operacion por cada grupo (chunk) de nodos
    foreach ($chunks as $chunk) {
      foreach ($chunk as $nid) {
        $batch->addOperation([NodeProcessor::class, 'process'], [$nid]);
      }
    }

    //Decimos a Drupal que ejecute este batch en la plantilla de carga
    batch_set($batch->toArray());
  }
}
