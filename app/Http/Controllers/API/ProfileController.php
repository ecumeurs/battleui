<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function getProfile(Request $request, string $id)
    {
        
    }

    public function updateProfile(Request $request, string $id)
    {
        
    }

    public function getCharacter(Request $request, string $id, string $characterId)
    {
        
    }
    
    public function getCharacters(Request $request, string $id)
    {
        
    }

    public function rerollCharacter(Request $request, string $id, string $characterId)
    {
        // only at account creation.   
    }

    public function updateCharacter(Request $request, string $id, string $characterId)
    {
        
    }

    public function deleteCharacter(Request $request, string $id, string $characterId)
    {
        
    }
}