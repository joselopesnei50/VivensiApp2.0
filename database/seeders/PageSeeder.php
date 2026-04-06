<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $pages = [
            [
                'slug' => 'termos',
                'title' => 'Termos de Uso',
                'content' => '<h1>Termos de Uso</h1><p>Bem-vindo ao Vivensi. Estes são os termos de uso padrão. Edite este conteúdo no painel administrativo.</p>',
            ],
            [
                'slug' => 'privacidade',
                'title' => 'Política de Privacidade',
                'content' => '<h1>Política de Privacidade</h1><p>Sua privacidade é importante para nós. Edite este conteúdo no painel administrativo.</p>',
            ],
            [
                'slug' => 'sobre',
                'title' => 'Sobre Nós',
                'content' => '<h1>Sobre a Vivensi</h1><p>A Vivensi é uma plataforma de gestão financeira. Edite este conteúdo no painel administrativo.</p>',
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }
}
