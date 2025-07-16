<?php

namespace Drupal\Tests\voting\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\voting\Entity\Answer;
use Drupal\voting\Entity\Question;
use Drupal\voting\Entity\Option;
use Drupal\voting\Plugin\rest\resource\VoteResource;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\voting\Plugin\rest\resource\VoteResource
 * @group voting
 */
class VoteResourceTest extends UnitTestCase {

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
   * Config factory mock.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Vote resource.
   *
   * @var \Drupal\voting\Plugin\rest\resource\VoteResource
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
    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);

    $this->loggerFactory->get('voting')->willReturn($this->logger->reveal());

    $container = $this->prophesize(ContainerInterface::class);
    $container->getParameter('serializer.formats')->willReturn(['json']);
    $container->get('logger.factory')->willReturn($this->loggerFactory->reveal());
    $container->get('entity_type.manager')->willReturn($this->entityTypeManager->reveal());
    $container->get('current_user')->willReturn($this->currentUser->reveal());
    $container->get('config.factory')->willReturn($this->configFactory->reveal());

    $this->resource = VoteResource::create(
      $container->reveal(),
      [],
      'voting_vote_resource',
      []
    );
  }

  /**
   * Test post with voting disabled.
   */
  public function testPostVotingDisabled() {
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('enabled')->willReturn(FALSE);
    $this->configFactory->get('voting.settings')->willReturn($config->reveal());

    $response = $this->resource->post([]);
    $this->assertEquals(403, $response->getStatusCode());
    $this->assertEquals(['message' => 'Voting is currently disabled'], $response->getResponseData());
  }

  /**
   * Test post without permission.
   */
  public function testPostWithoutPermission() {
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('enabled')->willReturn(TRUE);
    $this->configFactory->get('voting.settings')->willReturn($config->reveal());

    $this->currentUser->hasPermission('vote in polls')->willReturn(FALSE);

    $response = $this->resource->post([]);
    $this->assertEquals(403, $response->getStatusCode());
    $this->assertEquals(['message' => 'Access denied'], $response->getResponseData());
  }

  /**
   * Test post with missing data.
   */
  public function testPostMissingData() {
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('enabled')->willReturn(TRUE);
    $this->configFactory->get('voting.settings')->willReturn($config->reveal());

    $this->currentUser->hasPermission('vote in polls')->willReturn(TRUE);

    $response = $this->resource->post([]);
    $this->assertEquals(400, $response->getStatusCode());
    $this->assertEquals(['message' => 'Missing required data'], $response->getResponseData());
  }

  /**
   * Test post with non-existing question.
   */
  public function testPostQuestionNotFound() {
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('enabled')->willReturn(TRUE);
    $this->configFactory->get('voting.settings')->willReturn($config->reveal());

    $this->currentUser->hasPermission('vote in polls')->willReturn(TRUE);

    $questionStorage = $this->prophesize(EntityStorageInterface::class);
    $questionStorage->load(1)->willReturn(NULL);
    $this->entityTypeManager->getStorage('voting_question')->willReturn($questionStorage->reveal());

    $response = $this->resource->post(['question_id' => 1, 'option_id' => 1]);
    $this->assertEquals(404, $response->getStatusCode());
    $this->assertEquals(['message' => 'Question not found'], $response->getResponseData());
  }

  /**
   * Test successful vote.
   */
  public function testPostSuccessful() {
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('enabled')->willReturn(TRUE);
    $this->configFactory->get('voting.settings')->willReturn($config->reveal());

    $this->currentUser->hasPermission('vote in polls')->willReturn(TRUE);
    $this->currentUser->id()->willReturn(1);

    $question = $this->prophesize(Question::class);
    $question->id()->willReturn(1);

    $option = $this->prophesize(Option::class);
    $option->get('question')->willReturn((object) ['target_id' => 1]);

    $questionStorage = $this->prophesize(EntityStorageInterface::class);
    $questionStorage->load(1)->willReturn($question->reveal());
    $this->entityTypeManager->getStorage('voting_question')->willReturn($questionStorage->reveal());

    $optionStorage = $this->prophesize(EntityStorageInterface::class);
    $optionStorage->load(1)->willReturn($option->reveal());
    $this->entityTypeManager->getStorage('voting_option')->willReturn($optionStorage->reveal());

    $answerStorage = $this->prophesize(EntityStorageInterface::class);
    $answerStorage->loadByProperties(['question' => 1, 'user' => 1])->willReturn([]);
    $this->entityTypeManager->getStorage('voting_answer')->willReturn($answerStorage->reveal());

    $answer = $this->prophesize(Answer::class);
    $answer->validate()->willReturn(new \ArrayObject());
    $answer->save()->willReturn(1);
    $answerStorage->create([
      'question' => 1,
      'option' => 1,
      'user' => 1,
    ])->willReturn($answer->reveal());

    $response = $this->resource->post(['question_id' => 1, 'option_id' => 1]);
    $this->assertEquals(201, $response->getStatusCode());
    $this->assertEquals(['message' => 'Vote recorded successfully'], $response->getResponseData());
  }

  /**
   * Test post with already voted.
   */
  public function testPostAlreadyVoted() {
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('enabled')->willReturn(TRUE);
    $this->configFactory->get('voting.settings')->willReturn($config->reveal());

    $this->currentUser->hasPermission('vote in polls')->willReturn(TRUE);
    $this->currentUser->id()->willReturn(1);

    $question = $this->prophesize(Question::class);
    $question->id()->willReturn(1);

    $questionStorage = $this->prophesize(EntityStorageInterface::class);
    $questionStorage->load(1)->willReturn($question->reveal());
    $this->entityTypeManager->getStorage('voting_question')->willReturn($questionStorage->reveal());

    $option = $this->prophesize(Option::class);
    $option->get('question')->willReturn((object) ['target_id' => 1]);

    $optionStorage = $this->prophesize(EntityStorageInterface::class);
    $optionStorage->load(1)->willReturn($option->reveal());
    $this->entityTypeManager->getStorage('voting_option')->willReturn($optionStorage->reveal());

    // Simular que já existe um voto para este usuário nesta pergunta.
    $existingVote = $this->prophesize(Answer::class);
    $answerStorage = $this->prophesize(EntityStorageInterface::class);
    $answerStorage->loadByProperties(['question' => 1, 'user' => 1])->willReturn([$existingVote->reveal()]);
    $this->entityTypeManager->getStorage('voting_answer')->willReturn($answerStorage->reveal());

    $response = $this->resource->post(['question_id' => 1, 'option_id' => 1]);
    $this->assertEquals(400, $response->getStatusCode());
    $this->assertEquals(['message' => 'You have already voted on this question'], $response->getResponseData());
  }

}
