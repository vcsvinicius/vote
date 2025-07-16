<?php

namespace Drupal\voting;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Question list builder for the voting module.
 */
class QuestionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() : array {
    $header['identifier'] = $this->t('Identifier');
    $header['title'] = $this->t('Title');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['identifier'] = $entity->get('identifier')->value;
    $row['title'] = $entity->label();

    $operations = $this->getOperations($entity);
    $operations['view'] = [
      'title' => $this->t('View'),
      'url' => Url::fromRoute('voting.admin_question_view', ['question_id' => $entity->id()]),
      'weight' => 90,
    ];

    $row['operations'] = [
      'data' => [
        '#type' => 'operations',
        '#links' => $operations,
      ],
    ];

    return $row;
  }

}
