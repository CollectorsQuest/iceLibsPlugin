<?php

abstract class IceAjaxAction extends sfAction
{
  abstract protected function getObject(sfRequest $request);

  public function execute($request)
  {
    // In development, for non-ajax requests, show the HTML output rather than the JSON one
    if (SF_ENV == 'dev' && !$this->getRequest()->isXmlHttpRequest())
    {
      $this->getRequest()->setRequestFormat('html');
    }

    // Turning off the Symfony debug toolbar
    sfConfig::set('sf_web_debug', false);

    // Do we have an object to work with?
    $object = $this->getObject($request);

    $section = $request->getParameter('section');
    $page = $request->getParameter('page');

    $template = str_replace(' ', '', ucwords(str_replace('-', ' ', $section) .' '. $page));
    $method = 'execute'. $template;

    if ($section == 'partial')
    {
      return $this->renderPartial($this->getModuleName() .'/'. $page, array('object' => $object));
    }
    else if ($section == 'component')
    {
      return $this->renderComponent($this->getModuleName(), $page, array('object' => $object));
    }

    $this->object = $object;

    return $this->$method($request, $template);
  }

  protected function success($fastcgi_finish_request = false)
  {
    $json = $this->json(array('Success' => true));

    if ($fastcgi_finish_request === true)
    {
      echo $json;
      fastcgi_finish_request();
    }
    else
    {
      $this->output($json);
    }

    return sfView::NONE;
  }

  /**
   * Output a pre-determined error format as json:
   * {
   *   Error: {
   *     Title: $title,
   *     Message: $message
   *   }
   * }
   *
   * @param     string $title
   * @param     string $message
   * @param     boolean $fastcgi_finish_request
   * @return    sfView::NONE
   */
  protected function error($title, $message, $fastcgi_finish_request = false)
  {
    $this->getResponse()->setStatusCode(500);

    $json = $this->json(array(
      'Error' => array('Title' => $title, 'Message' => $message)
    ));

    if ($fastcgi_finish_request === true)
    {
      echo $json;
      fastcgi_finish_request();
    }
    else
    {
      $this->output($json);
    }

    return sfView::NONE;
  }

  /**
   * Output a custom error array as JSON
   *
   * @param     array $to_json
   * @param     integer $status_code
   * @param     boolean $fastcgi_finish_request
   * @return    sfView::NONE
   */
  protected function errors(array $to_json, $status_code = 500, $fastcgi_finish_request = false)
  {
    $this->getResponse()->setStatusCode($status_code);

    $json = $this->json($to_json);

    if ($fastcgi_finish_request === true)
    {
      echo $json;
      fastcgi_finish_request();
    }
    else
    {
      $this->output($json);
    }

    return sfView::NONE;
  }

  protected function json($data)
  {
    $json = json_encode($data);

    if (SF_ENV == 'dev' && !$this->getRequest()->isXmlHttpRequest())
    {
      $this->getRequest()->setRequestFormat('html');

      $this->getContext()->getConfiguration()->loadHelpers('Partial');
      $json = get_partial('iceGlobalModule/json', array('data' => $data));
    }
    else
    {
      $this->getRequest()->setRequestFormat('json');
      $this->getResponse()->setHttpHeader('Content-Type', 'application/json; charset=utf-8');
      if (strlen($json) < 4028)
      {
        $this->getResponse()->setHttpHeader('X-JSON', $json);
      }
    }

    return $json;
  }

  protected function output($text)
  {
    if (is_array($text))
    {
      $text = $this->json($text);
    }

    $this->renderText($text);

    return sfView::NONE;
  }

  protected function loadHelpers($helpers)
  {
    $configuration = sfProjectConfiguration::getActive();
    $configuration->loadHelpers($helpers);
  }

  protected function __($string, $args = array(), $catalogue = 'messages')
  {
    return $this->getContext()->getI18n()->__($string, $args, $catalogue);
  }
}
