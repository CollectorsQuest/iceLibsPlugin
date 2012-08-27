<?php

class IceValidatorDateRange extends sfValidatorDateRange
{
  /**
   * @see sfValidatorDateRange
   */
  protected function doClean($value)
  {
    $fromField = $this->getOption('from_field');
    $toField   = $this->getOption('to_field');

    if (!is_array($value))
    {
      @list($from, $to) = explode(' - ', $value);

      $value = array(
        $fromField => !empty($from) ? date_format(new DateTime($from), 'm/d/Y') : null,
        $toField => !empty($to) ? date_format(new DateTime($to), 'm/d/Y') : null
      );
    }

    if ($value[$fromField] && $value[$toField])
    {
      $v = new sfValidatorSchemaCompare(
        $fromField, sfValidatorSchemaCompare::LESS_THAN_EQUAL, $toField,
        array('throw_global_error' => true), array('invalid' => $this->getMessage('invalid'))
      );
      $v->clean($value);
    }

    return $value;
  }
}
