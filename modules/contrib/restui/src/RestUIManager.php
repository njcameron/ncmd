<?php
/**
 * @file
 * Contains \Drupal\restui\RestUIManager.
 */

namespace Drupal\restui;

use Drupal\Core\Entity\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Rest UI Manager Service.
 */
class RestUIManager {

  /**
   * Entity manager Service Object.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Resources Array.
   *
   * @var array
   */
  protected $resources;

  /**
   * Constructs a RestUIManager object.
   */
  public function __construct(EntityManager $entityManager) {
    $this->entityManager = $entityManager;
  }

  /**
   * Returns an array of all resources.
   *
   * @return
   *   An array of all resources.
   */
  public function getAllResources() {
    if (!isset($this->resources)) {
      $this->loadResources();
    }
    return $this->resources;
  }

  /**
   * Loads Rest resources Array.
   */
  protected function loadResources() {
    $this->resources = array(array('name' => 'node'));
  }

}
