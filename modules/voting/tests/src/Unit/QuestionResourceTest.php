<?php

namespace Drupal\Tests\voting\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\voting\Entity\Question;
use Drupal\voting\Plugin\rest\resource\QuestionResource;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\voting\Plugin\rest\resource\QuestionResource
 * @group voting
 */
class QuestionResourceTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Entity type manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Account proxy mock.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Logger factory mock.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Logger factory mock.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Question resource object.
   *
   * @var \Drupal\voting\Plugin\rest\resource\QuestionResource
   */
  protected $resource;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->currentUser = $this->prophesize(AccountProxyInterface::class);
    $this->logger = $this->prophesize(LoggerInterface::class);
    $this->loggerFactory = $this->prophesize('Drupal\Core\Logger\LoggerChannelFactoryInterface');

    $this->loggerFactory->get('voting')->willReturn($this->logger->reveal());

    $container = $this->prophesize(ContainerInterface::class);
    $container->getParameter('serializer.formats')->willReturn(['json']);
    $container->get('logger.factory')->willReturn($this->loggerFactory->reveal());
    $container->get('entity_type.manager')->willReturn($this->entityTypeManager->reveal());
    $container->get('current_user')->willReturn($this->currentUser->reveal());

    $this->resource = QuestionResource::create(
      $container->reveal(),
      [],
      'voting_question_resource',
      []
    );
  }

  /**
   * Test get questions without permission.
   */
  public function testGetWithoutPermission() {
    $this->currentUser->hasPermission('vote in polls')->willReturn(FALSE);

    $response = $this->resource->get();
    $this->assertEquals(403, $response->getStatusCode());
    $this->assertEquals(['message' => 'Access denied'], $response->getResponseData());
  }

  /**
   * Test get questions with permission.
   */
  public function testGetQuestions() {
    $this->currentUser->hasPermission('vote in polls')->willReturn(TRUE);

    $question1 = $this->prophesize(Question::class);
    $question1->id()->willReturn(1);
    $question1->get('title')->willReturn((object) ['value' => 'Question 1']);
    $question1->get('identifier')->willReturn((object) ['value' => 'question_1']);

    $question2 = $this->prophesize(Question::class);
    $question2->id()->willReturn(2);
    $question2->get('title')->willReturn((object) ['value' => 'Question 2']);
    $question2->get('identifier')->willReturn((object) ['value' => 'question_2']);

    $storage = $this->prophesize(EntityStorageInterface::class);
    $storage->loadMultiple()->willReturn([
      $question1->reveal(),
      $question2->reveal(),
    ]);

    $this->entityTypeManager->getStorage('voting_question')->willReturn($storage->reveal());

    $response = $this->resource->get();
    $this->assertEquals(200, $response->getStatusCode());

    $expected_data = [
      [
        'id' => 1,
        'title' => 'Question 1',
        'identifier' => 'question_1',
      ],
      [
        'id' => 2,
        'title' => 'Question 2',
        'identifier' => 'question_2',
      ],
    ];
    $this->assertEquals($expected_data, $response->getResponseData());
  }

  /**
   * Test get with permission, but without questions.
   */
  public function testGetNoQuestions() {
    $this->currentUser->hasPermission('vote in polls')->willReturn(TRUE);

    $storage = $this->prophesize(EntityStorageInterface::class);
    $storage->loadMultiple()->willReturn([]);

    $this->entityTypeManager->getStorage('voting_question')->willReturn($storage->reveal());

    $response = $this->resource->get();
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals([], $response->getResponseData());
  }

}
