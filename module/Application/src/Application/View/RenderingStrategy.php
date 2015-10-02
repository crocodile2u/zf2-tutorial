<?php
/**
 * Created by PhpStorm.
 * User: vbolshov <bolshov@tradetracker.com>
 * Date: 2-10-15
 * Time: 13:50
 */

namespace Application\View;


use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Model\JsonModel;
use Zend\View\Renderer\JsonRenderer;
use Zend\View\ViewEvent;

class RenderingStrategy implements ListenerAggregateInterface
{
    /** @var ServiceManager  */
    private $sm;
    protected $listeners = [];

    /**
     * @param ServiceManager $sm
     */
    public function __construct(ServiceManager $sm = null)
    {
        $this->sm = $sm;
    }

    public function attach(EventManagerInterface $events, $priority = null)
    {
        if (null === $priority) {
            $this->listeners[] = $events->attach('renderer', array($this, 'selectRenderer'));
            $this->listeners[] = $events->attach('response', array($this, 'injectResponse'));
        } else {
            $this->listeners[] = $events->attach('renderer', array($this, 'selectRenderer'), $priority);
            $this->listeners[] = $events->attach('response', array($this, 'injectResponse'), $priority);
        }
    }

    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * @param  ViewEvent $e
     * @return \Zend\View\Renderer\RendererInterface
     */
    public function selectRenderer($e)
    {
        $model = $e->getModel();
        if ($model instanceof JsonModel) {
            return $this->sm->get(JsonRenderer::class);
        }

        return $this->sm->get(BlitzRenderer::class);
    }

    /**
     * @param  \Zend\Mvc\MvcEvent $e The MvcEvent instance
     * @return void
     */
    public function injectResponse($e)
    {
        $renderer = $e->getRenderer();
        $response = $e->getResponse();

        if ($renderer instanceof JsonRenderer) {
            // JSON Renderer; set content-type header
            $headers = $response->getHeaders();
            $headers->addHeaderLine('content-type', 'application/json');
        }

        // Inject the content
        $response->setContent($e->getResult());
    }
}