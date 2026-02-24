<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Aligns the experiments table with the new design:
     * - path (exp_id) is the primary identifier, NOT NULL
     * - article_doi and data_doi are now nullable (DEFAULT NULL)
     * - section column is removed
     * - The unique index on (article_doi, section, type) is removed
     */
    public function up(): void
    {
        // Drop views that reference the old schema
        DB::statement('DROP VIEW IF EXISTS `experiments_FF`');
        DB::statement('DROP VIEW IF EXISTS `experiments_OP`');
        DB::statement('DROP VIEW IF EXISTS `experiments_OP_data`');

        Schema::table('experiments', function (Blueprint $table) {
            // Make article_doi nullable
            $table->string('article_doi', 255)->nullable()->default(null)->change();
            // Make data_doi nullable
            $table->string('data_doi', 255)->nullable()->default(null)->change();
        });

        // Drop unique index on (article_doi, section, type) if it exists
        $indexes = DB::select("SHOW INDEX FROM `experiments` WHERE Key_name = 'experiments_doi_section_type_path_unique'");
        if (!empty($indexes)) {
            Schema::table('experiments', function (Blueprint $table) {
                $table->dropUnique('experiments_doi_section_type_path_unique');
            });
        }

        // Drop section column if it exists
        if (Schema::hasColumn('experiments', 'section')) {
            Schema::table('experiments', function (Blueprint $table) {
                $table->dropColumn('section');
            });
        }

        // Recreate views without section
        DB::statement("
            CREATE VIEW `experiments_FF` AS
            SELECT `experiments`.`id` AS `id`,
                `experiments`.`article_doi` AS `article_doi`,
                `experiments`.`article_doi` AS `doi`,
                `experiments`.`data_doi` AS `data_doi`,
                `experiments`.`path` AS `path`,
                `experiments`.`data` AS `data`
            FROM `experiments`
            WHERE (`experiments`.`type` = 'FF')
        ");

        DB::statement("
            CREATE VIEW `experiments_OP` AS
            SELECT `experiments`.`id` AS `id`,
                `experiments`.`article_doi` AS `article_doi`,
                `experiments`.`article_doi` AS `doi`,
                `experiments`.`path` AS `path`
            FROM `experiments`
            WHERE (`experiments`.`type` = 'OP')
        ");

        DB::statement("
            CREATE VIEW `experiments_OP_data` AS
            SELECT `experiments`.`id` AS `experiment_id`,
                `lipids`.`id` AS `lipid_id`,
                `experiments`.`article_doi` AS `doi`,
                `experiments`.`path` AS `path`,
                `lipids`.`name` AS `lipid_name`,
                `lipids`.`molecule` AS `lipid_molecule`,
                `experiments_membrane_composition`.`data` AS `OP_data`
            FROM `experiments`
            LEFT JOIN `experiments_membrane_composition` ON `experiments`.`id` = `experiments_membrane_composition`.`experiment_id`
            LEFT JOIN `lipids` ON `experiments_membrane_composition`.`lipid_id` = `lipids`.`id`
            WHERE `experiments`.`type` = 'OP'
              AND `experiments_membrane_composition`.`data` IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     *
     * NOTE: This rollback is best-effort only. Deriving the original section values
     * from the path column is not guaranteed to be accurate, and re-adding the unique
     * constraint may fail if there are conflicts. The NOT NULL constraint on article_doi
     * and data_doi cannot be safely restored if any records have NULL values.
     */
    public function down(): void
    {
        // Drop the updated views
        DB::statement('DROP VIEW IF EXISTS `experiments_FF`');
        DB::statement('DROP VIEW IF EXISTS `experiments_OP`');
        DB::statement('DROP VIEW IF EXISTS `experiments_OP_data`');

        // Restore section column with values derived from path
        if (!Schema::hasColumn('experiments', 'section')) {
            Schema::table('experiments', function (Blueprint $table) {
                $table->bigInteger('section')->default(1);
            });
            // Attempt to populate section from the last path segment
            DB::statement("UPDATE `experiments` SET `section` = CAST(SUBSTRING_INDEX(`path`, '/', -1) AS UNSIGNED)");
        }

        // Restore original views
        DB::statement("
            CREATE VIEW `experiments_FF` AS
            SELECT `experiments`.`id` AS `id`,
                `experiments`.`article_doi` AS `article_doi`,
                `experiments`.`article_doi` AS `doi`,
                `experiments`.`data_doi` AS `data_doi`,
                `experiments`.`path` AS `path`,
                `experiments`.`section` AS `section`,
                `experiments`.`data` AS `data`
            FROM `experiments`
            WHERE (`experiments`.`type` = 'FF')
        ");

        DB::statement("
            CREATE VIEW `experiments_OP` AS
            SELECT `experiments`.`id` AS `id`,
                `experiments`.`article_doi` AS `article_doi`,
                `experiments`.`article_doi` AS `doi`,
                `experiments`.`path` AS `path`,
                `experiments`.`section` AS `section`
            FROM `experiments`
            WHERE (`experiments`.`type` = 'OP')
        ");

        DB::statement("
            CREATE VIEW `experiments_OP_data` AS
            SELECT `experiments`.`id` AS `experiment_id`,
                `lipids`.`id` AS `lipid_id`,
                `experiments`.`article_doi` AS `doi`,
                `experiments`.`path` AS `path`,
                `lipids`.`name` AS `lipid_name`,
                `lipids`.`molecule` AS `lipid_molecule`,
                `experiments_membrane_composition`.`data` AS `OP_data`
            FROM `experiments`
            LEFT JOIN `experiments_membrane_composition` ON `experiments`.`id` = `experiments_membrane_composition`.`experiment_id`
            LEFT JOIN `lipids` ON `experiments_membrane_composition`.`lipid_id` = `lipids`.`id`
            WHERE `experiments`.`type` = 'OP'
              AND `experiments_membrane_composition`.`data` IS NOT NULL
        ");
    }
};
