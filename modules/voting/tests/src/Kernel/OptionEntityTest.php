<?php

namespace Drupal\Tests\voting\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Tests the Option entity.
 *
 * @group voting
 */
class OptionEntityTest extends KernelTestBase {

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
   * The Option storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $optionStorage;

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
    $this->installEntitySchema('voting_answer');
    $this->installSchema('system', ['sequences']);

    // Get the storage handlers.
    $this->optionStorage = $this->container->get('entity_type.manager')->getStorage('voting_option');
    $this->questionStorage = $this->container->get('entity_type.manager')->getStorage('voting_question');
  }

  /**
   * Tests creating an Option entity.
   */
  public function testCreateOptionEntity(): void {
    // Create a Question entity.
    $question = $this->questionStorage->create([
      'title' => 'Sample Question',
      'identifier' => 'sample_identifier',
    ]);
    $question->save();

    // Create an Option entity.
    $option = $this->optionStorage->create([
      'title' => 'Sample Option',
      'question' => $question->id(),
    ]);
    $option->save();

    // Load the entity and assert its properties.
    $loaded_option = $this->optionStorage->load($option->id());
    $this->assertEquals('Sample Option', $loaded_option->get('title')->value);
    $this->assertEquals($question->id(), $loaded_option->get('question')->target_id);
  }

  /**
   * Tests the getVoteCount method.
   */
  public function testGetVoteCount(): void {
    // Create a Question entity.
    $question = $this->questionStorage->create([
      'title' => 'Sample Question',
      'identifier' => 'sample_identifier',
    ]);
    $question->save();

    // Create an Option entity.
    $option = $this->optionStorage->create([
      'title' => 'Sample Option',
      'question' => $question->id(),
    ]);
    $option->save();

    // Simulate votes by creating voting_answer entities.
    $answer_storage = $this->container->get('entity_type.manager')->getStorage('voting_answer');
    for ($i = 0; $i < 3; $i++) {
      $answer = $answer_storage->create(['option' => $option->id()]);
      $answer->save();
    }

    // Assert that the getVoteCount method returns the correct count.
    $this->assertEquals(3, $option->getVoteCount());
  }

}
