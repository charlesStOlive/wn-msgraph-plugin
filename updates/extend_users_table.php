<?php namespace Waka\Msgraph\Updates;

use Winter\Storm\Database\Updates\Migration;
use Schema;

class ExtendUsersTable extends Migration
{
    public function up()
    {
        Schema::table('backend_users', function ($table) {
            $table->string('msgraph_id')->nullable();
        });
    }

    public function down()
    {
        if (Schema::hasColumn('backend_users', 'msgraph_id')) {
            Schema::table('backend_users', function ($table) {
                $table->dropColumn('msgraph_id');
            });
        }
    }
}
