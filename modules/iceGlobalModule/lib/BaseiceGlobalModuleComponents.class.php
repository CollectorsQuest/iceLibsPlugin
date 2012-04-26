<?php

class BaseiceGlobalModuleComponents extends sfComponents
{
  public function executeBreadcrumbs()
  {
    $module = $this->getContext()->getModuleName();
    $action = $this->getContext()->getActionName();

    $ice_propel_breadcrumbs = new IcePropelBreadcrumbs($module, $action);
    $this->breadcrumbs = $ice_propel_breadcrumbs->getBreadcrumbs();
    $this->separator = $ice_propel_breadcrumbs->getSeparator();
  }
}
