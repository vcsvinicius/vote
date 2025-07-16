<?php

namespace Drupal\auth_api\Authentication;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserAuthInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Authenticates users using HTTP Basic Authentication.
 */
class UserAuth implements AuthenticationProviderInterface {

  /**
   * Constructs a UserAuth object.
   *
   * @param \Drupal\user\UserAuthInterface $userAuth
   *   The user auth service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected UserAuthInterface $userAuth,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request): bool {
    return $request->headers->has('PHP_AUTH_USER') && $request->headers->has('PHP_AUTH_PW');
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request): EntityInterface|AccountInterface|null {
    $username = $request->headers->get('PHP_AUTH_USER');
    $password = $request->headers->get('PHP_AUTH_PW');

    $uid = $this->userAuth->authenticate($username, $password);
    if ($uid) {
      return $this->entityTypeManager->getStorage('user')->load($uid);
    }

    return NULL;
  }

}
