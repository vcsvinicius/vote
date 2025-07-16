<?php

namespace Drupal\voting\Plugin\rest\resource;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\voting\Entity\Answer;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource to vote on questions.
 */
#[RestResource(
  id: "voting_vote_resource",
  label: new TranslatableMarkup("Voting Vote Resource"),
  uri_paths: [
    "canonical" => "/api/voting/vote",
  ]
)]
class VoteResource extends ResourceBase {

  /**
   * Constructs a new VoteResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('voting'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('config.factory')
    );
  }

  /**
   * Responds to POST requests.
   *
   * @param array $data
   *   The data to create a new vote.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function post(array $data): ResourceResponse {
    // Check if voting is enabled.
    $votingEnabled = $this->configFactory
      ->get('voting.settings')
      ->get('enabled');
    if (!$votingEnabled) {
      return new ResourceResponse(['message' => 'Voting is currently disabled'], 403);
    }

    // Checking if the user has permission to vote in polls.
    if (!$this->currentUser->hasPermission('vote in polls')) {
      return new ResourceResponse(['message' => 'Access denied'], 403);
    }

    // Validate the input data.
    if (!isset($data['question_id']) || !isset($data['option_id'])) {
      return new ResourceResponse(['message' => 'Missing required data'], 400);
    }

    // Load the question and option entities.
    $question = $this->entityTypeManager
      ->getStorage('voting_question')
      ->load($data['question_id']);
    if (!$question) {
      return new ResourceResponse(['message' => 'Question not found'], 404);
    }

    $option = $this->entityTypeManager
      ->getStorage('voting_option')
      ->load($data['option_id']);
    if (!$option || $option->get('question')->target_id != $question->id()) {
      return new ResourceResponse(['message' => 'Invalid option for this question'], 400);
    }

    // Check if the user has already voted on this question.
    $existing_vote = $this->entityTypeManager->getStorage('voting_answer')->loadByProperties([
      'question' => $data['question_id'],
      'user' => $this->currentUser->id(),
    ]);
    if (!empty($existing_vote)) {
      return new ResourceResponse(['message' => 'You have already voted on this question'], 400);
    }

    // Create a new vote entity.
    $vote = $this->entityTypeManager->getStorage('voting_answer')->create([
      'question' => $data['question_id'],
      'option' => $data['option_id'],
      'user' => $this->currentUser->id(),
    ]);
    assert($vote instanceof Answer);
    $violations = $vote->validate();
    if ($violations->count() > 0) {
      return new ResourceResponse(['message' => $violations->__toString()], 400);
    }

    $vote->save();

    return new ResourceResponse(['message' => 'Vote recorded successfully'], 201);
  }

}
