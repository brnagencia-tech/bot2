<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('tags')) {
            Schema::create('tags', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('color')->nullable();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('contact_tag')) {
            Schema::create('contact_tag', function (Blueprint $table) {
                $table->unsignedBigInteger('contact_id');
                $table->unsignedBigInteger('tag_id');
                $table->unique(['contact_id','tag_id']);
            });
        }

        if (!Schema::hasTable('segments')) {
            Schema::create('segments', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->json('rule_json')->nullable();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('flows')) {
            Schema::create('flows', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->json('json');
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('flow_runs')) {
            Schema::create('flow_runs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('flow_id');
                $table->unsignedBigInteger('contact_id')->nullable();
                $table->enum('status', ['pending','running','done','failed'])->default('pending');
                $table->text('last_error')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('workspace_settings')) {
            Schema::create('workspace_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->string('key');
                $table->json('value')->nullable();
                $table->timestamps();
                $table->unique(['user_id','key']);
            });
        }

        if (!Schema::hasTable('broadcast_jobs')) {
            Schema::create('broadcast_jobs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('segment_id');
                $table->text('text');
                $table->enum('status', ['queued','running','done','failed'])->default('queued');
                $table->unsignedInteger('progress')->default(0);
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('wa_messages')) {
            Schema::create('wa_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('message_id');
                $table->string('wa_id')->nullable();
                $table->string('status')->nullable();
                $table->json('raw')->nullable();
                $table->timestamps();
                $table->index('message_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_messages');
        Schema::dropIfExists('broadcast_jobs');
        Schema::dropIfExists('workspace_settings');
        Schema::dropIfExists('flow_runs');
        Schema::dropIfExists('flows');
        Schema::dropIfExists('segments');
        Schema::dropIfExists('contact_tag');
        Schema::dropIfExists('tags');
    }
};

