<?php

namespace App\Traits;

use Kreait\Firebase\Auth\UserRecord;

trait Firebase
{
    /**
     * Get users by their Firebase UIDs.
     *
     * @param array $uids
     * @return array
     */
    public function getUsers(array $uids): array
    {
        return app('firebase.auth')->getUsers($uids);
    }

    /**
     * Activate a user by their Firebase UID.
     *
     * @param string $uid
     * @return UserRecord
     */
    public function activeUser(string $uid): UserRecord
    {
        return app('firebase.auth')->enableUser($uid);
    }
}
