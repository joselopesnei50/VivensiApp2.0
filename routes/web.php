<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Vivensi 2.0
|--------------------------------------------------------------------------
| Rotas organizadas por domínio de negócio em routes/partials/.
| Não adicione rotas diretamente aqui. Escolha o arquivo correto:
|
|  public.php   → sem autenticação (landing, portal doador, webhooks)
|  auth.php     → login, logout, registro, reset de senha
|  billing.php  → checkout e assinaturas
|  projects.php → projetos, tarefas, transações
|  ngo.php      → módulo ONG (doadores, editais, beneficiários, RH...)
|  whatsapp.php → chat, broadcast, automações, instâncias
|  social.php   → redes sociais, Social AI, inteligência territorial
|  personal.php → módulo pessoal e MEI
|  admin.php    → painel super admin
|  shared.php   → rotas compartilhadas (dashboard, perfil, suporte...)
|--------------------------------------------------------------------------
*/

require __DIR__.'/partials/public.php';
require __DIR__.'/partials/auth.php';
require __DIR__.'/partials/billing.php';
require __DIR__.'/partials/projects.php';
require __DIR__.'/partials/ngo.php';
require __DIR__.'/partials/whatsapp.php';
require __DIR__.'/partials/social.php';
require __DIR__.'/partials/personal.php';
require __DIR__.'/partials/admin.php';
require __DIR__.'/partials/shared.php';
