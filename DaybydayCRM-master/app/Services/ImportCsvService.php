<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

class ImportCsvService
{
    public function import(string $filePath, string $tableName): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        if (!file_exists($filePath) || !is_readable($filePath)) {
            $results['errors'][] = "Le fichier n'existe pas ou n'est pas lisible.";
            return $results;
        }

        $file = fopen($filePath, 'r');
        if ($file === false) {
            $results['errors'][] = "Impossible d'ouvrir le fichier.";
            return $results;
        }

        $header = fgetcsv($file);
        if ($header === false) {
            $results['errors'][] = "Le fichier CSV est vide ou l'en-tête est manquant.";
            fclose($file);
            return $results;
        }

        // Vérifier les doublons dans l'en-tête
        if (count($header) !== count(array_unique($header))) {
            $results['errors'][] = "L'en-tête CSV contient des colonnes en double.";
            fclose($file);
            return $results;
        }

        $columns = Schema::getColumnListing($tableName);
        if (empty($columns)) {
            $results['errors'][] = "La table '$tableName' n'existe pas ou n'a pas de colonnes.";
            fclose($file);
            return $results;
        }

        // Générer les règles de validation une seule fois
        $validationRules = $this->generateValidationRules($tableName);

        $batchSize = 100;
        $dataToInsert = [];
        $rowNumber = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($file)) !== false) {
                $rowNumber++;

                if (count($row) !== count($header)) {
                    $results['errors'][] = "Erreur à la ligne {$rowNumber}: Nombre de colonnes incorrect.";
                    $results['failed']++;
                    continue;
                }

                $rowData = array_combine($header, $row);

                $filteredData = array_intersect_key($rowData, array_flip($columns));
                if (isset($filteredData['id'])) {
                    unset($filteredData['id']); // Supprimer l'ID pour éviter les conflits
                }

                // Validation avec les règles pré-générées
                $validator = Validator::make($filteredData, $validationRules);
                if ($validator->fails()) {
                    $results['errors'][] = "Erreur ligne {$rowNumber}: " . implode(', ', $validator->errors()->all());
                    $results['failed']++;
                    continue;
                }

                // Ajout des timestamps
                foreach (['created_at', 'updated_at'] as $timestamp) {
                    if (in_array($timestamp, $columns)) {
                        $filteredData[$timestamp] = now();
                    }
                }

                $dataToInsert[] = $filteredData;

                if (count($dataToInsert) >= $batchSize) {
                    DB::table($tableName)->insert($dataToInsert);
                    $results['success'] += count($dataToInsert);
                    $dataToInsert = [];
                }
            }

            if (!empty($dataToInsert)) {
                DB::table($tableName)->insert($dataToInsert);
                $results['success'] += count($dataToInsert);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = "Erreur d'importation : " . $e->getMessage();
        } finally {
            fclose($file);
        }

        return $results;
    }


    protected function generateValidationRules(string $tableName): array
    {
        $rules = [];
        $databaseName = config('database.connections.mysql.database');

        $columnsInfo = DB::select("
            SELECT COLUMN_NAME, IS_NULLABLE, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
        ", [$databaseName, $tableName]);

        foreach ($columnsInfo as $column) {
            $columnName = $column->COLUMN_NAME;
            $isNullable = $column->IS_NULLABLE === 'YES';
            $dataType = $column->DATA_TYPE;
            $maxLength = $column->CHARACTER_MAXIMUM_LENGTH;

            $columnRules = [];

            // Exclure la colonne 'id' de la règle 'required'
            if ($columnName !== 'id' && !$isNullable) {
                $columnRules[] = 'required';
            } else {
                $columnRules[] = 'nullable';
            }

            // Règles de type
            switch ($dataType) {
                case 'varchar':
                case 'char':
                    $columnRules[] = 'string';
                    if ($maxLength) {
                        $columnRules[] = "max:$maxLength";
                    }
                    break;
                case 'text':
                    $columnRules[] = 'string';
                    break;
                case 'int':
                case 'bigint':
                case 'integer':
                    $columnRules[] = 'integer';
                    break;
                case 'decimal':
                case 'numeric':
                case 'float':
                case 'double':
                    $columnRules[] = 'numeric';
                    break;
                case 'date':
                    $columnRules[] = 'date';
                    break;
                case 'datetime':
                case 'timestamp':
                    $columnRules[] = 'date';
                    break;
                case 'tinyint':
                    $columnRules[] = 'boolean'; // Supposition pour les colonnes TINYINT(1)
                    break;
                default:
                    $columnRules[] = 'string';
                    break;
            }

            $rules[$columnName] = implode('|', $columnRules);
        }

        return $rules;
    }

}