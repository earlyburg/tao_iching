<?php

namespace Drupal\tao_iching\Service;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TaocookieService implements EventSubscriberInterface {

  /**
   * Current request.
   *
   * @var Request
   */
  protected Request $request;

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
   * @param RequestStack $request_stack
   *
   */
  public function __construct(RequestStack $request_stack) {
    $this->request = $request_stack->getCurrentRequest();
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
    return $this->request->cookies->get($this->getCookieName());
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

