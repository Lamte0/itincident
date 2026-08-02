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
        User::firstOrCreate(['email' => 'admin@dgtcp.ci'], [
            'name' => 'Administrateur Système',
            'password' => Hash::make('password'),
            'role' => 'ADMIN',
            'service' => 'Direction Informatique',
            'matricule' => 'ADM001',
        ]);

        // Chef Service Maintenance
        User::firstOrCreate(['email' => 'chef.maintenance@dgtcp.ci'], [
            'name' => 'Chef Service Maintenance',
            'password' => Hash::make('password'),
            'role' => 'CHEF_SERVICE',
            'service' => 'Service Maintenance',
            'matricule' => 'CSM001',
        ]);

        // Maintenanciers
        User::firstOrCreate(['email' => 'tech.reseau@dgtcp.ci'], [
            'name' => 'Technicien Réseau',
            'password' => Hash::make('password'),
            'role' => 'MAINTENANCIER',
            'service' => 'Service Maintenance',
            'matricule' => 'MTN001',
        ]);

        User::firstOrCreate(['email' => 'tech.hardware@dgtcp.ci'], [
            'name' => 'Technicien Hardware',
            'password' => Hash::make('password'),
            'role' => 'MAINTENANCIER',
            'service' => 'Service Maintenance',
            'matricule' => 'MTN002',
        ]);

        User::firstOrCreate(['email' => 'tech.logiciel@dgtcp.ci'], [
            'name' => 'Technicien Logiciel',
            'password' => Hash::make('password'),
            'role' => 'MAINTENANCIER',
            'service' => 'Service Maintenance',
            'matricule' => 'MTN003',
        ]);

        // Utilisateurs standards
        User::firstOrCreate(['email' => 'jean.dupont@dgtcp.ci'], [
            'name' => 'Jean Dupont',
            'password' => Hash::make('password'),
            'role' => 'UTILISATEUR',
            'service' => 'Service Comptabilité',
            'matricule' => 'USR001',
        ]);

        User::firstOrCreate(['email' => 'marie.kouassi@dgtcp.ci'], [
            'name' => 'Marie Kouassi',
            'password' => Hash::make('password'),
            'role' => 'UTILISATEUR',
            'service' => 'Service Budget',
            'matricule' => 'USR002',
        ]);

        User::firstOrCreate(['email' => 'pierre.konan@dgtcp.ci'], [
            'name' => 'Pierre Konan',
            'password' => Hash::make('password'),
            'role' => 'UTILISATEUR',
            'service' => 'Service Trésorerie',
            'matricule' => 'USR003',
        ]);
    }
}
