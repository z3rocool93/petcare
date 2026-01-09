<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Agregamos la columna para la ruta de la foto, después del email
            // Usamos 2048 de longitud por si la ruta es muy larga o usas URLs externas después
            $table->string('profile_photo_path', 2048)->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminamos la columna si se hace un rollback
            $table->dropColumn('profile_photo_path');
        });
    }
};
