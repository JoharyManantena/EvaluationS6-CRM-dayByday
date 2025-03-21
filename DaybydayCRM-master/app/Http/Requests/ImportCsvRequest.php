<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportCsvRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette demande.
     */
    public function authorize(): bool
    {
        return true; // Vous pouvez ajouter une logique d'autorisation ici si nécessaire
    }

    /**
     * Récupère les règles de validation qui s'appliquent à la demande.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'csv_file' => 'required|file|mimes:csv,txt|max:2048', // Taille max de 2 Mo pour le fichier CSV
            'table_name' => 'required|string|max:255|in:absences,activities,appointments,business_hours,clients,comments,contacts,department_user,departments,documents,industries,integrations,invoice_lines,invoices,leads,mails,migrations,notifications,offers,password_resets,payments,permission_role,permissions,products,projects,reset_logs,role_user,roles,settings,statuses,subscriptions,tasks,users', // Vérifier que la table est dans la liste autorisée
        ];
    }


    /**
     * Messages d'erreur personnalisés.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'csv_file.required' => 'Le fichier CSV est requis.',
            'csv_file.file' => 'Le fichier doit être un fichier.',
            'csv_file.mimes' => 'Le fichier doit être au format CSV ou TXT.',
            'csv_file.max' => 'Le fichier ne doit pas dépasser 2 Mo.',
            'table_name.required' => 'Le nom de la table est requis.',
            'table_name.string' => 'Le nom de la table doit être une chaîne de caractères.',
            'table_name.max' => 'Le nom de la table ne doit pas dépasser 255 caractères.',
            'table_name.in' => 'Le nom de la table n\'est pas valide.', // Message d'erreur pour les tables non autorisées
        ];
    }
}
