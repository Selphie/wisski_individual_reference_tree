<?php

namespace Drupal\entity_reference_tree\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;

/**
 * EntityReferenceTreeController class.
 */
class  EntityReferenceTreeController extends ControllerBase {
  
  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;
  
  /**
   * The EntityReferenceTreeController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   */
  public function __construct(FormBuilder $formBuilder) {
    $this->formBuilder = $formBuilder;
  }
  
  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('form_builder')
        );
  }
  
  /**
   * Callback for opening the modal form.
   */
  public function openSearchForm(string $field_edit_id, string $bundle, string $entity_type) {
    $response = new AjaxResponse();
    
    // Get the modal form using the form builder.
    $modal_form = $this->formBuilder->getForm('Drupal\entity_reference_tree\Form\SearchForm', $field_edit_id, $bundle, $entity_type);
    
    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(new OpenModalDialogCommand('Entity tree', $modal_form, ['width' => '800']));
    
    return $response;
  }
  
  /**
   * Callback for JsTree json data.
   */
  public function treeJson(string $entity_type, string $bundles) {
    
    // Instance a entity tree builder for this entity type if it exists.
    if (\Drupal::hasService('entity_reference_' . $entity_type . '_tree_builder')) {
      $treeBuilder = \Drupal::service('entity_reference_' . $entity_type . '_tree_builder');
    }
    else {
      // Todo: A basic entity tree builder.
      $treeBuilder = \Drupal::service('entity_reference_entity_tree_builder'); 
    }
    
    $bundlesAry = explode(',', $bundles);
    $entityTrees = [];
    $entityNodeAry = [];
    
    foreach ($bundlesAry as $bundle_id) {
      $tree = $treeBuilder->loadTree($entity_type, $bundle_id);
      if (!empty($tree)) {
        $entityTrees[] = $tree;
      }
    }
    
    foreach ($entityTrees as $tree) {
      foreach ($tree as $entity) {
        // Create tree node for each entity.
        // Store them into an array passed to JS.
        // An array in JavaScript is indexed list.
        // JavaScript's array indices are always sequential
        // and start from 0.
        $entityNodeAry[] = $treeBuilder->createTreeNode($entity);
      }
    }
        
    return new JsonResponse($entityNodeAry);
  }
}