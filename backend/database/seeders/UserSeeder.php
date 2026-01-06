<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        User::create([
            'name' => 'Administrateur Système',
            'email' => 'admin@dgtcp.ci',
            'password' => Hash::make('password'),
            'role' => 'ADMIN',
            'service' => 'Direction Informatique',
            'matricule' => 'ADM001',
        ]);

        // Chef Service Maintenance
        User::create([
            'name' => 'Chef Service Maintenance',
            'email' => 'chef.maintenance@dgtcp.ci',
            'password' => Hash::make('password'),
            'role' => 'CHEF_SERVICE',
            'service' => 'Service Maintenance',
            'matricule' => 'CSM001',
        ]);

        // Maintenanciers
        User::create([
            'name' => 'Technicien Réseau',
            'email' => 'tech.reseau@dgtcp.ci',
            'password' => Hash::make('password'),
            'role' => 'MAINTENANCIER',
            'service' => 'Service Maintenance',
            'matricule' => 'MTN001',
        ]);

        User::create([
            'name' => 'Technicien Hardware',
            'email' => 'tech.hardware@dgtcp.ci',
            'password' => Hash::make('password'),
            'role' => 'MAINTENANCIER',
            'service' => 'Service Maintenance',
            'matricule' => 'MTN002',
        ]);

        User::create([
            'name' => 'Technicien Logiciel',
            'email' => 'tech.logiciel@dgtcp.ci',
            'password' => Hash::make('password'),
            'role' => 'MAINTENANCIER',
            'service' => 'Service Maintenance',
            'matricule' => 'MTN003',
        ]);

        // Utilisateurs standards
        User::create([
            'name' => 'Jean Dupont',
            'email' => 'jean.dupont@dgtcp.ci',
            'password' => Hash::make('password'),
            'role' => 'UTILISATEUR',
            'service' => 'Service Comptabilité',
            'matricule' => 'USR001',
        ]);

        User::create([
            'name' => 'Marie Kouassi',
            'email' => 'marie.kouassi@dgtcp.ci',
            'password' => Hash::make('password'),
            'role' => 'UTILISATEUR',
            'service' => 'Service Budget',
            'matricule' => 'USR002',
        ]);

        User::create([
            'name' => 'Pierre Konan',
            'email' => 'pierre.konan@dgtcp.ci',
            'password' => Hash::make('password'),
            'role' => 'UTILISATEUR',
            'service' => 'Service Trésorerie',
            'matricule' => 'USR003',
        ]);
    }
}
