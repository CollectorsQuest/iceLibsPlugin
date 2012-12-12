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
   * Render an ajax error. Two method signatures are allowed:
   *
   *  - error($title, $message) outputs the following JSON:
   *    {
   *      Error: { Title: '$title', Message: '$message/$title' }
   *    }
   *
   * or you can give an array to be directly outputted as JSON:
   *  - error(array('error' => 'message'))
   *    {
   *      error: 'message'
   *    }
   *
   * @param     string|array $title
   * @param     string|null $message
   * @param     boolean $fastcgi_finish_request
   *
   * @return    sfView::NONE
   */
  protected function error($title, $message = null, $fastcgi_finish_request = false)
  {
    $this->getResponse()->setStatusCode(500);

    if (is_array($title))
    {
      $json = $this->json($title);
    }
    else
    {
      $json = $this->json(array(
        'Error' => array('Title' => $title, 'Message' => $message ?: $title)
      ));
    }

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
