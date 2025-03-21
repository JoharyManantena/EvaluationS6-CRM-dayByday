<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportCsvRequest;
use App\Services\ImportCsvService;
use Illuminate\Http\Request;

class ImportCsvController extends Controller
{
    protected $importCsvService;

    public function __construct(ImportCsvService $importCsvService)
    {
        $this->importCsvService = $importCsvService;
    }

    public function showForm()
    {
        return view('import_csv', ['tableName' => 'clients']); // Nom de table par défaut, peut être modifié
    }

    public function import(ImportCsvRequest $request)
    {
        // Récupérer le chemin du fichier et le nom de la table
        $filePath = $request->file('csv_file')->getRealPath();
        $tableName = $request->input('table_name'); // Récupérer le nom de la table depuis le formulaire

        // Appel du service d'importation
        $results = $this->importCsvService->import($filePath, $tableName);

        // Vérification des erreurs
        if (!empty($results['errors'])) {
            return redirect()->back()->withErrors($results['errors']);
        }

        // Affichage du message de succès
        $successMessage = "{$results['success']} enregistrements importés avec succès. {$results['failed']} enregistrements ont échoué.";
        return redirect()->back()->with('success', $successMessage);
    }
    
}
