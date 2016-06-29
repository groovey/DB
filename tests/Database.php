<?php

use Groovey\ORM\DB;

class Database
{
    public static function create()
    {
        $exist = DB::schema()->hasTable('users');

        if ($exist) {
            DB::schema()->drop('users');
        }

        DB::schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
    }

    public static function drop()
    {
        DB::schema()->drop('users');
    }
}
