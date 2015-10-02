<?php

namespace Application;

use Application\View\BlitzRenderer;
use Application\View\RenderingStrategy;
use Zend\Mvc\ModuleRouteListener;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Renderer\JsonRenderer;
use Zend\View\Resolver\TemplatePathStack;

class Module
{
    public function onBootstrap($e)
    {
        $e->getApplication()->getServiceManager()->get('translator');
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        $eventManager->attach('render', function($e) {
            /** @var \Zend\Mvc\MvcEvent $e */
            return $this->registerRenderingStrategy($e);
        }, 100);
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                RenderingStrategy::class => function ($sm) {
                    /** @var ServiceManager $sm */
                    return new RenderingStrategy($sm);
                },
                BlitzRenderer::class => function($sm) {
                    /** @var ServiceManager $sm */
                    $ret = new BlitzRenderer();
                    $ret->setResolver($sm->get(TemplatePathStack::class));
                    return $ret;
                },
                JsonRenderer::class => function($sm) {
                    return new JsonRenderer();
                }
            ),
        );
    }

    /**
     * @param  \Zend\Mvc\MvcEvent $e The MvcEvent instance
     * @return void
     */
    private function registerRenderingStrategy($e) {
        $app          = $e->getTarget();
        $locator      = $app->getServiceManager();
        $view         = $locator->get(\Zend\View\View::class);
        $renderingStrategy = $locator->get(RenderingStrategy::class);

        // Attach strategy, which is a listener aggregate, at high priority
        $view->getEventManager()->attach($renderingStrategy, 100);
    }

}
