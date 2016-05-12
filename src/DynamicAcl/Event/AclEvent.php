<?php
namespace DynamicAcl\Event;

use Zend\EventManager\Event;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class AclEvent extends Event implements ServiceLocatorAwareInterface
{
    const DYNAMIC_ACL_EVENT_MENU_CHANGE = 'dynamic.acl.event.menu.change';
    const DYNAMIC_ACL_EVENT_ROLE_CHANGE = 'dynamic.acl.event.role.change';
    const DYNAMIC_ACL_IDENTIFIER        = 'dynamic.acl.identifier';
    use ServiceLocatorAwareTrait;
}
