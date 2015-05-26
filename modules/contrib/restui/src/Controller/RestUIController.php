<?php
/**
 * @file
 * Contains \Drupal\restui\Controller\RestUIController.
 */

namespace Drupal\restui\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Drupal\restui\RestUIManager;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Url;

/**
 * Controller routines for REST resources.
 */
class RestUIController implements ContainerInjectionInterface {

  /**
   * Rest UI Manager Service.
   *
   * @var \Drupal\restui\RestUIManager
   */
  protected $restUIManager;

  /**
   * Resource plugin manager.
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager
   */
  protected $resourcePluginManager;

  /**
   * The URL generator to use.
   *
   * @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The route builder used to rebuild all routes.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * Injects RestUIManager Service.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('restui.manager'),
      $container->get('plugin.manager.rest'),
      $container->get('url_generator'),
      $container->get('router.builder')
    );
  }

  /**
   * Constructs a RestUIController object.
   */
  public function __construct(RestUIManager $restUIManager, ResourcePluginManager $resourcePluginManager, UrlGeneratorInterface $url_generator, RouteBuilderInterface $routeBuilder) {
    $this->restUIManager = $restUIManager;
    $this->resourcePluginManager = $resourcePluginManager;
    $this->urlGenerator = $url_generator;
    $this->routeBuilder= $routeBuilder;
  }

  /**
   * Returns an administrative overview of all REST resources.
   *
   * @return string
   *   A HTML-formatted string with the administrative page content.
   *
   */
  public function listResources() {
    // Get the list of enabled and disabled resources.
    $config = \Drupal::config('rest.settings')->get('resources') ?: array();
    // Strip out the nested method configuration, we are only interested in the
    // plugin IDs of the resources.
    $enabled_resources = array_combine(array_keys($config), array_keys($config));
    $available_resources = array('enabled' => array(), 'disabled' => array());
    $resources = $this->resourcePluginManager->getDefinitions();
    foreach ($resources as $id => $resource) {
      $status = in_array($id, $enabled_resources) ? 'enabled' : 'disabled';
      $available_resources[$status][$id] = $resource;
    }

    // Sort the list of resources by label.
    $sort_resources = function($resource_a, $resource_b) {
      return strcmp($resource_a['label'], $resource_b['label']);
    };
    if (!empty($available_resources['enabled'])) {
      uasort($available_resources['enabled'], $sort_resources);
    }
    if (!empty($available_resources['disabled'])) {
      uasort($available_resources['disabled'], $sort_resources);
    }

    // Heading.
    $list['resources_title'] = array(
      '#markup' => '<h2>' . t('REST resources') . '</h2>',
    );
    $list['resources_help'] = array(
      '#markup' => '<p>' . t('Here you can enable and disable available resources. Once a resource ' .
                             'has been enabled, you can restrict its formats and authentication by ' .
                             'clicking on its "Edit" link.') . '</p>',
    );
    $list['enabled']['heading']['#markup'] = '<h2>' . t('Enabled') . '</h2>';
    $list['disabled']['heading']['#markup'] = '<h2>' . t('Disabled') . '</h2>';

    // List of resources.
    foreach (array('enabled', 'disabled') as $status) {
      $list[$status]['#type'] = 'container';
      $list[$status]['#attributes'] = array('class' => array('rest-ui-list-section', $status));
      $list[$status]['table'] = array(
        '#theme' => 'table',
        '#header' => array(
          'resource_name' => array(
            'data' => t('Resource name'),
            'class' => array('rest-ui-name'),
          ),
          'path' => array(
            'data' => t('Path'),
            'class' => array('views-ui-path'),
          ),
          'description' => array(
            'data' => t('Description'),
            'class' => array('rest-ui-description'),
          ),
          'operations' => array(
            'data' => t('Operations'),
            'class' => array('rest-ui-operations'),
          ),
        ),
        '#rows' => array(),
      );
      foreach ($available_resources[$status] as $id => $resource) {
        $uri_paths = '<code>' . $resource['uri_paths']['canonical'] . '</code>';

        $list[$status]['table']['#rows'][$id] = array(
          'data' => array(
            'name' => $resource['label'],
            'path' =>  array('data' => array(
              '#type' => 'inline_template',
              '#template' => $uri_paths,
            )),
            'description' => array(),
            'operations' => array(),
          )
        );

        if ($status == 'disabled') {
          $list[$status]['table']['#rows'][$id]['data']['operations']['data'] = array(
            '#type' => 'operations',
            '#links' => array(
              'enable' => array(
                'title' => t('Enable'),
                'url' => Url::fromRoute('restui.edit', array('resource_id' => $id)),
              ),
            ),
          );
        }
        else {
          $list[$status]['table']['#rows'][$id]['data']['operations']['data'] = array(
            '#type' => 'operations',
            '#links' => array(
              'edit' => array(
                'title' => t('Edit'),
                'url' => Url::fromRoute('restui.edit', array('resource_id' => $id)),

              ),
              'disable' => array(
                'title' => t('Disable'),
                'url' => Url::fromRoute('restui.disable', array('resource_id' => $id), array('query' => array('token' => \Drupal::csrfToken()->get('restui_disable')))),
              ),
            ),
          );

          $list[$status]['table']['#rows'][$id]['data']['description']['data'] = array(
            '#theme' => 'restui_resource_info',
            '#resource' => $config[$id],
          );
        }
      }
    }

    $list['enabled']['table']['#empty'] = t('There are no enabled resources.');
    $list['disabled']['table']['#empty'] = t('There are no disabled resources.');
    $list['#title'] = t('REST resources');
    return $list;
  }

  /**
   * Disables a resource.
   *
   * @param string $resource_id
   *   The identifier or the REST resource.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects back to the listing page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function disable($resource_id, Request $request) {
    if (!\Drupal::csrfToken()->validate($request->query->get('token'), 'restui_disable')) {
      // Throw an access denied exception if the token is invalid or missing.
      throw new AccessDeniedHttpException();
    }

    $config = \Drupal::configFactory()->getEditable('rest.settings');
    $resources = $config->get('resources') ?: array();
    $plugin = $this->resourcePluginManager->getInstance(array('id' => $resource_id));
    if (!empty($plugin)) {
      // disable the resource.
      unset($resources[$resource_id]);
      $config->set('resources', $resources);
      $config->save();

      // Rebuild routing cache.
      $this->routeBuilder->rebuild();
      drupal_set_message(t('The resource was disabled successfully.'));
    }

    // Redirect back to the page.
    return new RedirectResponse($this->urlGenerator->generate('restui.list', array(), TRUE));
  }

}
