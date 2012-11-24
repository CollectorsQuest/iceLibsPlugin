<?php
/**
 * File: cqWidgetFormSelectChosen.class.php
 *
 * @author zecho
 * @version $Id$
 *
 */

class cqWidgetFormSelectChosen extends sfWidgetFormSelect
{

  /**
   * Constructor.
   *
   * Available options:
   *
   *  * choices:         An array of possible choices (required)
   *
   * @param array $options     An array of options
   * @param array $attributes  An array of default HTML attributes
   *
   * @see sfWidgetForm
   */
  protected function configure($options = array(), $attributes = array())
  {
    $this->addRequiredOption('choices');
    $this->addOption('translate_choices', true);
    $this->addOption('multiple', false);
    $this->addOption('no_results', false);
    $this->addOption('allow_single_deselect', true);
  }

  public function render($name, $value = null, $attributes = array(), $errors = array())
  {
    $options = new stdClass();
    if ($this->getOption('no_results'))
    {
      $options->no_results_text = $this->getOption('no_results');
    }
    $options->allow_single_deselect = $this->getOption('allow_single_deselect');


    $html = parent::render($name, $value, $attributes, $errors);
    $html .= sprintf('<script type="text/javascript">jQuery(document).ready(function(){jQuery("#%s").chosen(%s)});</script>',
      $this->generateId($name),
      json_encode($options)
    );

    return $html;
  }

//  public function getJavaScripts()
//  {
//    return array('/assets/jquery/chosen.js');
//  }
//
//  public function getStylesheets()
//  {
//    return array('/assets/jquery/chosen.css');
//  }

}
