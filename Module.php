<?php                                                                                                                                                                                <?php
// 2015-03-11 DB: modified to add dynamic menu + ACL builder
// NOTE: DO NOT activate this module!!!
// It's here just to illustrate techniques which could be used to implement a dynamic ACL and menu
namespace DynamicAcl;

use DynamicAcl\Event\AclEvent;
use DynamicAcl\Service\AclService;
use Zend\Mvc\MvcEvent;

class Module_Do_Not_Activate
{

    const DEFAULT_CACHE_KEY = 'default';

    public function getConfig() {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig() {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function onBootStrap(MvcEvent $e)
    {
        $eventManager = $e->getApplication()->getEventManager();
        $eventManager->attach(MvcEvent::EVENT_DISPATCH, array($this, 'dynamicAclMenu'), 99);
        $sharedManager = $eventManager->getSharedManager();
        // 2015-03-11 DB: need to clear menu cache if the the menu changes!
        $sharedManager->attach( '*', AclEvent::DYNAMIC_ACL_EVENT_MENU_CHANGE, [$this, 'clearMenuCache']);
        // 2015-03-11 DB: need to clear ACL cache if the role changes!
        $sharedManager->attach( '*', AclEvent::DYNAMIC_ACL_EVENT_ROLE_CHANGE), [$this, 'clearAclCache']);
    }

    public function dynamicAclMenu(MvcEvent $e)
    {
        // build menu and acl
        session_start();
        $redirect        = TRUE;
        $matches         = $e->getRouteMatch();
        $sm              = $e->getApplication()->getServiceManager();
        $aclService      = $sm->get('dynamic-acl-service');
        $nav             = $aclService->getDefaultMenu();

        // get layout view instance
        $view = $e->getViewModel();

        // build menu and ACL
        $acl = $aclService->getAcl();
        $nav = $aclService->getMenu();

        // get routing info
        $controller = $matches->getParam('controller');
        $action     = $matches->getParam('action');

        // get roles from groups
        $roles = $aclService->getRolesFromGroups();

        // check rights; break if an allow is granted
        foreach ($roles as $group) {
            if ($acl->hasRole($group) && $acl->hasResource($controller) && $acl->isAllowed($group, $controller, $action))
            {
                $redirect = FALSE;
                break;
            }
        }

        // set router as default
        \Zend\Navigation\Page\Mvc::setDefaultRouter($sm->get('Router'));
        $view->setVariable('navigation', $nav);

        if ($redirect) {
            $matches->setParam('controller', 'log-me-in-controller-login');
            $matches->setParam('action', 'index');
        }

    }

    /**
     * Clears menu cache
     * @param AclEvent $e
     */
    public function clearMenuCache(AclEvent $e)
    {
        $aclService= $e->getServiceLocator()->get('dynamic-acl-service');
        $aclService->clearMenuCache();
    }

    /**
     * Clears ACL cache
     * @param AclEvent $e
     */
    public function clearAclCache(AclEvent $e)
    {
        $aclService= $e->getServiceLocator()->get('dynamic-acl-service');
        $aclService->clearAclCache();
    }

    public function getServiceConfig()
    {
        return array(
          'services' => array(
              'dynamic-acl-session-name' => 'MAIN_SESSION',
	          'dynamic-acl-default-menu' => array(),
              'dynamic-acl-cache-config' => array(
                      'adapter' => array(
                          'name' => 'filesystem',
                          'options' => array(
                              'ttl'       => 3600,
                              'cache_dir' => __DIR__ . '/../../data/cache',
                          ),
                      ),
                      'plugins' => array(
                          // Throw exceptions on cache errors
                          'exception_handler' => array('throw_exceptions' => TRUE),
                          'serializer' => array(),
                      ),
              ),
          ),
            'factories' => array(
              'dynamic-acl-roles-for-select' => function ($sm) {
                  $list = array();
                  foreach ($sm->get('roles') as $key => $role) {
                      $list[$key] = $role['label'];
                  }
                  return $list;
              },
              // produces an array of [$key] => array(inheritance)
              'dynamic-acl-roles-inheritance' => function ($sm) {
                  $list = array();
                  foreach ($sm->get('roles') as $key => $role) {
                      $list[$key] = $role['parent'];
                  }
                  return $list;
              },
              'dynamic-acl-cache' => function ($sm) {
                  return \Zend\Cache\StorageFactory::factory($sm->get('dynamic-acl-cache-config'));
              },
              'dynamic-acl-service' => function ($sm) {
                  $service = new DynamicAclService();
                  $service->setGroupsTable($sm->get('GroupsTable'));
                  $service->setNavMenuTable($sm->get('NavMenuTable'));
                  $service->setAclAllowTable($sm->get('AclAllowTable'));
                  $service->setRolesInheritance($sm->get('dynamic-acl-roles-inheritance'));
                  $service->setSession($sm->get('dynamic-acl-session'));
                  $service->setServiceManager($sm);
                  return $service;
              },
              'dynamic-acl-session' => function ($sm) {
                  return new \Zend\Session\Container($sm->get('dynamic-acl-session-name'));
              },
          ),
        );
    }

}
