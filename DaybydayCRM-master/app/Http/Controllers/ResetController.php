<?php

namespace App\Http\Controllers;

use App\Services\DatabaseService;
use Illuminate\Http\Request;

class ResetController extends Controller
{
    protected $databaseService;

    public function __construct(DatabaseService $databaseService)
    {
        $this->databaseService = $databaseService;
    }

    public function showResetForm()
    {
        return view('reset');
    }

    public function resetDatabase(Request $request)
    {
        // Call your service to reset the database
        $this->databaseService->resetDatabase();

        // Redirect back to the same page with a success message
        return back()->with('success', 'Database has been reset successfully!');
    }
}