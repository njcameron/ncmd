<?php
/**
 * @file
 * Contains \Drupal\ncd8ConfigForm
 */
namespace Drupal\ncd8_config_manager;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure hello settings for this site.
 */
class ncd8ConfigForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ncd8_config_manager_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ncd8_config_manager.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ncd8_config_manager.settings');
    $strings = $config->get('strings');
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $config->get('name'),
    );
    $form['catch_line'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('catch_line'),
      '#default_value' => $config->get('catch_line'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ncd8_config_manager.settings')
      ->set('name', $form_state->getValue('name'))
      ->set('catch_line', $form_state->getValue('catch_line'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}