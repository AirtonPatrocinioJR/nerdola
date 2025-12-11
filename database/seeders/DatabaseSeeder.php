<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Criar administrador
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@nerdola.com',
            'password' => Hash::make('password'),
            'phone' => '11999999999',
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Criar cliente de teste
        $client = User::create([
            'name' => 'Cliente Teste',
            'email' => 'cliente@nerdola.com',
            'password' => Hash::make('password'),
            'phone' => '11988888888',
            'role' => 'client',
            'is_active' => true,
        ]);

        // Criar carteira para o cliente
        $code = 'NDL' . str_pad($client->id, 6, '0', STR_PAD_LEFT);
        Wallet::create([
            'user_id' => $client->id,
            'code' => $code,
            'balance' => 1000.00, // Saldo inicial para teste
        ]);

        // Criar usuário do sistema (para receber pagamentos via QR Codes gerados pelo admin)
        $systemUser = User::firstOrCreate(
            ['email' => 'system@nerdola.com'],
            [
                'name' => 'Sistema',
                'password' => Hash::make('password'), // Senha aleatória, não será usada
                'phone' => '00000000000',
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // Criar carteira para o sistema se não existir
        if (!$systemUser->wallet) {
            $systemCode = 'NDL' . str_pad($systemUser->id, 6, '0', STR_PAD_LEFT);
            Wallet::create([
                'user_id' => $systemUser->id,
                'code' => $systemCode,
                'balance' => 0.00,
            ]);
        }

        $this->command->info('Usuários criados com sucesso!');
        $this->command->info('Admin: admin@nerdola.com / password');
        $this->command->info('Cliente: cliente@nerdola.com / password');
        $this->command->info('Sistema: system@nerdola.com (carteira criada)');
    }
}

