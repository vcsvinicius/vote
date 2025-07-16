<?php

namespace Drupal\voting\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for voting settings.
 */
class VotingSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['voting.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'voting_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('voting.settings');

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Voting System'),
      '#default_value' => $config->get('enabled'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('voting.settings')
      ->set('enabled', $form_state->getValue('enabled'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
