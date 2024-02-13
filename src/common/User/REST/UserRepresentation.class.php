<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\User\REST;

use PFUser;

/**
 * @psalm-immutable
 */
class UserRepresentation extends MinimalUserRepresentation
{
    public ?string $email;
    public ?string $status;

    private function __construct(MinimalUserRepresentation $minimal_user_representation, ?string $email, ?string $status)
    {
        foreach (get_object_vars($minimal_user_representation) as $name => $value) {
            $this->$name = $value;
        }

        $this->email  = $email;
        $this->status = $status;
    }

    /**
     * @return UserRepresentation
     */
    public static function build(PFUser $user)
    {
        return new self(parent::build($user), $user->getEmail(), $user->getStatus());
    }
}
