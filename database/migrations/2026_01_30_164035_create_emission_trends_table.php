<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmissionTrendsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('emission_trends', function (Blueprint $table) {
            $table->id();

            // one row per year in your CSV
            $table->unsignedSmallInteger('year')->unique();
            $table->decimal('basinwide_ch4_emiss_mg_hr', 12, 5)->nullable();
            $table->decimal('oil_million_bbls', 12, 6)->nullable();
            $table->decimal('gas_million_bbleq', 12, 6)->nullable();
            $table->decimal('energy_million_bbleq', 12, 6)->nullable();
            $table->decimal('oil_gas_ratio', 12, 6)->nullable();

            $table->unsignedInteger('winterozone_exceed_num')->nullable();
            $table->unsignedInteger('producing_wells')->nullable();
            $table->unsignedInteger('newwells')->nullable();

            $table->decimal('prodfromhighwells_thousbbleq_mnth', 14, 6)->nullable();
            $table->decimal('prodfromlowwells_thousbbleq_mnth', 14, 6)->nullable();
            $table->decimal('prodfromoilwells_thousbbleq_mnth', 14, 6)->nullable();
            $table->decimal('prodfromgaswells_thousbbleq_mnth', 14, 6)->nullable();
            $table->decimal('pcnt_prodfromhighwells', 12, 6)->nullable();

            $table->unsignedInteger('gaswells')->nullable();
            $table->unsignedInteger('oilwells')->nullable();
            $table->unsignedInteger('new_gaswells')->nullable();
            $table->unsignedInteger('new_oilwells')->nullable();
            $table->unsignedInteger('highprodwells')->nullable();
            $table->unsignedInteger('lowprodwells')->nullable();

            $table->decimal('emissinens_totenergy', 12, 6)->nullable();
            $table->decimal('emissinens_gas', 12, 6)->nullable();
            $table->decimal('emisco2eq20_millnmg', 14, 6)->nullable();

            $table->decimal('utahgasprice_dollpermcf', 12, 6)->nullable();
            $table->decimal('utahcrudeprice_dollperbbl', 12, 6)->nullable();

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('emission_trends');
    }
}
