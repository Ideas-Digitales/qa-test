<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use App\Services\PermissionChecker;

class TestController extends Controller
{
    protected $permissionChecker;
    protected $authService;
    

    public function __construct(AuthService $authService) //, PermissionChecker $permissionChecker)
    {
        $this->authService = $authService;
        
        //$this->permissionChecker = $permissionChecker;
    }

    public function checkLoginStatus(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $token = $request->input('token');

        if ($this->authService->isLoggedIn($token)) {
            return response()->json(['message' => 'El usuario está logueado']);
        } else {
            return response()->json(['error' => 'El usuario no está logueado'], 401);
        }
    }


    public function getUserId(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $token = $request->input('token');
        $userId = $this->authService->getUserId($token);

        if ($userId) {
            return response()->json(['user_id' => $userId]);
        } else {
            return response()->json(['error' => 'No se pudo obtener el user_id'], 401);
        }
    }

    public function checkPermission(Request $request)
    {
        $userId = $request->user()->id;
        $action = 'some_action';
        $resource = 'some_resource';

        if (!$this->permissionChecker->isAble($userId, $action, $resource)) {
            return response()->json(['error' => 'No tienes permisos para realizar esta acción'], 403);
        }

        return response()->json(['message' => 'Tienes permisos para realizar esta acción']);
    }
}
