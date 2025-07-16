<?php

namespace Drupal\voting\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\voting\Entity\Question;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for admin voting pages.
 */
class VotingAdminController extends ControllerBase {

  /**
   * Construct of VotingAdminController file.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(
    protected $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Admin view page for question detail.
   *
   * @param int $question_id
   *   The ID of the question.
   *
   * @return array
   *   Render array for the admin view page.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function adminViewQuestion(int $question_id): array {
    $question = $this->entityTypeManager
      ->getStorage('voting_question')
      ->load($question_id);
    if (!$question) {
      throw new NotFoundHttpException();
    }

    assert($question instanceof Question);
    $options = $question->getOptions();

    // Calculate total votes.
    $total_votes = 0;
    foreach ($options as $option) {
      $total_votes += $option->getVoteCount();
    }

    // Format options and calculate vote counts.
    $formatted_options = [];
    foreach ($options as $option) {
      $vote_count = $option->getVoteCount();

      $formatted_options[] = [
        'id' => $option->id(),
        'title' => $option->get('title')->value,
        'description' => $option->get('description')->value,
        'image' => $option->get('image')->entity ? $option->get('image')->entity->createFileUrl() : NULL,
        'votes' => $vote_count,
        // Calculate percentage of total votes.
        'percentage' => ($total_votes > 0) ? round(($vote_count / $total_votes) * 100, 2) : 0,
      ];
    }

    return [
      '#theme' => 'voting_admin_question_view',
      '#question' => [
        'id' => $question->id(),
        'title' => $question->get('title')->value,
        'options' => $formatted_options,
      ],
      '#total_votes' => $total_votes,
    ];
  }

}
