<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateOtherGoodsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('other_goods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 64);
            $table->unsignedInteger('useful_num');
            $table->unsignedInteger('lock_num');
            $table->index(['code'], 'idx_code');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('other_goods');
    }
}
