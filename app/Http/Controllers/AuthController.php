<?php

namespace App\Http\Controllers;

//use App\Services\AuthService;
use App\Services\AuthService;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;


class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;

    }

    private function descifrarDato($datos)
    {
        return Crypt::decrypt($datos);
    }

    private function cifrarDatos($datos) 
    {
        return Crypt::encrypt($datos);
    }

    private function generateSecretHash($username, $clientId, $clientSecret)
    {
        return base64_encode(hash_hmac('sha256', $username . $clientId, $clientSecret, true));
    }

    private function enmascararEmail(string $email): string
    {
        $partes = explode('@', $email);
        $nombreUsuario = $partes[0];
        $dominio = $partes[1];

        $longitudNombre = strlen($nombreUsuario);
        $nombreEnmascarado = str_repeat('x', $longitudNombre - 3) . substr($nombreUsuario, -3);

        return $nombreEnmascarado . '@' . $dominio;
    }

    private function enmascararTelefono(string $telefono): string
    {
        $longitudTelefono = strlen($telefono);
        return str_repeat('x', $longitudTelefono - 3) . substr($telefono, -3);
    }

    


    public function status(string $rut): JsonResponse
    {
    
        $validatedData = validator(['rut' => $rut], [
            'rut' => 'required|string|max:9'
        ])->validate();
        
        

        $rut = $validatedData['rut']; 

        $usuarios = [
            ['rut' => '111111112', 'activeContract' => true],
            ['rut' => '222222223', 'activeContract' => false],
            ['rut' => '333333334', 'activeContract' => true],
        ];

        $contratoActivo = false;

        foreach ($usuarios as $user) {
            if ($user['rut'] === $rut && $user['activeContract'] === true) {
                $contratoActivo = true;
                break;
            }
        }

        if (!$contratoActivo)  return response()->json(['has_contracts' => false], 400);

        $user_cifrado = [
            'rut'=>$rut,
            'token_expiration' => Carbon::now()->addMinutes(5)->toDateTimeString(),
        ];

        $cifrado = $this->cifrarDatos(json_encode($user_cifrado));
        return response()->json(['has_contracts' => true,'token'=>$cifrado]);
   
       
    }



    public function contracts(Request $request): JsonResponse
    {
    
        $validatedData = validator(['rut' => $request->rut,'token' => $request->header('Authorization')], [
            'rut' => 'required|string|max:9',
            'token' => 'required|string' 
        ])->validate();
        

        $rut = $validatedData['rut']; 
        $token = $validatedData['token']; 

        $tokenCifrado = $token;

        // Descifrar el token
        try{
            $tokenDescifrado = Crypt::decrypt($tokenCifrado);
        }
        catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(['error' => 'Invalid Token'], 401);
        }
     
        // Decodificar los datos JSON del token descifrado

        $user = json_decode($tokenDescifrado, true);
  
        if($user['token_expiration'] < Carbon::now()->toDateTimeString())
        {
            return response()->json(['error' => 'Token Expirado'], 401);
        }
       

        $usuarios = [
            ['rut' => '111111112', 'activeContract' => true, 'email_address'=>'user1@gmail.com','mobile_phone_number'=>'56987654321'],
            ['rut' => '222222223', 'activeContract' => true, 'email_address'=>'user2@gmail.com','mobile_phone_number'=>'56912332122'],
            ['rut' => '333333334', 'activeContract' => true, 'email_address'=>'user3@gmail.com','mobile_phone_number'=>'56943234488'],
        ];


        foreach ($usuarios as $user) {
            if ($user['rut'] === $rut) {
                $user_cifrado = [
                    'email_address'=>$user['email_address'],
                    'mobile_phone_number'=>$user['mobile_phone_number'],
                    'token_expiration' => Carbon::now()->addMinutes(5)->toDateTimeString(),
                ];

                $cifrado = $this->cifrarDatos(json_encode($user_cifrado));
                
                return response()->json(
                    [
                        'email_address'=>$this->enmascararEmail($user['email_address']),
                        'mobile_phone_number'=>$this->enmascararTelefono($user['mobile_phone_number']),
                        'token'=> $cifrado,
                    ]
                ); 
            }
        }

        return response()->json(['message' => 'No results'], 404);

    }

   
    public function sendcode(Request $request): JsonResponse
    {
        $validatedData = validator(['token' => $request->header('Authorization')], [
            'token' => 'required|string|size:400' 
        ])->validate();
        

        $token = $validatedData['token']; 

        $authorizationHeader = $token;

        // if (!$authorizationHeader) { //|| !str_starts_with($authorizationHeader, 'Encrypted ')) {
        //     return response()->json(['error' => 'Token no encontrado o formato inválido'], 401);
        // }

        $tokenCifrado = $authorizationHeader;

        // Descifrar el token
        try{
            $tokenDescifrado = Crypt::decrypt($tokenCifrado);
        }
        catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(['error' => 'Invalid Token'], 401);

        }

     
        // Decodificar los datos JSON del token descifrado
        $datos = json_decode($tokenDescifrado, true);
        

        $user = json_decode($this->descifrarDato($token), true);
  
        if($user['token_expiration'] < Carbon::now()->toDateTimeString())
        {
            return response()->json(['error' => 'Token Expirado'], 401);
        }

        $usuarios = [
            ['rut' => '111111112', 'activeContract' => true, 'email_address'=>'user1@gmail.com','mobile_phone_number'=>'56987654321'],
            ['rut' => '222222223', 'activeContract' => true, 'email_address'=>'user2@gmail.com','mobile_phone_number'=>'56912332122'],
            ['rut' => '333333334', 'activeContract' => true, 'email_address'=>'user3@gmail.com','mobile_phone_number'=>'56943234488'],
        ];

        $code = rand(1000,9999);

        $user['token_expiration'] = Carbon::now()->addMinutes(5)->toDateTimeString();
        $user['code'] = $code;

        $cifrado = $this->cifrarDatos(json_encode($user));
       
        return response()->json(['message' => 'Token valido','token'=>$cifrado,'code'=>$code]);
       

    }



    public function validationcode(Request $request): JsonResponse
    {
        $validatedData = validator(
            ['token' => $request->header('Authorization'),'code' => $request->code],
        [
            'token' => 'required|string',
            'code' => 'required|digits:4'
        ])->validate();
        

        $token = $validatedData['token']; 

        $authorizationHeader = $token;


        $tokenCifrado = $authorizationHeader;

        // Descifrar el token
        try{
            $tokenDescifrado = Crypt::decrypt($tokenCifrado);
        }
        catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(['error' => 'Invalid Token'], 401);

        }

        $code_request = $request->code;

        // Decodificar los datos JSON del token descifrado
        $datos = json_decode($tokenDescifrado, true);
        
        $user = json_decode($this->descifrarDato($token), true);
        
        if($user['token_expiration'] < Carbon::now()->toDateTimeString())
        {
            return response()->json(['error' => 'Token Expirado'], 401);
        }
        if($code_request != $user['code']) return response()->json(['message'=>'invalid code'],401);
        
        //dd($authorizationHeader,$tokenCifrado,$tokenDescifrado,$datos,$code_request);
  
        

        $usuarios = [
            ['rut' => '111111112', 'activeContract' => true, 'email_address'=>'user1@gmail.com','mobile_phone_number'=>'56987654321'],
            ['rut' => '222222223', 'activeContract' => true, 'email_address'=>'user2@gmail.com','mobile_phone_number'=>'56912332122'],
            ['rut' => '333333334', 'activeContract' => true, 'email_address'=>'user3@gmail.com','mobile_phone_number'=>'56943234488'],
        ];

        foreach ($usuarios as $u) {
            if ($u['email_address'] === $user['email_address']) {
                $user_cifrado = [
                    'rut'=>$u['rut'],
                    'email_address'=>$u['email_address'],
                    'mobile_phone_number'=>$u['mobile_phone_number'],
                ];

                $cifrado = $this->cifrarDatos(json_encode($user_cifrado));
                
                return response()->json(['token'=> $cifrado]); 
            }
        }

       
        return response()->json(['message' => 'error'],500);
       

    }    


    public function createpassword(Request $request): JsonResponse
    {
        $validatedData = validator(
            ['token' => $request->header('Authorization'),'password' => $request->password],
        [
            'token'=>'required|string',
            'password' => [
                'required',
                'string',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/',
            ],
           
        ])->validate();
        

        $token = $validatedData['token']; 
        $password = $validatedData['password']; 

        $authorizationHeader = $token;

        $tokenCifrado = $authorizationHeader;

        // Descifrar el token
        try{
            $tokenDescifrado = Crypt::decrypt($tokenCifrado);
        }
        catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(['error' => 'Invalid Token'], 401);

        }



        // Decodificar los datos JSON del token descifrado
        $datos = json_decode($tokenDescifrado, true);
        
        $user = json_decode($this->descifrarDato($token), true);

        $usuarios = [
            ['rut' => '111111112', 'activeContract' => true, 'email_address'=>'user1@gmail.com','mobile_phone_number'=>'56987654321','address'=>'Calle Uno 11 Santiago'],
            ['rut' => '222222223', 'activeContract' => true, 'email_address'=>'user2@gmail.com','mobile_phone_number'=>'56912332122','address'=>'Calle Dos 22 Quilicura'],
            ['rut' => '333333334', 'activeContract' => true, 'email_address'=>'user3@gmail.com','mobile_phone_number'=>'56943234488','address'=>'Calle Tres 33 San Bernardo'],
        ];

        foreach ($usuarios as $u) {
            if ($u['email_address'] === $user['email_address']) {
                $user_cifrado = [
                    'rut'=>$u['rut'],
                    'email_address'=>$u['email_address'],
                    'mobile_phone_number'=>$u['mobile_phone_number'],
                    'address'=>$u['address'],
                ];

                $cifrado = $this->cifrarDatos(json_encode($user_cifrado));
                
                return response()->json(['usuario'=>$user_cifrado,'token'=> $cifrado]); 
            }
        }
        dd($token,$password,$datos);
        
        //dd($authorizationHeader,$tokenCifrado,$tokenDescifrado,$datos,$code_request);
  
       
        return response()->json(['message' => 'usuario creado con exito']);
       

    }    
    

   

   


    

    
}
