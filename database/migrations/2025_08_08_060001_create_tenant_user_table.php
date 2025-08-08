<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('tenant_user', function (Blueprint $table): void {
      $table->unsignedBigInteger('tenant_id');
      $table->unsignedBigInteger('user_id');
      $table->primary(['tenant_id', 'user_id']);
      $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
      $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    });

    Schema::table('users', function (Blueprint $table): void {
      $table->unsignedBigInteger('latest_tenant_id')->nullable()->after('remember_token');
      $table->foreign('latest_tenant_id')->references('id')->on('tenants')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::table('users', function (Blueprint $table): void {
      $table->dropForeign(['latest_tenant_id']);
      $table->dropColumn('latest_tenant_id');
    });

    Schema::dropIfExists('tenant_user');
  }
};
