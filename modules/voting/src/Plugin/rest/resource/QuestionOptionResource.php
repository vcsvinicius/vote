<?php

namespace Drupal\voting\Plugin\rest\resource;

use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\voting\Entity\Question;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource to get voting questions.
 */
#[\Drupal\rest\Attribute\RestResource(
  id: "voting_question_option_resource",
  label: new TranslatableMarkup("Voting question and options Resource"),
  uri_paths: [
    "canonical" => "/api/voting/question/{id}",
  ]
)]
class QuestionOptionResource extends ResourceBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    protected $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
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
      $container->get('file_url_generator')
    );
  }

  /**
   * Return a question with option from GET request.
   *
   * @param null $id
   *   The ID of the voting question.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function get($id = NULL) {
    if (!$this->currentUser->hasPermission('vote in polls')) {
      return new ResourceResponse(['message' => 'Access denied'], 403);
    }

    // Get a specific question.
    $question = $this->entityTypeManager
      ->getStorage('voting_question')
      ->load($id);
    if (!$question) {
      return new ResourceResponse(['message' => 'Question not found'], 404);
    }
    $data = $this->formatQuestion($question);

    return new ResourceResponse($data);
  }

  /**
   * Formats a question entity for API output.
   *
   * @param \Drupal\voting\Entity\Question $question
   *   The question entity.
   *
   * @return array
   *   An array containing the question data.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function formatQuestion(Question $question): array {
    $options = [];
    foreach ($question->getOptions() as $option) {
      $image_url = '';
      if ($option->get('image')->entity) {
        $image_url = $this->fileUrlGenerator->generateAbsoluteString($option->get('image')->entity->getFileUri());
      }

      $options[] = [
        'id' => $option->id(),
        'title' => $option->get('title')->value,
        'description' => $option->get('description')->value,
        'image' => $image_url,
      ];
    }

    return [
      'id' => $question->id(),
      'title' => $question->get('title')->value,
      'identifier' => $question->get('identifier')->value,
      'options' => $options,
    ];
  }

}
