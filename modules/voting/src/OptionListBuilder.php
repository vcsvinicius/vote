<?php

namespace Drupal\voting;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Option list builder for the voting module.
 */
class OptionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['title'] = $this->t('Title');
    $header['question'] = $this->t('Question');
    $header['votes'] = $this->t('Votes');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['title'] = $entity->label();
    $row['question'] = $entity->get('question')->entity->label();
    $row['votes'] = $entity->getVoteCount() ?? 0;

    return $row + parent::buildRow($entity);
  }

}
