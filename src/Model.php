<?php

namespace Groovey\DB;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Model extends Eloquent
{
    public $timestamps = false;
    public $app;
}
