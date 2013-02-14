<?php

use AC\Kalinka\Authorizer\RoleAuthorizer;

class RoleAuthorizerTest extends KalinkaTestCase
{
    protected function getAuth($roles) {
        $auth = new RoleAuthorizer($roles);

        // We aren't using any context objects, so we can just use
        // the BaseContext class, which expects null for subject and
        // object, for all these things
        $auth->registerContexts([
            "comment" => "AC\Kalinka\Context\BaseContext",
            "post" => "AC\Kalinka\Context\BaseContext",
            "system" => "AC\Kalinka\Context\BaseContext",
            "image" => "AC\Kalinka\Context\BaseContext",
        ]);
        $auth->registerActions([
            "comment" => ["read", "write"],
            "post" => ["read", "write"],
            "image" => ["upload"],
            "system" => ["reset"]
        ]);
        $auth->registerRolePolicies([
            RoleAuthorizer::DEFAULT_POLICIES => [
                "comment" => [
                    "read" => "allow"
                ],
                "post" => [
                    "read" => "allow"
                ]
            ],
            "guest" => [],
            "contributor" => [
                "post" => [
                    "write" => "allow"
                ],
                "image" => [
                    "upload" => "allow"
                ]
            ],
            "editor" => [
                RoleAuthorizer::ACTS_AS => "contributor",
                "comment" => [
                    RoleAuthorizer::ALL_ACTIONS => "allow"
                ]
            ],
            "post_only_contributor" => [
                "post" => [
                    RoleAuthorizer::ACTS_AS => "contributor"
                ]
            ],
            "post_write_only_contributor" => [
                "post" => [
                    "write" => [
                        RoleAuthorizer::INCLUDE_POLICIES => "contributor"
                    ]
                ]
            ],
            "comment_editor" => [
                RoleAuthorizer::ACTS_AS => "editor",
                "post" => [
                    "write" => [] // No policies, so deny by default
                ]
            ],
            "admin" => [
                RoleAuthorizer::ALL_CONTEXTS_AND_ACTIONS => "allow"
            ],
            "image_supplier" => [
                "image" => [
                    "upload" => "allow"
                ]
            ],
            "comment_supplier" => [
                "comment" => [
                    "write" => "allow"
                ]
            ],
        ]);

        return $auth;
    }

    public function testGuestPolicies() {
        // Common policies only
        $auth = $this->getAuth("guest");
        $this->assertCallsEqual([$auth, "can"], [self::X1, self::X2], [
            [true,  "read",   "comment"],
            [true,  "read",   "post"],
            [false, "write",  "comment"],
            [false, "write",  "post"],
            [false, "upload", "image"],
            [false, "reset",  "system"],
        ]);
    }

    public function testContributorPolicies() {
        // Adding to common policies
        $auth = $this->getAuth("contributor");
        $this->assertCallsEqual([$auth, "can"], [self::X1, self::X2], [
            [true,  "read",   "comment"],
            [true,  "read",   "post"],
            [false, "write",  "comment"],
            [true,  "write",  "post"],
            [true,  "upload", "image"],
            [false, "reset",  "system"],
        ]);
    }

    public function testEditorPolicies() {
        // Including another role and expanding on it
        $auth = $this->getAuth("editor");
        $this->assertCallsEqual([$auth, "can"], [self::X1, self::X2], [
            [true,  "read",   "comment"],
            [true,  "read",   "post"],
            [true,  "write",  "comment"], // ALL_ACTIONS
            [true,  "write",  "post"], // ALL_ACTIONS
            [true,  "upload", "image"],
            [false, "reset",  "system"],
        ]);
    }

    public function testPostOnlyContributorPolicies() {
        // Including context definition of another role 
        $auth = $this->getAuth("post_only_contributor");
        $this->assertCallsEqual([$auth, "can"], [self::X1, self::X2], [
            [true,  "read",   "comment"],
            [true,  "read",   "post"],
            [false, "write",  "comment"],
            [true,  "write",  "post"],
            [false, "upload", "image"],
            [false, "reset",  "system"],
        ]);
    }

    public function testPostWriteOnlyContributorPolicies() {
        // Including single action definition of another role
        $auth = $this->getAuth("post_write_only_contributor");
        $this->assertCallsEqual([$auth, "can"], [self::X1, self::X2], [
            [true,  "read",   "comment"],
            [true,  "read",   "post"],
            [false, "write",  "comment"],
            [true,  "write",  "post"],
            [false, "upload", "image"],
            [false, "reset",  "system"],
        ]);
    }

    public function testCommentEditorPolicies() {
        $auth = $this->getAuth("comment_editor");
        $this->assertCallsEqual([$auth, "can"], [self::X1, self::X2], [
            [true,  "read",   "comment"],
            [true,  "read",   "post"],
            [true,  "write",  "comment"],
            [false, "write",  "post"], // Force_deny
            [true,  "upload", "image"], // Recursive inclusion
            [false, "reset",  "system"],
        ]);
    }

    public function testAdminPolicies() {
        // Unrestricted access
        $auth = $this->getAuth("admin");
        $this->assertCallsEqual([$auth, "can"], [self::X1, self::X2], [
            [true,  "read",   "comment"],
            [true,  "read",   "post"],
            [true,  "write",  "comment"],
            [true,  "write",  "post"],
            [true,  "upload", "image"],
            [true,  "reset",  "system"],
        ]);
    }

    public function testMultipleRoles() {
        $auth = $this->getAuth(["image_supplier", "comment_supplier"]);
        $this->assertCallsEqual([$auth, "can"], [self::X1, self::X2], [
            [true,  "read",   "comment"],
            [true,  "read",   "post"],
            [true,  "write",  "comment"],
            [false, "write",  "post"],
            [true,  "upload", "image"],
            [false, "reset",  "system"],
        ]);
    }

    public function testAuthAppend() {
        // TODO Make this happen as though I were adding in another tiny role
        $auth = $this->getAuth("guest");
        $auth->appendPolicies([
            "post" => [
                "write" => [
                    RoleAuthorizer::INCLUDE_POLICIES => "contributor"
                ]
            ]
        ]);
        // TODO Assert something here
    }

    // TODO Allow us to remove all/some role-supplied policies for a context/action
}