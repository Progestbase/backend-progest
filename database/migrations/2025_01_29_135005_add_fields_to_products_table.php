<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('unit');       // New field for unit
            $table->string('brand');      // New field for brand
            $table->string('image_url');  // New field for image URL
        });
    }
    
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('unit');
            $table->dropColumn('brand');
            $table->dropColumn('image_url');
        });
    }
    
}
