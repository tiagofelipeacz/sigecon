<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Compartilha as configurações do site com as views públicas
        View::composer(['layouts.site', 'site.*'], function ($view) {
            // Se o controller já passou $site, não sobrescreve
            $data = $view->getData();
            if (array_key_exists('site', $data) && is_array($data['site'])) {
                return;
            }

            // Helper local para transformar qualquer caminho em URL pública
            $publicUrlFromAny = function (?string $p): ?string {
                if (!$p) return null;
                $p = trim(str_replace('\\','/',$p));
                if ($p === '') return null;

                if (Str::startsWith($p, ['http://','https://','data:image'])) return $p;
                if (Str::startsWith($p, ['/storage/','storage/'])) return asset(ltrim($p,'/'));

                $norm = ltrim($p,'/');
                if (Str::startsWith($norm,'public/')) $norm = substr($norm,7);

                if (Storage::disk('public')->exists($norm)) return asset('storage/'.$norm);
                if (file_exists(public_path($p)))               return asset($p);
                if (file_exists(public_path($norm)))            return asset($norm);
                if (file_exists(public_path('storage/'.$norm))) return asset('storage/'.$norm);

                return asset('storage/'.$norm);
            };

            // Detecta a tabela de configurações
            $table = null;
            foreach (['site_settings','settings','configs','configurations'] as $t) {
                if (Schema::hasTable($t)) { $table = $t; break; }
            }

            // Defaults
            $site = [
                'brand'        => 'GestaoConcursos',
                'primary'      => '#0f172a',
                'primary_color'=> '#0f172a',
                'accent'       => '#111827',
                'accent_color' => '#111827',
                'banner_title' => 'Concursos e Processos Seletivos',
                'banner_sub'   => 'Inscreva-se, acompanhe publicações e consulte resultados.',
                'banner_url'   => null,
                'banner_path'  => null,
                'logo_url'     => null,
                'logo_path'    => null,
            ];

            // Carrega do banco se existir
            if ($table) {
                try {
                    $row = Schema::hasColumn($table,'id')
                        ? (DB::table($table)->where('id',1)->first() ?? DB::table($table)->first())
                        : DB::table($table)->first();

                    if ($row) {
                        $site['brand']         = (string)($row->brand ?? $site['brand']);
                        $site['primary_color'] = (string)($row->primary_color ?? $row->primary ?? $site['primary_color']);
                        $site['primary']       = $site['primary_color'];
                        $site['accent_color']  = (string)($row->accent_color ?? $row->accent ?? $site['accent_color']);
                        $site['accent']        = $site['accent_color'];
                        $site['banner_title']  = (string)($row->banner_title ?? $site['banner_title']);
                        $site['banner_sub']    = (string)($row->banner_sub ?? $site['banner_sub']);

                        // Banner: várias chaves possíveis
                        foreach (['banner_url','banner_path','banner','image','hero_image','hero_url'] as $k) {
                            if (!empty($row->{$k})) {
                                $site['banner_path'] = (string)$row->{$k};
                                $site['banner_url']  = $publicUrlFromAny($site['banner_path']);
                                break;
                            }
                        }

                        // Logo: suporta muitas chaves
                        foreach (['logo_url','logo_path','logo','logotipo','logo_image','site_logo','brand_logo','header_logo'] as $k) {
                            if (!empty($row->{$k})) {
                                $site['logo_path'] = (string)$row->{$k};
                                $site['logo_url']  = $publicUrlFromAny($site['logo_path']);
                                break;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // mantém defaults silenciosamente
                }
            }

            $view->with('site', $site);
        });
    }
}
