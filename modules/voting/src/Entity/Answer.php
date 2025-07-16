<?php

namespace Drupal\voting\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the Answer entity.
 */
#[ContentEntityType(
  id: 'voting_answer',
  label: new TranslatableMarkup('Voting Answer'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
  ],
  handlers: [
    'view_builder' => 'Drupal\Core\Entity\EntityViewBuilder',
    'list_builder' => 'Drupal\voting\AnswerListBuilder',
    'form' => [
      'default' => 'Drupal\voting\Form\AnswerForm',
      'add' => 'Drupal\voting\Form\AnswerForm',
      'edit' => 'Drupal\voting\Form\AnswerForm',
      'delete' => 'Drupal\voting\Form\AnswerDeleteForm',
    ],
  ],
  links: [
    "canonical" => "/admin/structure/voting_answer/{voting_answer}",
    "add-form" => "/admin/structure/voting_answer/add",
    "edit-form" => "/admin/structure/voting_answer/{voting_answer}/edit",
    "delete-form" => "/admin/structure/voting_answer/{voting_answer}/delete",
    "collection" => "/admin/structure/voting_answer",
  ],
  admin_permission: 'administer voting answers',
  base_table: 'voting_answer'
)]
class Answer extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['question'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Question'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'voting_question')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['option'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Option'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'voting_option')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['timestamp'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Timestamp'))
      ->setDescription(t('The time that the vote was cast.'));

    return $fields;
  }

}
