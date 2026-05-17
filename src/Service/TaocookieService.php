<?php

namespace Drupal\tao_iching\Service;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * The TaoCookieService Service class.
 */
class TaocookieService implements EventSubscriberInterface {

  /**
   * Symphony http request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

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
   * The Taocookie Service constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The symphony http request stack.
   */
  public function __construct(
    RequestStack $request_stack,
  ) {
    $this->requestStack = $request_stack;
  }

  /**
   * The getCookieName function.
   *
   * @return string
   *   Returns the name of the cookie this service manages.
   */
  public function getCookieName() {
    return $this->cookieName;
  }

  /**
   * The getCookieValue function.
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
   * The setCookieValue function.
   *
   * @param mixed $value
   *   The new value for the cookie.
   */
  public function setCookieValue($value) {
    $this->updateCookie = TRUE;
    $this->newCookieValue = $value;
  }

  /**
   * The getUpdateCookie function.
   *
   * @return bool
   *   Whether the cookie should be updated during the response.
   */
  public function getUpdateCookie() {
    return $this->updateCookie;
  }

  /**
   * The getDeleteCookie function.
   *
   * @return bool
   *   Whether the cookie should be deleted during the response.
   */
  public function getDeleteCookie() {
    return $this->deleteCookie;
  }

  /**
   * The setDeleteCookie function.
   *
   * @param bool $delete_cookie
   *   Whether to delete the cookie during the response.
   */
  public function setDeleteCookie($delete_cookie = TRUE) {
    $this->deleteCookie = (bool) $delete_cookie;
  }

  /**
   * The getSubscribedEvents method.
   *
   * @return array
   *   An array of events this service subscribes to.
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => 'onResponse',
    ];
  }

  /**
   * React to the symfony kernel response event by managing visitor cookies.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
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
