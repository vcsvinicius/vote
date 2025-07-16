<?php

namespace Drupal\voting;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\voting\Entity\Option;
use Drupal\voting\Entity\Question;

/**
 * Voting service.
 */
class VotingService {

  /**
   * Constructor of the VotingService class.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * Save a new voting question.
   *
   * @param int $option_id
   *   The ID of the voting option.
   *
   * @return bool
   *   TRUE if the vote was saved successfully, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function vote(int $option_id): bool {
    $option = $this->entityTypeManager->getStorage('voting_option')->load($option_id);
    if (!$option) {
      return FALSE;
    }

    assert($option instanceof Option);
    $question = $option->get('question')->entity;
    if ($this->hasUserVoted($question)) {
      return FALSE;
    }

    // Save the new voting answer.
    $answer = $this->entityTypeManager->getStorage('voting_answer')->create([
      'question' => $question->id(),
      'option' => $option_id,
      'user' => $this->currentUser->id(),
    ]);
    $answer->save();

    return TRUE;
  }

  /**
   * Check if the current user has already voted for the given question.
   *
   * @param \Drupal\voting\Entity\Question $question
   *   The question entity.
   *
   * @return bool
   *   TRUE if the user has already voted, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function hasUserVoted(Question $question): bool {
    $vote_count = $this->entityTypeManager->getStorage('voting_answer')->getQuery()
      ->accessCheck(FALSE)
      ->condition('question', $question->id())
      ->condition('user', $this->currentUser->id())
      ->count()
      ->execute();

    return $vote_count > 0;
  }

  /**
   * Get the results for the given voting question.
   *
   * @param int $question_id
   *   The ID of the voting question.
   *
   * @return array|null
   *   An array of results, or NULL if the question does not exist.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getResults(int $question_id): ?array {
    $question = $this->entityTypeManager->getStorage('voting_question')->load($question_id);
    if (!$question) {
      return NULL;
    }

    $options = $this->entityTypeManager->getStorage('voting_option')->loadByProperties([
      'question' => $question_id,
    ]);

    $results = [];
    foreach ($options as $option) {
      $vote_count = $this->entityTypeManager->getStorage('voting_answer')->getQuery()
        ->accessCheck(FALSE)
        ->condition('question', $question_id)
        ->condition('option', $option->id())
        ->count()
        ->execute();

      $results[] = [
        'id' => $option->id(),
        'title' => $option->label(),
        'votes' => $vote_count,
      ];
    }

    return $results;
  }

}
