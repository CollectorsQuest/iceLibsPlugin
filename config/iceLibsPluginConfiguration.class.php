<?php

class iceLibsPluginConfiguration extends sfPluginConfiguration
{
  /**
   * @see sfPluginConfiguration
   */
  public function initialize()
  {
    $this->loadKintConfiguration();

    if ($GLOBALS['_kint_settings']['enabled'] && $this->configuration instanceof sfApplicationConfiguration)
    {
      $this->configuration->loadHelpers(array('IceKint'));
    }
  }

  public function loadKintConfiguration()
  {
    $config = sfConfig::get('app_ice_libs_kint', array());

    $GLOBALS['_kint_settings'] = array(
    	/**
    	 * @var callback
    	 *
    	 * @param string $file filename where the function was called
    	 * @param int|NULL $line the line number in the file (not applicable when used in resource dumps)
    	 */
    	'pathDisplayCallback' => isset($config['path_display_callback']) ? $config['path_display_callback'] : 'Kint::_debugPath',

    	/** @var int max length of string before it is truncated and displayed separately in full */
    	'maxStrLength' => isset($config['max_str_length']) ? $config['max_str_length'] : 60,

    	/** @var int max array/object levels to go deep, if zero no limits are applied */
    	'maxLevels' => isset($config['max_levels']) ? $config['max_levels'] : 5,

    	/** @var bool if set to false, kint will become silent */
    	'enabled' => isset($config['enabled']) ? $config['enabled'] : false,

    	/** @var string the css file to format the output of kint */
    	'skin' => isset($config['skin']) ? $config['skin'] : 'kint.css',
    );
  }
}
