<?php

class IceWidgetNonceToken extends sfWidgetFormInputHidden
{
  protected function configure($options = array(), $attributes = array())
  {
    $this->addOption('timeout', 86400);
    $this->addOption('salt', IceValidatorNonceToken::SALT);
    $this->addOption('action', null);

    parent::configure($options, $attributes);
  }

  public function render($name, $value = null, $attributes = array(), $errors = array())
  {
    !empty($_SESSION['_nonces']) OR $_SESSION['_nonces'] = array();

    // Make sure this array does not grow indefinitely
    if (count($_SESSION['_nonces']) > 100)
    {
      $_SESSION['_nonces'] = array_slice($_SESSION['_nonces'], -100, 100, true);
    }

    $time = time();
    $salt = $this->getOption('salt');

    $value = hash_hmac('md5', $time .'-'. $this->getOption('action') .'-'. session_id(), $salt);
    $token = sprintf('v1:%u:%s', $time, $value);

    $_SESSION['_nonces'][substr($value, -12, 10)] = $value;

    return $this->renderTag('input', array('type' => 'hidden', 'name' => $name, 'value' => $token));
  }
}
