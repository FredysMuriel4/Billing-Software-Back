<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    protected $model; //Encapsulamos una variable que nos traerá la información del modelo
    public function __construct() {
        $this->model = new User(); //Inicializamos la variable encapsulada, convirtiendola en instancia del modelo User
    }

    public function login(Request $request)
    {
        $credentials = $request->only('nit', 'password'); //seleccionamos solo el nit y el password de la request que llega por la ruta
        try {
            if (! $token = JWTAuth::attempt($credentials)) { //Validamos que las credenciales sean correctas
                return $this->model->respond(400, [], 'invalid_credentials', 'Error de autenticación'); //Si las credenciales son incorrectas, retornamos un error de auutenticación
            }
        } catch (JWTException $e) {
            return $this->model->respond(500, [], 'could_not_create_token', 'Error al iniciar sesión'); //Si ocurre un error durante la ejecución y creación del token de usuario, retornamos el error
        }
        return $this->model->respond(200, $token, '', 'Logueo exitoso'); //retornamos el token, con un status ok y un mensaje para el ususario
    }

    public function logOut(Request $request)
    {
        try {
            $token = $request->token; //Recibimos el token de usuario
            JWTAuth::setToken($token)->invalidate(); //Invalidamos el token del usuario
            return $this->model->respond(200, [],'' , 'Sesión cerrada correctamente'); //Devolvemos el mensaje de que la sesión se cerró correctamente
        } catch (\Exception $e) {
            return $this->model->respond(500, [], $e->getMessage(), 'Error al cerrar sesión'); //Si ocurre un error durante la ejecución y creación del token de usuario, retornamos el error
        }
    }

    public function validateToken(Request $request)
    {
        try {
            return $this->model->respond(200, [],'' , 'Ejecución correcta'); //Devolvemos el mensaje de que la sesión se cerró correctamente
        } catch (\Exception $e) {
            return $this->model->respond(500, [], $e->getMessage(), 'Error durante la ejecución'); //Si ocurre un error durante la ejecución y creación del token de usuario, retornamos el error
        }
    }
}
