<?php

/**
 * Include the Facebook SDK
 */
require dirname(__FILE__) .'/vendor/Facebook.class.php';

class IceSecurityUser extends sfBasicSecurityUser
{
  protected static $_facebook_data = null;

  public function __construct(sfEventDispatcher $dispatcher, sfStorage $storage, $options = array())
  {
    parent::__construct($dispatcher, $storage, $options);

    self::$_facebook_data = $this->getAttribute('data', null, 'icepique/user/facebook');
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
  public function setFlash($name, $value, $persist = true, $namespace = 'symfony/user/sfUser')
  {
    if (!$this->options['use_flash'])
    {
      return;
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
   * @param  boolean  $delete     Whether to delete the flash after we get the value
   * @param  string   $namespace
   *
   * @return mixed The value of the flash variable
   */
  public function getFlash($name, $default = null, $delete = false, $namespace = 'symfony/user/sfUser')
  {
    if (!$this->options['use_flash'])
    {
      return $default;
    }

    $value = $this->getAttribute($name, $default, $namespace.'/flash');

    if ($delete == true)
    {
      // clear removal flag and value
      $this->attributeHolder->remove($name, null, $namespace.'/flash/remove');
      $this->attributeHolder->remove($name, null, $namespace.'/flash');
    }

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
  public function hasFlash($name, $namespace = 'symfony/user/sfUser')
  {
    if (!$this->options['use_flash'])
    {
      return false;
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