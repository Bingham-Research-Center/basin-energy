<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('newsletter_admin_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->index();
            $table->unsignedBigInteger('subscriber_id')->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('broadcast_newsletters', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->text('body');
            $table->date('issue_month')->unique();
            $table->string('status', 24)->default('draft')->index();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('broadcast_newsletter_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('newsletter_id');
            $table->unsignedBigInteger('user_id');
            $table->string('email');
            $table->string('status', 24)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['newsletter_id', 'user_id'], 'newsletter_recipient_unique');
            $table->index(['newsletter_id', 'status'], 'newsletter_delivery_status');
            $table->foreign('newsletter_id')->references('id')->on('broadcast_newsletters')->onDelete('cascade');
        });

        Schema::create('broadcast_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->text('body');
            $table->string('audience', 16);
            $table->json('role_ids')->nullable();
            $table->timestamp('published_at');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('broadcast_announcement_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('announcement_id');
            $table->unsignedBigInteger('user_id');
            $table->string('email');
            $table->string('status', 24)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['announcement_id', 'user_id'], 'announcement_recipient_unique');
            $table->index(['announcement_id', 'status'], 'announcement_delivery_status');
            $table->foreign('announcement_id')->references('id')->on('broadcast_announcements')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_announcement_deliveries');
        Schema::dropIfExists('broadcast_announcements');
        Schema::dropIfExists('broadcast_newsletter_deliveries');
        Schema::dropIfExists('broadcast_newsletters');
        Schema::dropIfExists('newsletter_admin_alerts');
        Schema::dropIfExists('newsletter_subscriptions');
    }
};
