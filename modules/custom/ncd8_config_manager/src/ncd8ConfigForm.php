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
    $ncd8_config = $this->config('ncd8_config_manager.settings');
    $strings = $ncd8_config->get('strings');

    foreach ($strings as $string_set_ID => $string_set) {
      $form[$string_set_ID] = array(
        '#type' => 'fieldgroup',
        '#title' => $string_set_ID,
      );
      foreach ($string_set as $id => $string) {
        // #dirtyhack warning. @todo figure out how to set rich text / normal string
        // in code.

        // This is the default form setting.
        $form[$id] = array(
          '#type' => 'textfield',
          '#title' => $id,
          '#default_value' => $string,
        );

        // For longer strings use a text area.
        if (strlen($string) > 20) {
          $form[$id] = array(
            '#type' => 'textarea',
            '#title' => $id,
            '#default_value' => $string,
          );
        }

        // For rich text use a WYSIWIG.
        if(!empty($string["value"])) {
          $form[$id] = array(
            '#type' => 'text_format',
            '#default_value' => $string["value"],
            '#title' => $id,
          );
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $var_group = "strings";

    $strings = $this->config('ncd8_config_manager.settings')->get($var_group);

    foreach ($strings as $string_set_ID => $string_set) {
      foreach ($string_set as $id => $string) {
        $key = $var_group . '.' . $string_set_ID . '.' . $id;
        $value = $form_state->getValue($id);
        $this->config('ncd8_config_manager.settings')->set($key, $value);
      }
    }

    $this->config('ncd8_config_manager.settings')->save();

    parent::submitForm($form, $form_state);
  }
}