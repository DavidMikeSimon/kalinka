<?php

namespace AC\Kalinka\Guard;

/**
 * Interface class for Guard classes, which define security policies.
 *
 * Note that whether or not the policies actually apply in any given
 * case is determed by Authorizers; the Guard classes simply make
 * the policies available.
 */
interface GuardInterface
{
    /**
     * Checks if the named policy permits access.
     *
     * @return Boolean
     */
    public function checkPolicy($name, $subject, $object = null);

    /**
     * Checks if a policy list collectively permits access.
     *
     * See BaseGuard for details on policy lists.
     *
     * @return Boolean
     */
    public function checkPolicyList($policies, $subject, $object = null);

    /**
     * Returns a list of the named policies supported by this Guard.
     *
     * @return Array of strings
     */
    public function getPolicies();

    /**
     * Returns a list of possible actions on the guarded resource.
     *
     * @return Array of strings
     */
    public function getActions();
}
