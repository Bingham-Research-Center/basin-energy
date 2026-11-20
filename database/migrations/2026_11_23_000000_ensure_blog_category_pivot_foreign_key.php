<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Covers deployments where the original category migration is already
        // recorded as Ran, but the pivot table was imported or created later.
        \App\Support\BlogCategoryPivotSchema::ensure();
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Refusing to drop blog category pivot FK during rollback: '.
            'the constraint may have existed before this reconciliation migration.'
        );
    }
};
