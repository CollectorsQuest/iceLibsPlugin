<?php

class BaseiceGlobalModuleActions extends sfActions
{
  /**
   * Output captcha image
   *
   * @param \sfRequest $request
   * @return string
   */
  public function executeCaptchaImage(sfRequest $request)
  {
    // Turn off the layout
    $this->setLayout(false);

    // Turn off the web debug
    sfConfig::set('sf_web_debug', false);

    /** @var $sf_response sfWebResponse */
    $sf_response = $this->getResponse();

    // Make sure we send a .gif and laso turn off browser caching
    $sf_response->setContentType('image/png');
    $sf_response->setHttpHeader('Expires', $sf_response->getDate(time()), true);
    $sf_response->setHttpHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT', true);
    $sf_response->setHttpHeader('Cache-Control', 'no-cache', true);

    $captcha = new IceCaptcha();

    if ($request->getParameter('w') > 0)
    {
      $captcha->setWidth($request->getParameter('w'));
    }
    if ($request->getParameter('h') > 0)
    {
      $captcha->setHeight($request->getParameter('h'));
    }
    if ($request->getParameter('bc'))
    {
      $captcha->setBackgroundColor($request->getParameter('bc'));
    }
    if ($request->getParameter('fc'))
    {
      $captcha->setFontColor($request->getParameter('fc'));
    }
    if ($request->getParameter('fs') > 0)
    {
      $captcha->setFontSize($request->getParameter('fs'));
    }
    if ($request->getParameter('cl') > 0)
    {
      $captcha->setCodeLength($request->getParameter('cl'));
    }

    // Generate the actual image and send to the browser
    $captcha->generateImage();

    // Send the Response with the Headers
    $this->getResponse()->send();

    /**
     * @see http://php-fpm.org/wiki/Features#fastcgi_finish_request.28.29
     */
    if ( sfConfig::get('app_ice_captcha_early_fcgi_finish_request', true)
      && function_exists('fastcgi_finish_request') )
    {
      fastcgi_finish_request();
    }

    $captchas   = sfContext::getInstance()->getUser()->getAttribute('captchas', array(), 'ice_captcha');
    $captchas   = array_reverse($captchas);
    $captchas[] = $captcha->getSecurityCode();
    $captchas   = array_reverse($captchas);

    // Save the new captchas in the session (the last 5 of them)
    sfContext::getInstance()->getUser()->setAttribute('captchas', array_slice($captchas, 0, 5), 'ice_captcha');
    sfContext::getInstance()->getUser()->setAttribute('captcha', $captcha->getSecurityCode());

    return sfView::NONE;
  }

  protected function sendEmail($to, $subject, $body)
  {
    return true;
  }
}
