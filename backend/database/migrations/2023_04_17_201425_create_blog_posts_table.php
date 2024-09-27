<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBlogPostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->string('sub_heading', 500)->nullable();
            $table->string('slug', 100)->unique();
            $table->text('content');
            $table->string('image', 100);
            $table->enum('category', ['sales', 'marketing', 'operations']);
            $table->boolean('is_published')->default(false);
            $table->string('meta_title', 100);
            $table->string('meta_description', 300);
            $table->string('meta_image_alt', 100);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('blog_posts');
    }
}
