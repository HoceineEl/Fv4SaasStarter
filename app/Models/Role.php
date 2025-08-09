<?php

namespace App\Models;

use App\Models\Concerns\SaasModel;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use SaasModel;
}
