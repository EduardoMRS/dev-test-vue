<?php

namespace Database\Seeders;

use App\Models\NotificationSetting;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ── Admin user ──────────────────────────────────────────────
        User::updateOrCreate(
            ['email' => env('APP_ROOT_USER', 'admin') . '@admin.com'],
            [
                'name'      => env('APP_ROOT_USER', 'admin'),
                'password'  => bcrypt(env('APP_ROOT_PASSWORD', '12345678')),
                'birthdate' => '1990-01-01',
                'sex'       => 'other',
            ]
        )->assignRole('admin');

        // ── Demo users ──────────────────────────────────────────────
        $joao = User::updateOrCreate(
            ['email' => 'joao@teste.com'],
            [
                'name'              => 'João Silva',
                'password'          => bcrypt('12345678'),
                'email_verified_at' => now(),
                'birthdate'         => '1992-05-15',
                'sex'               => 'male',
                'role'              => 'user',
                'active'            => true,
            ]
        );

        $maria = User::updateOrCreate(
            ['email' => 'maria@teste.com'],
            [
                'name'              => 'Maria Santos',
                'password'          => bcrypt('12345678'),
                'email_verified_at' => now(),
                'birthdate'         => '1995-08-22',
                'sex'               => 'female',
                'role'              => 'user',
                'active'            => true,
            ]
        );

        // ── Notification settings ───────────────────────────────────
        foreach ([$joao, $maria] as $user) {
            NotificationSetting::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'days_before'   => 2,
                    'email_enabled' => true,
                    'push_enabled'  => true,
                ]
            );
        }

        // ── Tasks (20 total: 10 per user) ───────────────────────────
        $taskTemplates = [
            ['title' => 'Revisar relatório mensal',        'description' => 'Verificar os números do relatório de vendas e corrigir inconsistências.'],
            ['title' => 'Preparar apresentação',           'description' => 'Criar slides para a reunião semanal de equipe.'],
            ['title' => 'Atualizar documentação da API',   'description' => 'Documentar os novos endpoints adicionados no último sprint.'],
            ['title' => 'Corrigir bug no checkout',        'description' => 'Investigar e resolver o erro 500 na finalização do pedido.'],
            ['title' => 'Configurar CI/CD',                'description' => 'Adicionar pipeline de testes automatizados no GitHub Actions.'],
            ['title' => 'Reunião com cliente',             'description' => 'Discutir requisitos do novo módulo de relatórios.'],
            ['title' => 'Refatorar módulo de pagamento',   'description' => 'Separar lógica de gateway em classes independentes.'],
            ['title' => 'Escrever testes unitários',       'description' => 'Adicionar cobertura de testes para o serviço de notificações.'],
            ['title' => 'Planejar sprint da semana',       'description' => 'Definir prioridades e atribuir tarefas para a equipe.'],
            ['title' => 'Deploy para staging',             'description' => 'Realizar deploy da versão 2.1 no ambiente de homologação.'],
        ];

        $today = Carbon::today();

        foreach ([$joao, $maria] as $user) {
            // Skip if user already has tasks (idempotent)
            if ($user->tasks()->count() >= 10) {
                continue;
            }

            foreach ($taskTemplates as $i => $tpl) {
                $daysOffset = match (true) {
                    $i < 2  => rand(-3, -1),     // overdue
                    $i < 4  => rand(0, 2),        // due very soon
                    $i < 7  => rand(3, 7),        // upcoming week
                    default => rand(8, 21),        // later
                };

                $completed = $i < 2; // first 2 tasks are completed

                Task::create([
                    'user_id'      => $user->id,
                    'title'        => $tpl['title'],
                    'description'  => $tpl['description'],
                    'due_date'     => $today->copy()->addDays($daysOffset),
                    'completed'    => $completed,
                    'completed_at' => $completed ? now() : null,
                    'notify_email' => true,
                    'notify_push'  => $i % 2 === 0, // alternate push notifications
                ]);
            }
        }
    }
}
