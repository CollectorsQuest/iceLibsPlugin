<?php

/**
 * Include the Facebook SDK
 */
require dirname(__FILE__) .'/vendor/Facebook.class.php';

class IceSecurityUser extends sfBasicSecurityUser
{
  const EXTRA_FLASH_NAMESPACE = 'symfony/user/sfUser/extra-flash';

  protected static $_facebook_data = null;

  public function __construct(sfEventDispatcher $dispatcher, sfStorage $storage, $options = array())
  {
    parent::__construct($dispatcher, $storage, $options);

    self::$_facebook_data = $this->getAttribute('data', null, 'icepique/user/facebook');
  }


  /**
   * Initializes this sfUser.
   *
   * Overridden for handling of custom flash namespaces
   *
   * @see sfUser::initialize()
   */
  public function initialize(sfEventDispatcher $dispatcher, sfStorage $storage, $options = array())
  {
    parent::initialize($dispatcher, $storage, $options);

    // flag namespaced flash to be removed at shutdown
    $namespaces = $this->attributeHolder->getNames(
      self::EXTRA_FLASH_NAMESPACE.'/namespaces'
    );
    if ($this->options['use_flash'] && $namespaces)
    {
      foreach ($namespaces as $namespace)
      {
        if ($names = $this->attributeHolder->getNames($namespace.'/flash'))
        {
          if ($this->options['logging'])
          {
            $this->dispatcher->notify(new sfEvent($this, 'application.log', array(
                sprintf('Flag old flash messages for namespace %s ("%s")',
                        $namespace,
                        implode('", "', $names)),
            )));
          }

          foreach ($names as $name)
          {
            $this->attributeHolder->set(
              $name,
              true,
              $namespace.'/flash/remove'
            );
          }
        }
      }
    }
  }

  /**
   * Executes the shutdown procedure.
   *
   * Overridden for handling of custom flash namespaces
   *
   * @see sfUser::shutdown
   */
  public function shutdown()
  {
    // remove namespaced flash that are tagged to be removed
    $namespaces = $this->attributeHolder->getNames(
      self::EXTRA_FLASH_NAMESPACE.'/namespaces'
    );
    if ($this->options['use_flash'] && $namespaces)
    {
      foreach ($namespaces as $namespace)
      {
        if ($names = $this->attributeHolder->getNames($namespace.'/flash/remove'))
        {
          if ($this->options['logging'])
          {
            $this->dispatcher->notify(new sfEvent($this, 'application.log', array(
                sprintf('Remove old flash messages for namespace %s ("%s")',
                        $namespace,
                        implode('", "', $names)),
            )));
          }

          foreach ($names as $name)
          {
            $this->attributeHolder->remove(
              $name,
              null,
              $namespace.'/flash'
            );
            $this->attributeHolder->remove(
              $name,
              null,
              $namespace.'/flash/remove'
            );
          }
        }

        // if there are no flashes in the namespace, remove it
        if (!$this->attributeHolder->getNames($namespace.'/flash'))
        {
          $this->attributeHolder->remove($namespace, null, self::EXTRA_FLASH_NAMESPACE.'/namespaces');
        }
      }
    }

    parent::shutdown();
  }

  public function getFacebook($credentials = array())
  {
    if (empty($credentials))
    {
      $credentials = sfConfig::get('app_credentials_facebook');
    }

    $facebook = new IceFacebook(array(
      'appId'  => $credentials['application_id'],
      'secret' => $credentials['application_secret']
    ));

    // Check if the user logged in with the Javascript API
    if (!empty($_COOKIE['fbs_'. $facebook->getAppId()]))
    {
      parse_str(trim($_COOKIE['fbs_'. $facebook->getAppId()], '"'), $cookies);
      $facebook->setAccessToken($cookies['access_token']);
    }

    return $facebook;
  }

  public function isFacebookAuthenticated()
  {
    return $this->getFacebookId() ? true : false;
  }

  public function getFacebookUser()
  {
    if (( $facebook = $this->getFacebook() ))
    {
      return $facebook->getUser();
    }

    return null;
  }

  public function getFacebookData()
  {
    if (self::$_facebook_data == null)
    {
      $facebook = $this->getFacebook();

      if ($facebook && $facebook->getUser())
      {
        try
        {
          self::$_facebook_data = $facebook->api('/me');
        }
        catch (FacebookApiException $e) { ; }
      }

      $this->setAttribute('data', self::$_facebook_data, 'icepique/user/facebook');
    }

    return self::$_facebook_data;
  }

  public function getFacebookId()
  {
    $data = $this->getFacebookData();

    return isset($data['id']) ? $data['id'] : null;
  }

  public function can($action)
  {
    return $this->hasCredential($action);
  }

  /**
   * Gets the two letter culture.
   *
   * @return string
   */
  public function getCultureShort()
  {
    return substr($this->culture, 0, 2);
  }

  /**
   * Sets a flash variable that will be passed to the very next action.
   *
   * @param  string  $name       The name of the flash variable
   * @param  string  $value      The value of the flash variable
   * @param  bool    $persist    true if the flash have to persist for the following request (true by default)
   * @param  string  $namespace
   */
  public function setFlash($name, $value, $persist = true, $namespace = null)
  {
    if (!$this->options['use_flash'])
    {
      return;
    }

    if (null == $namespace)
    {
      $namespace = 'symfony/user/sfUser';
    }
    else
    {
      $namespace = 'symfony/user/extra-flash/'.$namespace;

      $this->attributeHolder->set($namespace, true, self::EXTRA_FLASH_NAMESPACE.'/namespaces');
    }

    $this->setAttribute($name, $value, $namespace.'/flash');

    if ($persist)
    {
      // clear removal flag
      $this->attributeHolder->remove($name, null, $namespace.'/flash/remove');
    }
    else
    {
      $this->setAttribute($name, true, $namespace.'/flash/remove');
    }
  }

  /**
   * Gets a flash variable.
   *
   * @param  string   $name       The name of the flash variable
   * @param  string   $default    The default value returned when named variable does not exist.
   * @param  string   $namespace
   *
   * @return mixed The value of the flash variable
   */
  public function getFlash($name, $default = null, $namespace = null)
  {
    if (!$this->options['use_flash'])
    {
      return $default;
    }

    if (null == $namespace)
    {
      $namespace = 'symfony/user/sfUser';
    }
    else
    {
      $namespace = 'symfony/user/extra-flash/'.$namespace;
    }

    $value = $this->getAttribute($name, $default, $namespace.'/flash');

    return $value;
  }

  /**
   * Returns true if a flash variable of the specified name exists.
   *
   * @param  string  $name      The name of the flash variable
   * @param  string  $namespace
   *
   * @return bool true if the variable exists, false otherwise
   */
  public function hasFlash($name, $namespace = null)
  {
    if (!$this->options['use_flash'])
    {
      return false;
    }

    if (null == $namespace)
    {
      $namespace = 'symfony/user/sfUser';
    }
    else
    {
      $namespace = 'symfony/user/extra-flash/'.$namespace;
    }

    return $this->hasAttribute($name, $namespace.'/flash');
  }

  public function getIpAddress()
  {
    return (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] != getenv('SERVER_ADDR')) ? $_SERVER['REMOTE_ADDR'] : getenv('HTTP_X_FORWARDED_FOR');
  }

  public function clearAttributes()
  {
    $this->getAttributeHolder()->removeNamespace('icepique/user/facebook');
  }

}