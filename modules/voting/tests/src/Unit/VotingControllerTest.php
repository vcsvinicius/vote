<?php

namespace Drupal\Tests\voting\Unit;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\voting\Controller\VotingController;
use Drupal\voting\Entity\Question;
use Drupal\voting\Entity\Option;
use Drupal\voting\VotingService;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\voting\Controller\VotingController
 * @group voting
 */
class VotingControllerTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Entity type manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Voting service mock.
   *
   * @var \Drupal\voting\VotingService
   */
  protected $votingService;

  /**
   * Config factory mock.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Request stack mock.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Messenger mock.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Translation mock.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Voting controller instance to be tested.
   *
   * @var \Drupal\voting\Controller\VotingController
   */
  protected $controller;

  /**
   * Account proxy mock.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Url generator mock.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->votingService = $this->prophesize(VotingService::class);
    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $this->requestStack = $this->prophesize(RequestStack::class);
    $this->messenger = $this->prophesize(MessengerInterface::class);
    $this->stringTranslation = $this->prophesize(TranslationInterface::class);
    $this->currentUser = $this->prophesize(AccountProxyInterface::class);
    $this->urlGenerator = $this->prophesize(UrlGeneratorInterface::class);

    $this->controller = new VotingController(
      $this->entityTypeManager->reveal(),
      $this->votingService->reveal(),
      $this->configFactory->reveal(),
      $this->requestStack->reveal(),
      $this->urlGenerator->reveal()
    );
    $this->controller->setStringTranslation($this->stringTranslation->reveal());
    $this->controller->setMessenger($this->messenger->reveal());
  }

  /**
   * Test list of questions.
   */
  public function testListQuestions() {
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('enabled')->willReturn(TRUE);
    $this->configFactory->get('voting.settings')->willReturn($config->reveal());

    $question1 = $this->prophesize(Question::class);
    $question1->id()->willReturn(1);
    $question1->get('title')->willReturn((object) ['value' => 'Question 1']);

    $question2 = $this->prophesize(Question::class);
    $question2->id()->willReturn(2);
    $question2->get('title')->willReturn((object) ['value' => 'Question 2']);

    $questionStorage = $this->prophesize(EntityStorageInterface::class);
    $questionStorage->loadMultiple()->willReturn([$question1->reveal(), $question2->reveal()]);
    $this->entityTypeManager->getStorage('voting_question')->willReturn($questionStorage->reveal());

    $this->votingService->hasUserVoted($question1->reveal())->willReturn(FALSE);
    $this->votingService->hasUserVoted($question2->reveal())->willReturn(TRUE);

    $result = $this->controller->listQuestions();

    $this->assertEquals('voting_questions_list', $result['#theme']);
    $this->assertCount(2, $result['#questions']);
    $this->assertTrue($result['#is_voting_enabled']);
    $this->assertEquals(1, $result['#questions'][0]['id']);
    $this->assertEquals('Question 1', $result['#questions'][0]['title']);
    $this->assertFalse($result['#questions'][0]['voted']);
    $this->assertEquals(2, $result['#questions'][1]['id']);
    $this->assertEquals('Question 2', $result['#questions'][1]['title']);
    $this->assertTrue($result['#questions'][1]['voted']);
  }

  /**
   * Test view question with vote count enabled.
   */
  public function testViewQuestion() {
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('enabled')->willReturn(TRUE);
    $this->configFactory->get('voting.settings')->willReturn($config->reveal());

    $question = $this->prophesize(Question::class);
    $question->id()->willReturn(1);
    $question->get('title')->willReturn((object) ['value' => 'Test Question']);
    $question->showVoteCount()->willReturn(TRUE);

    $option1 = $this->prophesize(Option::class);
    $option1->id()->willReturn(1);
    $option1->get('title')->willReturn((object) ['value' => 'Option 1']);
    $option1->get('description')->willReturn((object) ['value' => 'Description 1']);
    $option1->get('image')->willReturn((object) ['entity' => NULL]);
    $option1->getVoteCount()->willReturn(3);

    $option2 = $this->prophesize(Option::class);
    $option2->id()->willReturn(2);
    $option2->get('title')->willReturn((object) ['value' => 'Option 2']);
    $option2->get('description')->willReturn((object) ['value' => 'Description 2']);
    $option2->get('image')->willReturn((object) ['entity' => NULL]);
    $option2->getVoteCount()->willReturn(2);

    $question->getOptions()->willReturn([$option1->reveal(), $option2->reveal()]);

    $questionStorage = $this->prophesize(EntityStorageInterface::class);
    $questionStorage->load(1)->willReturn($question->reveal());
    $this->entityTypeManager->getStorage('voting_question')->willReturn($questionStorage->reveal());

    $this->votingService->hasUserVoted($question->reveal())->willReturn(FALSE);

    $result = $this->controller->viewQuestion(1);

    $this->assertEquals('voting_question_view', $result['#theme']);
    $this->assertEquals(1, $result['#question']['id']);
    $this->assertEquals('Test Question', $result['#question']['title']);
    $this->assertCount(2, $result['#question']['options']);
    $this->assertTrue($result['#question']['show_vote_count']);
    $this->assertTrue($result['#is_voting_enabled']);
    $this->assertFalse($result['#has_voted']);
    $this->assertNull($result['#user_vote']);
    $this->assertEquals(5, $result['#total_votes']);
  }

  /**
   * Test submit vote.
   */
  public function testSubmitVote() {
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('enabled')->willReturn(TRUE);
    $this->configFactory->get('voting.settings')->willReturn($config->reveal());

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

    $this->votingService->hasUserVoted($question->reveal())->willReturn(FALSE);
    $this->votingService->vote(1)->willReturn(TRUE);

    $request = new Request();
    $request->request->set('option', 1);
    $this->requestStack->getCurrentRequest()->willReturn($request);

    $this->stringTranslation->translate(Argument::any())->willReturnArgument(0);
    $this->messenger->addStatus(Argument::any())->shouldBeCalled();
    $this->urlGenerator->generateFromRoute('voting.questions_list', [], [])->willReturn('/voting/questions');

    $result = $this->controller->submitVote(1);

    $this->assertInstanceOf(RedirectResponse::class, $result);
    $this->assertEquals('/voting/questions', $result->getTargetUrl());
  }

}
