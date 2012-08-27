<?php

class sfWidgetFormJQueryDateRange extends sfWidgetFormInput
{
  /**
   * Configures the current widget.
   *
   * Available options:
   *
   *  * config:      A JavaScript array that configures the JQuery date widget
   *  * date_widget: The date widget instance to use as a "base" class
   *
   * @param array $options     An array of options
   * @param array $attributes  An array of default HTML attributes
   *
   * @see sfWidgetForm
   */
  protected function configure($options = array(), $attributes = array())
  {
    $this->addOption('config', '{}');

    parent::configure($options, $attributes);
  }

  /**
   * @param  string $name        The element name
   * @param  string $value       The date displayed in this widget
   * @param  array  $attributes  An array of HTML attributes to be merged with the default HTML attributes
   * @param  array  $errors      An array of errors for the field
   *
   * @return string An HTML tag string
   *
   * @see sfWidgetForm
   */
  public function render($name, $value = null, $attributes = array(), $errors = array())
  {
    // Make array and clean empty values (to)
    $value = array_filter((array) $value);

    return parent::render($name, implode(' - ', $value), $attributes, $errors).
      sprintf(<<<EOF
<script type="text/javascript">
  jQuery(document).ready(function()
  {
    jQuery('#%s').daterangepicker(jQuery.extend({}, %s));
  });
</script>
EOF
        ,
        $this->generateId($name),
        $this->getOption('config')
      );
  }

  /**
   * Gets the stylesheet paths associated with the widget.
   *
   * @return array An array of stylesheet paths
   */
  public function getStylesheets()
  {
    return array('/assets/css/jquery/daterangepicker.css' => 'all');
  }

  /**
   * Gets the JavaScript paths associated with the widget.
   *
   * @return array An array of JavaScript paths
   */
  public function getJavascripts()
  {
    return array(
      '/assets/js/jquery/date.js',
      '/assets/js/jquery/daterangepicker.js'
    );
  }
}
