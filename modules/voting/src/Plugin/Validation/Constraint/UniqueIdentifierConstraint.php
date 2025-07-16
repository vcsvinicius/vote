<?php

namespace Drupal\voting\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks that the submitted value is a unique identifier.
 */
#[Constraint(
  id: 'UniqueIdentifier',
  label: new TranslatableMarkup('Unique Identifier', [], ['context' => 'Validation']),
  type:'string',
)]
class UniqueIdentifierConstraint extends SymfonyConstraint {
  /**
   * The error message when the value is not unique.
   *
   * @var string
   */
  public string $message = 'The identifier %value is already in use. It must be unique.';

}
