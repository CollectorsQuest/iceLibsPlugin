<?php

class IceGateKeeper
{
  public static function open($name, $type = 'feature')
  {
    $name = strtolower(sfInflector::underscore(str_replace(' ', '', $name)));

    return sfConfig::get('ice_gatekeeper_'. rtrim($type, 's') .'s_'. $name, true);
  }

  public static function locked($name, $type = 'feature')
  {
    return !self::open($name, $type);
  }
}
