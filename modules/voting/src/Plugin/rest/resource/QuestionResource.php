<?php

namespace Drupal\voting\Plugin\rest\resource;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\voting\Entity\Question;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource to get voting questions.
 */
#[\Drupal\rest\Attribute\RestResource(
  id: "voting_question_resource",
  label: new TranslatableMarkup("Voting Question Resource"),
  uri_paths: [
    "canonical" => "/api/voting/questions",
  ]
)]
class QuestionResource extends ResourceBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
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
      $container->get('current_user')
    );
  }

  /**
   * Return all question from a GET request.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function get(): ResourceResponse {
    if (!$this->currentUser->hasPermission('vote in polls')) {
      return new ResourceResponse(['message' => 'Access denied'], 403);
    }

    // List all questions.
    $questions = $this->entityTypeManager->getStorage('voting_question')->loadMultiple();
    $data = [];
    foreach ($questions as $question) {
      assert($question instanceof Question);
      $data[] = $this->formatQuestion($question);
    }

    return new ResourceResponse($data);
  }

  /**
   * Formats a question entity for API output.
   *
   * @param \Drupal\voting\Entity\Question $question
   *   The question entity.
   *
   * @return array
   *   The formatted question data.
   */
  protected function formatQuestion(Question $question): array {
    return [
      'id' => $question->id(),
      'title' => $question->get('title')->value,
      'identifier' => $question->get('identifier')->value,
    ];
  }

}
