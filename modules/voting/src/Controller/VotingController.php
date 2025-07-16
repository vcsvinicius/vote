<?php

namespace Drupal\voting\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\voting\Entity\Question;
use Drupal\voting\VotingService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for voting feature.
 */
class VotingController extends ControllerBase {

  /**
   * Constructor of VotingController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\voting\VotingService $votingService
   *   Voting service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request stack.
   */
  public function __construct(
    protected $entityTypeManager,
    protected VotingService $votingService,
    protected $configFactory,
    protected RequestStack $requestStack,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('voting.voting_service'),
      $container->get('config.factory'),
      $container->get('request_stack')
    );
  }

  /**
   * Page to list questions.
   *
   * @return array
   *   Rendered list of questions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function listQuestions() : array {
    $questions = $this->entityTypeManager
      ->getStorage('voting_question')
      ->loadMultiple();
    $is_voting_enabled = $this->isVotingEnabled();

    // Format questions for rendering.
    $formatted_questions = [];
    foreach ($questions as $question) {
      assert($question instanceof Question);

      $formatted_questions[] = [
        'id' => $question->id(),
        'title' => $question->get('title')->value,
        'voted' => $this->votingService->hasUserVoted($question),
      ];
    }

    return [
      '#theme' => 'voting_questions_list',
      '#questions' => $formatted_questions,
      '#is_voting_enabled' => $is_voting_enabled,
    ];
  }

  /**
   * View a specific question for end-user.
   *
   * @param int $question_id
   *   ID of the question.
   *
   * @return array
   *   Rendered question page.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function viewQuestion(int $question_id): array {
    $question = $this->entityTypeManager
      ->getStorage('voting_question')
      ->load($question_id);
    if (!$question) {
      throw new NotFoundHttpException();
    }

    // Format options for rendering.
    assert($question instanceof Question);
    $options = $question->getOptions();
    $formatted_options = [];
    foreach ($options as $option) {
      $formatted_options[] = [
        'id' => $option->id(),
        'title' => $option->get('title')->value,
        'description' => $option->get('description')->value,
        'image' => $option->get('image')->entity ? $option->get('image')->entity->createFileUrl() : NULL,
      ];
    }

    // Get user's vote for this question if any.
    $has_voted = $this->votingService->hasUserVoted($question);
    $user_vote = NULL;
    if ($has_voted) {
      $answer = $this->entityTypeManager->getStorage('voting_answer')
        ->loadByProperties([
          'user' => $this->currentUser()->id(),
          'option.entity.question' => $question_id,
        ]);
      if (!empty($answer)) {
        $answer = reset($answer);
        $user_vote = $answer->get('option')->target_id;
      }
    }

    // Get total votes for this question.
    $total_votes = 0;
    foreach ($options as $option) {
      $total_votes += $option->getVoteCount();
    }

    // Check if voting is enabled.
    $is_voting_enabled = $this->isVotingEnabled();

    return [
      '#theme' => 'voting_question_view',
      '#question' => [
        'id' => $question->id(),
        'title' => $question->get('title')->value,
        'options' => $formatted_options,
        'show_vote_count' => $question->showVoteCount(),
      ],
      '#is_voting_enabled' => $is_voting_enabled,
      '#has_voted' => $has_voted,
      '#user_vote' => $user_vote,
      '#total_votes' => $total_votes,
    ];
  }

  /**
   * Save vote for a specific question.
   *
   * @param int $question_id
   *   ID of the question.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to list of questions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function submitVote(int $question_id): RedirectResponse {
    // Check if voting is enabled.
    if (!$this->isVotingEnabled()) {
      $this->messenger()->addError($this->t('Voting is currently disabled.'));
      return $this->redirect('voting.questions_list');
    }

    // Load question and check if it exists.
    $question = $this->entityTypeManager
      ->getStorage('voting_question')
      ->load($question_id);
    if (!$question) {
      $this->messenger()->addError($this->t('Question not found.'));
      return $this->redirect('voting.questions_list');
    }
    assert($question instanceof Question);

    // Check if user has already voted on this question.
    if ($this->votingService->hasUserVoted($question)) {
      $this->messenger()->addError($this->t('You have already voted on this question.'));
      return $this->redirect('voting.questions_list');
    }

    // Load option and check if it valid option.
    $option_id = $this->requestStack
      ->getCurrentRequest()->request
      ->get('option');
    $option = $this->entityTypeManager
      ->getStorage('voting_option')
      ->load($option_id);
    if (!$option || $option->get('question')->target_id != $question_id) {
      $this->messenger()->addError($this->t('Invalid option.'));
      return $this->redirect('voting.questions_list');
    }

    // Save vote.
    $result = $this->votingService->vote($option_id);

    if ($result) {
      $this->messenger()->addStatus($this->t('Your vote has been recorded.'));
    }
    else {
      $this->messenger()->addError($this->t('There was an error recording your vote.'));
    }

    return $this->redirect('voting.questions_list');
  }

  /**
   * Check if voting is enabled.
   *
   * @return bool
   *   Whether voting is enabled or not.
   */
  private function isVotingEnabled(): bool {
    $config = $this->configFactory->get('voting.settings');
    return $config->get('enabled') ?: FALSE;
  }

}
