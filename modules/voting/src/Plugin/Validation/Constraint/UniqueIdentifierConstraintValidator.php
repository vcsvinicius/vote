<?php

namespace Drupal\voting\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validates the unique identifier of a voting question.
 */
class UniqueIdentifierConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Constructor of unique identifier constraint validator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Validate if identifier is unique.
   *
   * @param mixed $value
   *   The identifier value to be validated.
   * @param \Symfony\Component\Validator\Constraint $constraint
   *   The unique identifier constraint.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function validate(mixed $value, Constraint $constraint): void {
    if (empty($value)) {
      return;
    }

    // Transform the identifier to lowercase and replace spaces per underscores.
    $identifier = current($value->getValue())['value'];
    $identifier = strtolower($identifier);
    $identifier = str_replace(' ', '_', $identifier);

    $query = $this->entityTypeManager
      ->getStorage('voting_question')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('identifier', $identifier)
      ->range(0, 1);

    $entity = $this->context->getObject()->getEntity();
    if (!$entity->isNew()) {
      $query->condition('id', $entity->id(), '<>');
    }

    $result = $query->execute();

    if (!empty($result)) {
      $this->context->addViolation($constraint->message, [
        '%value' => current($value->getValue())['value'],
      ]);
    }
  }

}
