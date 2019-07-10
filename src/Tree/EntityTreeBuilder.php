<?php

namespace Drupal\entity_reference_tree\Tree;

use Drupal\Core\Session\AccountProxyInterface;

/**
 * Provides a class for building a tree from general entity.
 *
 * @ingroup entity_reference_tree_api
 *
 * @see \Drupal\entity_reference_tree\Tree\TreeBuilderInterface
 */
class EntityTreeBuilder implements TreeBuilderInterface {

  /**
   *
   * @var string
   *   The permission name to access the tree.
   */
  private $accessPermission = 'access content';

  /**
   * Load all entities from an entity bundle for the tree.
   *
   * @param string $entityType
   *   The type of the entity.
   *
   * @param string $bundleID
   *   The bundle ID.
   *
   * @return array
   *   All entities in the entity bundle.
   */
  public function loadTree(string $entityType, string $bundleID, int $parent = 0, int $max_depth = NULL) {
    if ($this->hasAccess()) {
      if ($bundleID === '*') {
        // Load all entities regardless bundles.
        $entities = \Drupal::entityTypeManager()->getStorage($entityType)->loadMultiple();
        $hasBundle = FALSE;
      }
      else {
        $hasBundle = TRUE;
        // Build the tree node for the bundle.
        $tree = [
            (object) [
                'id' => $bundleID,
                // Required.
                'parent' => '#',
                // Node text.
                'text' => $bundleID,
            ],
        ];
        
        // Load all entity id within a bundle.
        $eids = \Drupal::entityQuery($entityType)
        ->condition('type', $bundleID)
        ->execute();
        // No entity found.
        if (empty($eids)) {
          return $tree;
        }
        
        // Load all entities matched the conditions.
        $entities = \Drupal::entityTypeManager()->getStorage($entityType)->loadMultiple($eids);
      }
      
      // Buld the tree.
      foreach ($entities as $entity) {
        $tree[] = (object) [
          'id' => $entity->id(),
        // Required.
          'parent' => $hasBundle ? $entity->bundle() : '#',
        // Node text.
          'text' => $entity->label(),
        ];
      }

      return $tree;
    }
    // The user is not allowed to access taxonomy overviews.
    return NULL;
  }

  /**
   * Create a tree node.
   *
   * @param $entity
   *   The entity for the tree node.
   *
   * @param array $selected
   *   A anrray for all selected nodes.
   *
   * @return array
   *   The tree node for the entity.
   */
  public function createTreeNode($entity, array $selected = []) {

    $node = [
    // Required.
      'id' => $entity->id,
    // Required.
      'parent' => $entity->parent,
    // Node text.
      'text' => $entity->text,
      'state' => ['selected' => FALSE],
    ];

    if (in_array($entity->id, $selected)) {
      // Initially selected node.
      $node['state']['selected'] = TRUE;
    }

    return $node;
  }

  /**
   * Get the ID of a tree node.
   *
   * @param $entity
   *   The entity for the tree node.
   *
   * @return string|int|null
   *   The id of the tree node for the entity.
   */
  public function getNodeId($entity) {
    return $entity->id;
  }

  /**
   * Check if a user has the access to the tree.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *   The user object to check.
   *
   * @return bool
   *   If the user has the access to the tree return TRUE,
   *   otherwise return FALSE.
   */
  private function hasAccess(AccountProxyInterface $user = NULL) {
    // Check current user as default.
    if (empty($user)) {
      $user = \Drupal::currentUser();
    }

    return $user->hasPermission($this->accessPermission);
  }

}
