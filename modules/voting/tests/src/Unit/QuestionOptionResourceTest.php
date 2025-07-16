<?php

namespace Drupal\Tests\voting\Unit;

use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\voting\Entity\Question;
use Drupal\voting\Plugin\rest\resource\QuestionOptionResource;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\voting\Plugin\rest\resource\QuestionOptionResource
 * @group voting
 */
class QuestionOptionResourceTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * The mocked current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $currentUser;

  /**
   * The mocked file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $fileUrlGenerator;

  /**
   * The QuestionOptionResource instance to test.
   *
   * @var \Drupal\voting\Plugin\rest\resource\QuestionOptionResource
   */
  protected $resource;

  /**
   * The mocked logger.
   *
   * @var \Psr\Log\LoggerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize('Drupal\Core\Entity\EntityTypeManagerInterface');
    $this->currentUser = $this->prophesize(AccountProxyInterface::class);
    $this->fileUrlGenerator = $this->prophesize(FileUrlGeneratorInterface::class);
    $this->logger = $this->prophesize(LoggerInterface::class);

    $container = $this->prophesize(ContainerInterface::class);
    $container->get('entity_type.manager')->willReturn($this->entityTypeManager->reveal());
    $container->get('current_user')->willReturn($this->currentUser->reveal());
    $container->get('file_url_generator')->willReturn($this->fileUrlGenerator->reveal());
    $container->getParameter('serializer.formats')->willReturn(['json']);

    // Modificação aqui: simular o logger.factory corretamente.
    $loggerFactory = $this->prophesize('Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $loggerFactory->get('voting')->willReturn($this->logger->reveal());
    $container->get('logger.factory')->willReturn($loggerFactory->reveal());

    $this->resource = QuestionOptionResource::create(
      $container->reveal(),
      [],
      'voting_question_option_resource',
      [],
    );
  }

  /**
   * Try to get a question option with valid permissions.
   */
  public function testGetWithoutPermission() {
    $this->currentUser->hasPermission('vote in polls')->willReturn(FALSE);

    $response = $this->resource->get(1);
    $this->assertEquals(403, $response->getStatusCode());
    $this->assertEquals(['message' => 'Access denied'], $response->getResponseData());
  }

  /**
   * Try to get non-existent question.
   */
  public function testGetNonExistentQuestion() {
    $this->currentUser->hasPermission('vote in polls')->willReturn(TRUE);

    $storage = $this->prophesize('Drupal\Core\Entity\EntityStorageInterface');
    $storage->load(1)->willReturn(NULL);

    $this->entityTypeManager->getStorage('voting_question')->willReturn($storage->reveal());

    $response = $this->resource->get(1);
    $this->assertEquals(404, $response->getStatusCode());
    $this->assertEquals(['message' => 'Question not found'], $response->getResponseData());
  }

  /**
   * Try to get an existing question with valid permissions.
   */
  public function testGetExistingQuestion() {
    $this->currentUser->hasPermission('vote in polls')->willReturn(TRUE);

    $question = $this->prophesize(Question::class);
    $question->id()->willReturn(1);
    $question->get('title')->willReturn((object) ['value' => 'Test Question']);
    $question->get('identifier')->willReturn((object) ['value' => 'test_question']);

    $option = $this->prophesize('Drupal\voting\Entity\Option');
    $option->id()->willReturn(1);
    $option->get('title')->willReturn((object) ['value' => 'Option 1']);
    $option->get('description')->willReturn((object) ['value' => 'Description 1']);
    $option->get('image')->willReturn((object) ['entity' => NULL]);

    $question->getOptions()->willReturn([$option->reveal()]);

    $storage = $this->prophesize('Drupal\Core\Entity\EntityStorageInterface');
    $storage->load(1)->willReturn($question->reveal());

    $this->entityTypeManager->getStorage('voting_question')->willReturn($storage->reveal());

    $response = $this->resource->get(1);
    $this->assertEquals(200, $response->getStatusCode());

    $expected_data = [
      'id' => 1,
      'title' => 'Test Question',
      'identifier' => 'test_question',
      'options' => [
        [
          'id' => 1,
          'title' => 'Option 1',
          'description' => 'Description 1',
          'image' => '',
        ],
      ],
    ];
    $this->assertEquals($expected_data, $response->getResponseData());
  }

  /**
   * Try to get an existing question without options.
   */
  public function testGetExistingQuestionWithoutOptions() {
    $this->currentUser->hasPermission('vote in polls')->willReturn(TRUE);

    $question = $this->prophesize(Question::class);
    $question->id()->willReturn(1);
    $question->get('title')->willReturn((object) ['value' => 'Test Question Without Options']);
    $question->get('identifier')->willReturn((object) ['value' => 'test_question_no_options']);

    // Configurar a pergunta para não ter opções.
    $question->getOptions()->willReturn([]);

    $storage = $this->prophesize('Drupal\Core\Entity\EntityStorageInterface');
    $storage->load(1)->willReturn($question->reveal());

    $this->entityTypeManager->getStorage('voting_question')->willReturn($storage->reveal());

    $response = $this->resource->get(1);
    $this->assertEquals(200, $response->getStatusCode());

    $expected_data = [
      'id' => 1,
      'title' => 'Test Question Without Options',
      'identifier' => 'test_question_no_options',
      'options' => [],
    ];
    $this->assertEquals($expected_data, $response->getResponseData());
  }

}
