<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Cria a coluna "snapshot" na inscrição
        if (!Schema::hasColumn('inscricoes', 'valor_inscricao')) {
            Schema::table('inscricoes', function (Blueprint $table) {
                $table->decimal('valor_inscricao', 10, 2)->nullable()->after('status');
            });
        }

        // Backfill seguro (preenche onde estiver nulo)
        $fkInsc = Schema::hasColumn('inscricoes','concurso_id')
            ? 'concurso_id'
            : (Schema::hasColumn('inscricoes','edital_id') ? 'edital_id' : null);

        if ($fkInsc === null) return;

        // Monta COALESCE dinamicamente conforme as colunas existentes
        $cvcParts = [];
        if (Schema::hasTable('concursos_vagas_cargos')) {
            if (Schema::hasColumn('concursos_vagas_cargos','valor_inscricao')) $cvcParts[] = 'cvc.valor_inscricao';
            if (Schema::hasColumn('concursos_vagas_cargos','taxa'))            $cvcParts[] = 'cvc.taxa';
            if (Schema::hasColumn('concursos_vagas_cargos','taxa_inscricao'))  $cvcParts[] = 'cvc.taxa_inscricao';
            if (Schema::hasColumn('concursos_vagas_cargos','valor'))           $cvcParts[] = 'cvc.valor';
        }
        $coalesceCvc = $cvcParts ? ('COALESCE('.implode(',', $cvcParts).')') : 'NULL';

        $concParts = [];
        if (Schema::hasColumn('concursos','valor_inscricao')) $concParts[] = 'cc.valor_inscricao';
        if (Schema::hasColumn('concursos','taxa_inscricao'))  $concParts[] = 'cc.taxa_inscricao';
        if (Schema::hasColumn('concursos','taxa'))            $concParts[] = 'cc.taxa';
        if (Schema::hasColumn('concursos','valor'))           $concParts[] = 'cc.valor';
        $coalesceConc = $concParts ? ('COALESCE('.implode(',', $concParts).')') : 'NULL';

        // Backfill por UPDATE JOIN (MySQL/MariaDB)
        try {
            $sql = "
                UPDATE inscricoes i
                LEFT JOIN cargos cg ON cg.id = i.cargo_id
                LEFT JOIN concursos cc ON cc.id = i.$fkInsc
                ".(Schema::hasTable('concursos_vagas_cargos') ? "
                LEFT JOIN concursos_vagas_cargos cvc
                       ON cvc.concurso_id = i.$fkInsc
                      AND cvc.nome = cg.nome
                " : "")."
                SET i.valor_inscricao = COALESCE($coalesceCvc, $coalesceConc)
                WHERE i.valor_inscricao IS NULL
            ";
            DB::statement($sql);
        } catch (\Throwable $e) {
            // Se o banco não aceitar o UPDATE JOIN (ex.: SQLite), ignoramos o backfill silenciosamente.
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inscricoes', 'valor_inscricao')) {
            Schema::table('inscricoes', function (Blueprint $table) {
                $table->dropColumn('valor_inscricao');
            });
        }
    }
};
