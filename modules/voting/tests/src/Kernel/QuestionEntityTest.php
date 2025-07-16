<?php

namespace Drupal\Tests\voting\Kernel;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Tests the Question entity.
 *
 * @group voting
 */
class QuestionEntityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'image',
    'file',
    'voting',
  ];

  /**
   * The Question storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $questionStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install the necessary schema.
    $this->installEntitySchema('voting_question');
    $this->installEntitySchema('voting_option');
    $this->installSchema('system', ['sequences']);

    // Get the Question storage.
    $this->questionStorage = $this->container->get('entity_type.manager')->getStorage('voting_question');
  }

  /**
   * Tests creating a Question entity.
   */
  public function testCreateQuestionEntity(): void {
    // Create a Question entity.
    $question = $this->questionStorage->create([
      'title' => 'Sample Question',
      'identifier' => 'Sample Identifier',
    ]);

    // Save the entity.
    $question->save();

    // Load the entity and assert its properties.
    $loaded_question = $this->questionStorage->load($question->id());
    $this->assertEquals('Sample Question', $loaded_question->get('title')->value);
    $this->assertEquals('sample_identifier', $loaded_question->get('identifier')->value);
  }

  /**
   * Tests the getOptions method.
   */
  public function testGetOptions(): void {
    // Create a Question entity.
    $question = $this->questionStorage->create([
      'title' => 'Sample Question',
      'identifier' => 'Sample Identifier',
    ]);
    $question->save();

    // Create a mock option entity associated with the question.
    $option_storage = $this->container->get('entity_type.manager')->getStorage('voting_option');
    $option = $option_storage->create(['question' => $question->id()]);
    $option->save();

    // Assert that the getOptions method returns the correct option.
    $options = $question->getOptions();
    $this->assertCount(1, $options);
    $this->assertEquals($option->id(), reset($options)->id());
  }

  /**
   * Tests the identifier constraint.
   */
  public function testIdentifierConstraint(): void {
    // Create a Question entity.
    $question = $this->questionStorage->create([
      'title' => 'Sample Question',
      'identifier' => 'Sample Identifier',
    ]);
    $question->save();

    // Attempt to change the identifier and expect an exception.
    try {
      $question->set('identifier', 'New Identifier');
      $question->save();
      $this->fail('Expected exception not thrown.');
    }
    catch (EntityStorageException $e) {
      $this->assertStringContainsString('The identifier cannot be changed after creation.', $e->getMessage());
    }
  }

  /**
   * Tests creating a Question with a duplicate identifier.
   */
  public function testDuplicateIdentifier(): void {
    // Create the first Question entity.
    $question1 = $this->questionStorage->create([
      'title' => 'First Question',
      'identifier' => 'unique_identifier',
    ]);
    $question1->save();

    // Attempt to create a second Question entity with the same identifier.
    $question2 = $this->questionStorage->create([
      'title' => 'Second Question',
      'identifier' => 'unique_identifier',
    ]);

    // Validate the entity to trigger constraints.
    $violations = $question2->validate();
    $this->assertGreaterThan(0, $violations->count(), 'Expected validation violations for duplicate identifier.');

    // Check if the specific violation message is present.
    $violation_messages = [];
    foreach ($violations as $violation) {
      $violation_messages[] = (string) $violation->getMessage();
    }
    $this->assertContains('The identifier <em class="placeholder">unique_identifier</em> is already in use. It must be unique.', $violation_messages);
  }

}
