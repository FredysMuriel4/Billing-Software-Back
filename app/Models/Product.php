<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;

class Product extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'products';
    protected $fillable = [
        'product_description',
        'product_unity_value',
        'product_stack',
    ];

    //************************************************************* LOGICA *************************************************************

    public function respond($status, $data, $error, $message)
    {
        return json_encode(['status' => $status,'data' => $data,'error' => $error,'message' => $message
        ]);
    }

    public function validateRequest($request)
    {
        return Validator::make(
            $request->all(),
            [
                'description' => 'required|string',
                'unity_value' => 'required|numeric',
                'stack' => 'required|numeric',
            ]
        );
    }

    public function getProducts()
    {
        try {
            $products = $this->get(); //Utilizando el metodo GET del ORM eloquent, que contienen los modelos, traemos todas los productos registrados
            return $this->respond(200, $products, '', 'Productos'); //Retornamos la información obtenida con el status ok y el mensaje para ususario
        } catch (\Exception $e) {
            return $this->respond(500, [], $e->getMessage(), 'Error al cargar facturas'); //si ocurre un error durante la ejecución, retornamos el error, con el estado y el mensaje
        }
    }

    public function storeProduct($request)
    {
        $validator = $this->validateRequest($request); //Validamos la información que viene por la request

        if ($validator->fails()) {//Verificamos que no falle la validación
            return $this->respond(500,  $validator->errors(), 'validation error', $validator->errors()->first());//si la validación falla me retorna el error al controlador
        }

        try {
            $product = $this->create([ //creamos un nuevo producto haciendo uso del metodo create del orm eloquent para los modelos
                'product_description' => $request->description, //a product_description le agregamos el valor de description que viene por la request
                'product_unity_value' => $request->unity_value, //a product_unity_value le asignamos el valor de unity_value que viene por la request
                'product_stack' => $request->stack //a stack le asignamos el valor de stack que viene por la request
            ]);
            return $this->respond(200, $product, '', 'Producto creado correctamente'); //retornamos la información, con el estado y el mensaje
        } catch (\Exception $e) {
            return $this->respond(500, [], $e->getMessage(), 'Error al crear producto');//si ocurre un error durante la ejecución, retornamos el error, con el estado y el mensaje
        }
    }
}
