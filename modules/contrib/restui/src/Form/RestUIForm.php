<?php

/**
 * @file
 * Contains \Drupal\restui\Form\RestUIForm.
 */

namespace Drupal\restui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Context\ContextInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Drupal\Core\Routing\RouteBuilderInterface;

/**
 * Manage REST resources.
 */
class RestUIForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The available Authentication Providers.
   *
   * @var array
   */
  protected $authenticationProviders;

  /**
   * The available serialization formats.
   *
   * @var array
   */
  protected $formats = array();

  /**
   * The REST plugin manager.
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager
   */
  protected $resourcePluginManager= array();

  /**
   * The route builder used to rebuild all routes.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * Constructs a \Drupal\user\RestForm object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandler $module_handler, array $authenticationProviders, array $formats, ResourcePluginManager $resourcePluginManager, RouteBuilderInterface $routeBuilder) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->authenticationProviders = $authenticationProviders;
    $this->formats = $formats;
    $this->resourcePluginManager = $resourcePluginManager;
    $this->routeBuilder= $routeBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      array_keys($container->get('restui.authentication_collector')->getSortedProviders()),
      $container->getParameter('serializer.formats'),
      $container->get('plugin.manager.rest'),
      $container->get('router.builder')
    );
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'restui';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'rest.settings',
    ];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   *
   * @var array $form
   *   The form array.
   * @var array $form_state
   *   The $form_state array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @var string $resource_id
   *   A string that identfies the REST resource.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function buildForm(array $form, FormStateInterface $form_state, $resource_id = NULL) {
    $plugin = $this->resourcePluginManager->getInstance(array('id' => $resource_id));
    if (empty($plugin)) {
      throw new NotFoundHttpException();
    }

    $config = \Drupal::config('rest.settings')->get('resources') ? : array();
    $methods = $plugin->availableMethods();
    $pluginDefinition = $plugin->getPluginDefinition();
    $form['#tree'] = TRUE;
    $form['resource_id'] = array('#type' => 'value', '#value' => $resource_id);
    $form['title'] = array(
      '#markup' => '<h2>' . t('Settings for resource @label', array('@label' => $pluginDefinition['label'])) . '</h2>',
    );
    $form['description'] = array(
      '#markup' => '<p>' . t('Here you can restrict which HTTP methods should this resource support.' .
                             ' And within each method, the available serialization formats and ' .
                             'authentication providers.') . '</p>',
    );
    $form['note'] = array(
      '#markup' => '<p>' . t('<b>Note:</b> Leaving all formats unchecked will enable all of them, while leaving all authentication providers unchecked will default to <code>cookie</code>') . '</p>',
    );
    $form['methods'] = array('#type' => 'container');

    foreach ($methods as $method) {
      $group = array();
      $group[$method] = array(
        '#title' => $method,
        '#type' => 'checkbox',
        '#default_value' => isset($config[$resource_id][$method]),
      );
      $group['settings'] = array(
        '#type' => 'container',
        '#attributes' => array('style' => 'padding-left:20px'),
      );

      // Available formats
      $enabled_formats = array();
      if (isset($config[$resource_id][$method]['supported_formats'])) {
        $enabled_formats = $config[$resource_id][$method]['supported_formats'];
      }
      $group['settings']['formats'] = array(
        '#title' => 'Supported formats',
        '#type' => 'checkboxes',
        '#options' => array_combine($this->formats, $this->formats),
        '#multiple' => TRUE,
        '#default_value' => $enabled_formats,
      );

      // Authentication providers.
      $enabled_auth = array();
      if (isset($config[$resource_id][$method]['supported_auth'])) {
        $enabled_auth = $config[$resource_id][$method]['supported_auth'];
      }
      $group['settings']['auth'] = array(
        '#title' => 'Authentication providers',
        '#type' => 'checkboxes',
        '#options' => array_combine($this->authenticationProviders, $this->authenticationProviders),
        '#multiple' => TRUE,
        '#default_value' => $enabled_auth,
      );
      $form['methods'][$method] = $group;
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, formstateinterface $form_state) {
    // At least one method must be checked.
    $method_checked = FALSE;
    foreach ($form_state->getValue('methods') as $method => $values) {
      if ($values[$method] == 1) {
        $method_checked = TRUE;
        // At least one format and authentication method must be selected.
        $formats = array_filter($values['settings']['formats']);
        $auth = array_filter($values['settings']['auth']);
        if (empty($formats)) {
          $form_state->setErrorByName('methods][' . $method . '][settings][formats', $this->t('At least one format must be selected for method !method.', array('!method' => $method)));
        }
        if (empty($auth)) {
          $form_state->setErrorByName('methods][' . $method . '][settings][auth' , $this->t('At least one authentication method must be selected for method !method.', array('!method' => $method)));
        }
      }
    }
    if (!$method_checked) {
      $form_state->setErrorByName('methods', $form_state, $this->t('At least one HTTP method must be selected'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, formstateinterface $form_state) {
    $methods = $form_state->getValue('methods');
    $resource_id = $form_state->getValue('resource_id');
    $resources = \Drupal::config('rest.settings')->get('resources') ?: array();
    // Reset the resource configuration.
    $resources[$resource_id] = array();
    foreach ($methods as $method => $settings) {
      if ($settings[$method] == TRUE) {
        $resources[$resource_id][$method] = array();
        // Check for selected formats.
        $formats = array_keys(array_filter($settings['settings']['formats']));
        if (!empty($formats)) {
          $resources[$resource_id][$method]['supported_formats'] = $formats;
        }
        // Check for selected authentication providers.
        $auth = array_keys(array_filter($settings['settings']['auth']));
        if (!empty($auth)) {
          $resources[$resource_id][$method]['supported_auth'] = $auth;
        }
      }
    }

    $config = \Drupal::configFactory()->getEditable('rest.settings');
    $config->set('resources', $resources);
    $config->save();

    // Rebuild routing cache.
    $this->routeBuilder->rebuild();
    drupal_set_message(t('The resource was updated successfully.'));
    // Redirect back to the listing.
    $form_state->setRedirectUrl(new Url('restui.list'));
  }

}
