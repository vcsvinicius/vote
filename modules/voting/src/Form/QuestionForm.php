<?php

namespace Drupal\voting\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for creating or editing a question entity.
 */
class QuestionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $entity = $this->entity;

    // Disable the identifier field if this is an existing entity.
    if (!$entity->isNew()) {
      $form['identifier']['widget'][0]['value']['#disabled'] = TRUE;
      $form['identifier']['widget'][0]['value']['#description'] = $this->t('The identifier cannot be changed after creation.');
    }
    else {
      $form['identifier']['widget'][0]['value']['#description'] = $this->t('The identifier will be converted to lowercase and spaces will be removed. This can be used in routes and cannot be changed after creation.');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $entity = $this->buildEntity($form, $form_state);

    // If this is an existing entity, ensure the identifier hasn't been changed.
    if (!$entity->isNew()) {
      if ($entity->get('identifier')->value !== current($form_state->getValue('identifier'))['value']) {
        $form_state->setErrorByName('identifier', $this->t('The identifier cannot be changed after creation.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): void {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Question.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Question.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.voting_question.collection');
  }

}
