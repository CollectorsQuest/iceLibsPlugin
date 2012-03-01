<?php

class IceValidatorNonceToken extends sfValidatorBase
{
  const SALT = 'TLYMr9Xgatw4KzfjAsNnmQCa';

  protected function configure($options = array(), $messages = array())
  {
    $this->setOption('required', true);

    $this->addOption('timeout', 86400);
    $this->addOption('salt', self::SALT);
    $this->addOption('action', null);

    $this->addMessage('nonce_timeout', 'Nonce timeout detected.');
    $this->addMessage('nonce_invalid', 'Inalid Nonce detected.');
    $this->addMessage('nonce_error', 'Nonce error detected.');
  }

  protected function doClean($token)
  {
    list($version, $time, $value1) = explode(':', $token);

    // Check immediately for the timeout
    if (time() - $this->getOption('timeout') > $time)
    {
      throw new sfValidatorError($this, 'nonce_timeout');
    }

    $name = substr($value1, -12, 10);
    $value2 = !empty($_SESSION['_nonces'][$name]) ? $_SESSION['_nonces'][$name] : null;

    // Unset the session variable
    unset($_SESSION['_nonces'][$name]);

    if ($value1 !== $value2)
    {
      throw new sfValidatorError($this, 'nonce_invalid');
    }

    return $token;
  }
}
