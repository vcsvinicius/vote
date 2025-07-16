<?php

namespace Drupal\Tests\voting\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Tests the Answer entity.
 *
 * @group voting
 */
class AnswerEntityTest extends KernelTestBase {

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
   * The Answer storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $answerStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install the necessary schema.
    $this->installEntitySchema('voting_answer');
    $this->installEntitySchema('user');
    $this->installEntitySchema('voting_question');
    $this->installEntitySchema('voting_option');
    $this->installSchema('system', ['sequences']);

    // Get the Answer storage.
    $this->answerStorage = $this->container->get('entity_type.manager')->getStorage('voting_answer');
  }

  /**
   * Tests creating an Answer entity.
   */
  public function testCreateAnswerEntity(): void {
    // Create a user entity to reference.
    $user = $this->createUser();

    // Create mock question and option entities.
    $question = $this->createMockEntity('voting_question');
    $option = $this->createMockEntity('voting_option');

    // Create an Answer entity.
    $answer = $this->answerStorage->create([
      'question' => $question->id(),
      'option' => $option->id(),
      'user' => $user->id(),
    ]);

    // Save the entity.
    $answer->save();

    // Load the entity and assert its properties.
    $loaded_answer = $this->answerStorage->load($answer->id());
    $this->assertEquals($question->id(), $loaded_answer->get('question')->target_id);
    $this->assertEquals($option->id(), $loaded_answer->get('option')->target_id);
    $this->assertEquals($user->id(), $loaded_answer->get('user')->target_id);
  }

  /**
   * Creates a user entity.
   *
   * @return \Drupal\user\UserInterface
   *   The created user entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createUser(): UserInterface {
    $user_storage = $this->container
      ->get('entity_type.manager')
      ->getStorage('user');

    $user = $user_storage->create([
      'name' => $this->randomMachineName(),
      'status' => 1,
    ]);
    $user->save();

    return $user;
  }

  /**
   * Creates a mock entity for testing.
   *
   * @param string $entity_type
   *   The entity type to create.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created mock entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function createMockEntity(string $entity_type): EntityInterface {
    $storage = $this->container->get('entity_type.manager')->getStorage($entity_type);
    $entity = $storage->create([]);
    $entity->save();
    return $entity;
  }

}
