<?php

/** @noinspection PhpIllegalPsrClassPathInspection */

/** @noinspection PhpUnused */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use KitLoong\MigrationsGenerator\Tests\TestMigration;

class ExpectedCreateReservedNameWithPrecision_DB_Table extends TestMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reserved_name_with_precision_[db]', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes('deleted_at', 2);
            $table->softDeletesTz('deleted_at_tz', 2);
            $table->string('remember_token', 120);
            $table->timestamps(2);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reserved_name_with_precision_[db]');
    }
}
