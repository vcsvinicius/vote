<?php

namespace Drupal\voting\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Form for deleting a voting option.
 */
class OptionDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to delete the voting option %title?', [
      '%title' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('entity.voting_option.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $entity = $this->getEntity();
    $entity->delete();

    $this->messenger()->addMessage($this->t('The voting option %title has been deleted.', [
      '%title' => $entity->label(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
