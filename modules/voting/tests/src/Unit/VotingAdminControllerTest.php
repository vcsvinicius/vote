<?php

namespace Drupal\Tests\voting\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\voting\Controller\VotingAdminController;
use Drupal\voting\Entity\Question;
use Drupal\voting\Entity\Option;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \Drupal\voting\Controller\VotingAdminController
 * @group voting
 */
class VotingAdminControllerTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Entity type manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Voting admin controller under test.
   *
   * @var \Drupal\voting\Controller\VotingAdminController
   */
  protected $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->controller = new VotingAdminController($this->entityTypeManager->reveal());
  }

  /**
   * Test access admin view question with non-existent question.
   */
  public function testAdminViewQuestionNotFound() {
    $questionStorage = $this->prophesize(EntityStorageInterface::class);
    $questionStorage->load(1)->willReturn(NULL);
    $this->entityTypeManager->getStorage('voting_question')->willReturn($questionStorage->reveal());

    $this->expectException(NotFoundHttpException::class);
    $this->controller->adminViewQuestion(1);
  }

  /**
   * Test access admin view question with valid question.
   */
  public function testAdminViewQuestion() {
    $question = $this->prophesize(Question::class);
    $question->id()->willReturn(1);
    $question->get('title')->willReturn((object) ['value' => 'Test Question']);

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

    $result = $this->controller->adminViewQuestion(1);

    $this->assertEquals('voting_admin_question_view', $result['#theme']);
    $this->assertEquals(1, $result['#question']['id']);
    $this->assertEquals('Test Question', $result['#question']['title']);
    $this->assertCount(2, $result['#question']['options']);
    $this->assertEquals(5, $result['#total_votes']);

    $this->assertEquals(1, $result['#question']['options'][0]['id']);
    $this->assertEquals('Option 1', $result['#question']['options'][0]['title']);
    $this->assertEquals('Description 1', $result['#question']['options'][0]['description']);
    $this->assertNull($result['#question']['options'][0]['image']);
    $this->assertEquals(3, $result['#question']['options'][0]['votes']);
    $this->assertEquals(60, $result['#question']['options'][0]['percentage']);

    $this->assertEquals(2, $result['#question']['options'][1]['id']);
    $this->assertEquals('Option 2', $result['#question']['options'][1]['title']);
    $this->assertEquals('Description 2', $result['#question']['options'][1]['description']);
    $this->assertNull($result['#question']['options'][1]['image']);
    $this->assertEquals(2, $result['#question']['options'][1]['votes']);
    $this->assertEquals(40, $result['#question']['options'][1]['percentage']);
  }

}
