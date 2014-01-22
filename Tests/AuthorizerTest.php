<?php

use AC\Kalinka\Guard\BaseGuard;
use AC\Kalinka\Authorizer\AuthorizerAbstract;

class MyAuthorizer extends AuthorizerAbstract
{
    public function __construct($subject = null)
    {
        parent::__construct($subject);
        $this->registerGuards([
            "comment" => new BaseGuard,
        ]);
        $this->registerActions([
            "comment" => ["read", "write"]
        ]);
    }

    protected function getPermission($action, $guardType, $guard, $subject, $object)
    {
        if (!($guard instanceof BaseGuard)) {
            throw new RuntimeException;
        }
        return true;
    }
}

class BadValuesAuthorizer extends AuthorizerAbstract
{
    public function __construct($subject = null)
    {
        parent::__construct($subject);
        $this->registerGuards([
            "something" => new BaseGuard,
        ]);
        $this->registerActions([
            "something" => ["read", "write"]
        ]);
    }

    protected function getPermission($action, $resType, $guard, $subject, $object)
    {
        if ($action == "read") {
            // Do nothing
        } else {
            return 3;
        }
    }
}

class AuthorizerTest extends KalinkaTestCase
{
    private $auth;
    protected function setUp()
    {
        $this->auth = new MyAuthorizer();
        $this->badAuth = new BadValuesAuthorizer();
    }

    public function testExplicitAllow()
    {
        $this->assertTrue($this->auth->can("read", "comment"));
    }

    public function testExceptionOnUnknownResourceType()
    {
        $this->setExpectedException(
            "InvalidArgumentException", "Unknown resource type"
        );
        $this->auth->can("write", "something");
    }

    public function testExceptionOnUnknownAction()
    {
        $this->setExpectedException(
            "InvalidArgumentException", "Unknown action"
        );
        $this->auth->can("nom", "comment");
    }

    public function testExceptionOnUnknownBoth()
    {
        $this->setExpectedException(
            "InvalidArgumentException", "Unknown resource type"
        );
        $this->auth->can("nom", "something");
    }

    public function testExceptionOnNullGetPermissionResult()
    {
        $this->setExpectedException(
            "LogicException", "invalid getPermission result"
        );
        $this->badAuth->can("read", "something");
    }

    public function testExceptionOnInvalidGetPermissionResult()
    {
        $this->setExpectedException(
            "LogicException", "invalid getPermission result"
        );
        $this->badAuth->can("write", "something");
    }
}
