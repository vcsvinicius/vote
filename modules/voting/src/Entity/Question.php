<?php

namespace Drupal\voting\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the Question entity.
 */
#[ContentEntityType(
  id: "voting_question",
  label: new TranslatableMarkup("Voting Question"),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'label' => 'title',
  ],
  handlers: [
    'view_builder' => 'Drupal\Core\Entity\EntityViewBuilder',
    'list_builder' => 'Drupal\voting\QuestionListBuilder',
    'form' => [
      'default' => 'Drupal\voting\Form\QuestionForm',
      'add' => 'Drupal\voting\Form\QuestionForm',
      'edit' => 'Drupal\voting\Form\QuestionForm',
      'delete' => 'Drupal\voting\Form\QuestionDeleteForm',
    ],
  ],
  links: [
    "add-form" => "/admin/structure/voting_question/add",
    "edit-form" => "/admin/structure/voting_question/{voting_question}/edit",
    "delete-form" => "/admin/structure/voting_question/{voting_question}/delete",
    "collection" => "/admin/structure/voting_question",
  ],
  admin_permission: 'administer voting',
  base_table: 'voting_question'
)]
class Question extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Question'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['identifier'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Identifier'))
      ->setDescription(t('The identifier will be converted to lowercase and spaces will be replaced. This will be used as machine name.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->addConstraint('UniqueIdentifier');

    $fields['show_vote_count'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Show vote count'))
      ->setDescription(t('Whether to display the total number of votes for this question.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Gets the show vote count setting.
   *
   * @return bool
   *   TRUE if the vote count should be shown, FALSE otherwise.
   */
  public function showVoteCount(): bool {
    return $this->get('show_vote_count')->value;
  }

  /**
   * Sets the show vote count setting.
   *
   * @param bool $show
   *   TRUE to show the vote count, FALSE to hide it.
   *
   * @return $this
   */
  public function setShowVoteCount(bool $show): static {
    $this->set('show_vote_count', $show);
    return $this;
  }

  /**
   * Get all options for this question.
   *
   * @return array
   *   An array of options for this question.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getOptions(): array {
    return $this->entityTypeManager()
      ->getStorage('voting_option')
      ->loadByProperties(['question' => $this->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    // When is updated, check if the identifier has changed.
    // If so, throw an exception.
    if (!$this->isNew() && $this->get('identifier')->value !== $this->original->get('identifier')->value) {
      throw new \LogicException(new TranslatableMarkup('The identifier cannot be changed after creation.'));
    }

    // Convert identifier field to lowercase and remove spaces.
    if ($this->hasField('identifier')) {
      $identifier = $this->get('identifier')->value;
      $identifier = strtolower($identifier);
      $identifier = str_replace(' ', '_', $identifier);
      $this->set('identifier', $identifier);
    }
  }

}
