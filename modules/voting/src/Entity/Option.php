<?php

namespace Drupal\voting\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the Option entity.
 */
#[ContentEntityType(
  id: "voting_option",
  label: new TranslatableMarkup("Voting Option"),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'label' => 'title',
  ],
  handlers: [
    'view_builder' => 'Drupal\Core\Entity\EntityViewBuilder',
    'list_builder' => 'Drupal\voting\OptionListBuilder',
    'form' => [
      'default' => 'Drupal\voting\Form\OptionForm',
      'add' => 'Drupal\voting\Form\OptionForm',
      'edit' => 'Drupal\voting\Form\OptionForm',
      'delete' => 'Drupal\voting\Form\OptionDeleteForm',
    ],
  ],
  links: [
    "canonical" => "/admin/structure/voting_option/{voting_option}",
    "add-form" => "/admin/structure/voting_option/add",
    "edit-form" => "/admin/structure/voting_option/{voting_option}/edit",
    "delete-form" => "/admin/structure/voting_option/{voting_option}/delete",
    "collection" => "/admin/structure/voting_option",
  ],
  admin_permission: 'administer voting',
  base_table: 'voting_option'
)]
class Option extends ContentEntityBase {

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

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Image'))
      ->setRequired(FALSE)
      ->setSetting('file_directory', 'voting_answers')
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * Gets the number of votes for this option.
   *
   * @return int
   *   The number of votes.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getVoteCount(): int {
    $answer_storage = \Drupal::entityTypeManager()->getStorage('voting_answer');
    $query = $answer_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('option', $this->id())
      ->count();

    return $query->execute();
  }

}
