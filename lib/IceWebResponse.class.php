<?php

class IceWebResponse extends sfWebResponse
{
  /**
   * This is not Symfony's context but rather the application from Icepique (Autoho, Bezplatno, etc)
   *
   * @var string
   */
  protected $_context = 'Icepique';

  private $_delayed_functions = array();

  /**
   * Sends the HTTP headers and the content.
   */
  public function send()
  {
    // Send the headers no matter what
    $this->sendHttpHeaders();

    /**
     * @see http://php-fpm.org/wiki/Features#fastcgi_finish_request.28.29
     */
    if (function_exists('fastcgi_finish_request'))
    {
      $this->sendContent();

      fastcgi_finish_request();
    }

    /**
     * @see http://www.php.net/manual/en/features.connection-handling.php
     */
    else if (!$this->headerOnly)
    {
      set_time_limit(30);
      ignore_user_abort(true);

      ob_end_clean();

      header("Connection: close\r\n");
      header("Content-Encoding: none\r\n");

      ob_start();
      $this->sendContent();
      $size = ob_get_length();
      header("Content-Length: $size");

      ob_end_flush();
      flush();
    }

    if (
      ($sf_context = sfContext::getInstance()) &&
      sfContext::getInstance()->has('user')
    ) {
      $sf_context->getUser()->shutdown();
      $sf_context->getStorage()->shutdown();
    }

    if ($functions = $this->getDelayedFunctions())
    foreach ($functions as $function)
    {
      if (is_array($function['callback']) && in_array($function['callback'][1], array('setNumberOf', 'setNumViews')))
      {
        if (is_object($function['callback'][0]) && method_exists($function['callback'][0], 'getId'))
        {
          $memcache = IceStatic::getMemcacheCache();

          $key = $this->_context .'-'. get_class($function['callback'][0]) .'-'. $function['callback'][0]->getId() .'-'. $function['callback'][1] .'-'. $function['params'][0];
          $operator = $param = $number = null;

          if (count($function['params']) == 2 && in_array(substr($function['params'][1], 0, 1), array('+', '-')))
          {
            $operator = substr($function['params'][1], 0, 1);

            $number = (int) substr($function['params'][1], 1);
            $param  = &$function['params'][1];
          }
          else if (count($function['params']) == 1 && in_array(substr($function['params'][0], 0, 1), array('+', '-')))
          {
            $operator = '+';

            $number = (int) substr($function['params'][0], 1);
            $param  = &$function['params'][0];
          }

          if ($number !== null && $param !== null)
          {
            $i = (int) $memcache->increment($key, $number);

            if ($i % 5 == 0)
            {
              $number = ($i === 0) ? 1 : $i;
              $memcache->decrement($key, $number);
            }
            else
            {
              continue;
            }

            $param = $operator . $number;
          }
        }
      }

      try
      {
        call_user_func_array($function['callback'], $function['params']);

        if (is_array($function['callback']) && ($function['callback'][0] instanceof BaseObject) && method_exists($function['callback'][0], 'save'))
        {
          if ($function['callback'][0]->isModified()) { $function['callback'][0]->save(); }
        }
      }
      catch (Exception $e) { ; }
    }
  }

  public function addDelayedFunction($callback, $params = array())
  {
    // In development we do not want to delay the execution
    if (sfConfig::get('sf_environment') == 'prod')
    {
      $this->_delayed_callbacks[] = array('callback' => $callback, 'params' => $params);
    }
    else
    {
      call_user_func_array($callback, $params);

      if (is_array($callback) && ($callback[0] instanceof BaseObject) && $callback[1] != 'save' && method_exists($callback[0], 'save'))
      {
        if ($callback[0]->isModified()) { $callback[0]->save(); }
      }
    }
  }

  private function getDelayedFunctions()
  {
    return $this->_delayed_functions;
  }

  public function setGeoLocation($geo_location)
  {
    if ($geo_location instanceof iceModelGeoCity || $geo_location instanceof iceModelGeoRegion)
    {
      $this->addMeta('geo.region', 'BG');
      $this->addMeta('geo.placename', $geo_location->getName());
      $this->addMeta('geo.position', $geo_location->getLatitude().';'. $geo_location->getLongitude());
      $this->addMeta('ICBM', $geo_location->getLatitude().','. $geo_location->getLongitude());
    }
  }
}
