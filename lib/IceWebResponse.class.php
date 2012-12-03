<?php

class IceWebResponse extends sfWebResponse
{
  /**
   * This is not Symfony's context but rather the application from Icepique (Autoho, Bezplatno, etc)
   *
   * @var string
   */
  protected $_context = 'Icepique';

  /** @var array */
  private $_delayed_functions = array();

  /** @var integer */
  protected $bulk_update_delta_limit = 5;

  public function sendContent()
  {
    /**
     * @see http://php-fpm.org/wiki/Features#fastcgi_finish_request.28.29
     */
    if (function_exists('fastcgi_finish_request'))
    {
      parent::sendContent();

      fastcgi_finish_request();
    }

    // allow to work in test env
    elseif (!$this->options['send_http_headers'])
    {
      parent::sendContent();
    }

    /**
     * @see http://www.php.net/manual/en/features.connection-handling.php
     */
    else
    {
      set_time_limit(30);
      ignore_user_abort(true);

      ob_end_clean();

      header("Connection: close\r\n");
      header("Content-Encoding: none\r\n");

      ob_start();
      parent::sendContent();
      $size = ob_get_length();
      header('Content-Length: '. $size);

      ob_end_flush();
      flush();
    }
  }

  /**
   * Sends the HTTP headers and the content.
   */
  public function send()
  {
    // Send the headers no matter what
    $this->sendHttpHeaders();

    // Send the content only if needed/requested
    if (!$this->headerOnly)
    {
      $this->sendContent();
    }

    if (
      ($sf_context = sfContext::getInstance()) &&
      sfContext::getInstance()->has('user')
    )
    {
      $sf_context->getUser()->shutdown();
      $sf_context->getStorage()->shutdown();
    }

    if (( $functions = $this->getDelayedFunctions() ))
    foreach ($functions as $function)
    {
      // handle delayed count column increase/decrease, so that fewer UPDATE statements are executed
      if (
        is_array($function['callback']) &&
        $function['callback'][0] instanceof BaseObject &&
        in_array($function['callback'][1], array('setNumberOf', 'updateColumn', 'setNumViews')) &&
        method_exists($function['callback'][0], 'getPrimaryKey')
      )
      {
        $memcache = IceStatic::getMemcacheCache();
        $operator = $param = $number = null;

        // if the function has two params, and the second one is in the format +/-(num)
        // then we are dealing with either setNumberOf or updateColumn
        if (count($function['params']) == 2 && in_array(substr($function['params'][1], 0, 1), array('+', '-')))
        {
          // and our operator is the first char of the second param
          $operator = substr($function['params'][1], 0, 1);

          // and the number is the rest of the param
          $number = (int) substr($function['params'][1], 1);

          // and we set a reference var to the "number" param
          $param  = &$function['params'][1];
        }
        // if the function has only one param of format +/-(num)
        // then we are dealing with setNumViews
        elseif (count($function['params']) == 1 && in_array(substr($function['params'][0], 0, 1), array('+', '-')))
        {
          // and our operator is the first char of the param
          $operator = substr($function['params'][0], 0, 1);

          // and the number is the rest of the param
          $number = (int) substr($function['params'][0], 1);

          // and we set a reference var to the "number" param
          $param  = &$function['params'][0];
        }

        // key format: context-modelClass-modelPK-"setNumberOf/setNumViews/updateColumn"-columnName/number-operator
        $key = implode('-', array(
          $this->_context, get_class($function['callback'][0]), $function['callback'][0]->getPrimaryKey(),
          $function['callback'][1], $function['params'][0], $operator
        ));

        // if we managed to extract a number from the function parameters
        if ($number !== null && $param !== null && $operator !== null)
        {
          // increment our delta for this object/function/column/operator
          $delta = (int) $memcache->increment($key, $number);

          // if our delta has reached the limit
          if ($delta % $this->bulk_update_delta_limit == 0)
          {
            // reset it in memcache
            $memcache->decrement($key, $delta);

            // and set the function number param through our reference var
            // so that we will now execute a bulk column modification;
            $param = $operator . $delta;
          }
          else
          {
            // otherwize skip the execution of this delayed function
            continue;
          }
        }
      }

      try
      {
        call_user_func_array($function['callback'], $function['params']);

        if (
          is_array($function['callback']) && ($function['callback'][0] instanceof BaseObject) &&
          $function['callback'][1] != 'save' && method_exists($function['callback'][0], 'save')
        )
        {
          $function['callback'][0]->save();
        }
      }
      catch (Exception $e)
      {
        ;
      }
    } // foreach delayed function
  } // send()

  public function addDelayedFunction($callback, $params = array())
  {
    // In development we do not want to delay the execution
    if (sfConfig::get('sf_environment') == 'prod')
    {
      $this->_delayed_functions[] = array('callback' => $callback, 'params' => $params);
    }
    else
    {
      call_user_func_array($callback, $params);

      if (
        is_array($callback) && ($callback[0] instanceof BaseObject) &&
        $callback[1] != 'save' && method_exists($callback[0], 'save')
      )
      {
        $callback[0]->save();
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

  /**
   * Delayed functions that execute bulk updates on counter columns will honor
   * this limit, and execute a bulk update only once it's reached for the particular
   * model object / function / column / operator
   *
   * @param integer $value
   */
  public function setBulkUpdateDeltaLimit($value)
  {
    $this->bulk_update_delta_limit = (int) $value;
  }

}
