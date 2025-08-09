<?php

namespace App\Models;

use App\Models\Concerns\SaasModel;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use SaasModel;
}
