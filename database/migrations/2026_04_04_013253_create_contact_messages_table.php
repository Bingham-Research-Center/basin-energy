<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contact_messages')) {
            \App\Support\LegacyMigrationSchemaInspector::assertCompatible(
                '2026_04_04_013253_create_contact_messages_table', 'contact_messages'
            );

            return;
        }

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->longText('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Refusing rollback: contact_messages may contain historical imported data. '.
            'Back up and remove it only by an explicit administrator operation.'
        );
    }
};