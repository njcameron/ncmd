<?php

namespace Drupal\restui;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class RestUIAuthenticationCollector {

  /**
   * Array of all registered authentication providers, keyed by ID.
   *
   * @var \Drupal\Core\Authentication\AuthenticationProviderInterface[]
   */
  protected $providers;

  /**
   * Array of all providers and their priority.
   *
   * @var array
   */
  protected $providerOrders = array();

  /**
   * Sorted list of registered providers.
   *
   * @var \Drupal\Core\Authentication\AuthenticationProviderInterface[]
   */
  protected $sortedProviders;

  /**
   * {@inheritdoc}
   */
  public function addProvider(AuthenticationProviderInterface $provider, $provider_id, $priority = 0) {
    $this->providers[$provider_id] = $provider;
    $this->providerOrders[$priority][$provider_id] = $provider;
    // Force the builders to be re-sorted.
    $this->sortedProviders = NULL;
  }

  /**
   * Returns the id of the authentication provider for a request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   *
   * @return string|NULL
   *   The id of the first authentication provider which applies to the request.
   *   If no application detects appropriate credentials, then NULL is returned.
   */
  protected function getProvider(Request $request) {
    foreach ($this->getSortedProviders() as $provider_id => $provider) {
      if ($provider->applies($request)) {
        return $provider_id;
      }
    }
  }

  /**
   * Returns the sorted array of authentication providers.
   *
   * @todo Replace with a list of providers sorted during compile time in
   *   https://www.drupal.org/node/2432585.
   *
   * @return \Drupal\Core\Authentication\AuthenticationProviderInterface[]
   *   An array of authentication provider objects.
   */
  public function getSortedProviders() {
    if (!isset($this->sortedProviders)) {
      // Sort the builders according to priority.
      krsort($this->providerOrders);
      // Merge nested providers from $this->providers into $this->sortedProviders.
      $this->sortedProviders = array();
      foreach ($this->providerOrders as $providers) {
        $this->sortedProviders = array_merge($this->sortedProviders, $providers);
      }
    }
    return $this->sortedProviders;
  }
}
