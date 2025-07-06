<?php

namespace Drupal\tao_iching\Service;

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TaocookieService implements EventSubscriberInterface {

  /**
   * Symphony http request stack
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack $requestStack
   */
  private RequestStack $requestStack;

  /**
   * Name of the cookie this service will manage.
   *
   * @var string
   */
  protected string $cookieName = 'sessionId';

  /**
   * The cookie value that will be set during the response event.
   *
   * @var mixed
   */
  protected mixed $newCookieValue;

  /**
   * Whether the cookie should be updated during the response.
   *
   * @var bool
   */
  protected bool $updateCookie = FALSE;

  /**
   * Whether the cookie should be deleted during the response.
   *
   * @var bool
   */
  protected bool $deleteCookie = FALSE;

  /**
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *
   */
  public function __construct(
    RequestStack $request_stack
  ) {
    $this->requestStack = $request_stack;
  }

  /**
   * @param \Psr\Container\ContainerInterface $container
   *
   * @return static
   * @throws \Psr\Container\ContainerExceptionInterface
   * @throws \Psr\Container\NotFoundExceptionInterface
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
    );
  }

  /**
   * Get this cookie's name.
   *
   * @return string
   */
  public function getCookieName() {
    return $this->cookieName;
  }

  /**
   * Get the cookie's value.
   *
   * @return mixed
   *   Cookie value.
   */
  public function getCookieValue() {
    if (!empty($this->newCookieValue)) {
      return $this->newCookieValue;
    }
    $request = $this->requestStack->getCurrentRequest();
    return $request->cookies->get($this->getCookieName());
  }

  /**
   * Set the cookie's new value.
   *
   * @param mixed $value
   */
  public function setCookieValue($value) {
    $this->updateCookie = TRUE;
    $this->newCookieValue = $value;
  }

  /**
   * Whether the cookie should be updated during the response.
   *
   * @return bool
   */
  public function getUpdateCookie() {
    return $this->updateCookie;
  }

  /**
   * Whether the cookie should be deleted during the response.
   *
   * @return bool
   */
  public function getDeleteCookie() {
    return $this->deleteCookie;
  }

  /**
   * Set weather the cookie should be deleted during the response.
   *
   * @param bool $delete_cookie
   *   Whether to delete the cookie during the response.
   */
  public function setDeleteCookie($delete_cookie = TRUE) {
    $this->deleteCookie = (bool) $delete_cookie;
  }

  /**
   * @return string[]
   *
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => 'onResponse',
    ];
  }

  /**
   * React to the symfony kernel response event by managing visitor cookies.
   *
   * @param ResponseEvent $event
   *   The event to process.
   */
  public function onResponse(ResponseEvent $event) {
    $response = $event->getResponse();
    if ($this->getUpdateCookie()) {
      $my_new_cookie = new Cookie($this->getCookieName(), $this->getCookieValue());
      $response->headers->setCookie($my_new_cookie);
    }
    if ($this->getDeleteCookie()) {
      $response->headers->clearCookie($this->getCookieName());
    }
  }

}

