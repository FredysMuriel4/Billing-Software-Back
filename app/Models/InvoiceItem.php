<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;

class InvoiceItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'invoice_items';
    protected $fillable = [
        'invoice_id',
        'product_id',
        'quantity',
        'total'
    ];

    public function getInvoice()
    {
        return $this->belongsTo(InvoiceHeader::class, 'invoice_id');
    }

    public function getProduct()
    {
        return $this->belongsTo(Product::class, 'product_id')->select('id', 'product_description', 'product_unity_value');
    }

    public function getAllProduct()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    //************************************************************* LOGICA *************************************************************

    public function respond($status, $data, $error, $message)
    {
        return [
            'status' => $status, //Código HTTP de la respuesta
            'data' => $data, //Datos solicitados
            'error' => $error, //Error ocurrido
            'message' => $message //Mensaje para el usuario
        ];
    }

    public function validateRequest($request)
    {
        return Validator::make(
            $request->all(),
            [
                'invoice_id' => 'required|numeric|exists:invoice_headers,id',
                'product_id' => 'required|numeric|exists:products,id',
                'quantity' => 'required|numeric',
                'value' => 'required|numeric'
            ]
        );
    }

    public function storeInvoiceDetail($request)
    {
        $validator = $this->validateRequest($request); //Validamos la información que viene por la request

        if ($validator->fails()) {//Verificamos que no falle la validación
            return $this->respond(500,  $validator->errors(), 'validation error', $validator->errors()->first()); //si la validación falla me retorna el error al controlador
        }

        try {

            $invoice_detail = $this->create([ //creamos un nuevo item de factura haciendo uso del metodo create del orm eloquent para los modelos
                'invoice_id' => $request['invoice_id'], //Invoice_id le asignamos el id de la factura general
                'product_id' => $request['product_id'], //product_id le asignamos el id del producto que viene por la request
                'quantity' => $request['quantity'], //quantity le asignamos la cantidad que viene por la request
                'total' => $request['value'] //total le asignamos el valor de value que viene por la request
            ]);

            return $this->respond(200, $invoice_detail, '', 'Detalle de factura creado de forma correcta'); //retornamos la información, con el estado y el mensaje

        } catch (\Exception $e) {
            return $this->respond(500, [], $e->getMessage(), 'Error al crear detalle de factura'); //si ocurre un error durante la ejecución, retornamos el error, con el estado y el mensaje
        }
    }

    public function updateInvoiceDetail($request, $id)
    {
        $validator = $this->validateRequest($request); //validamos la información que viene por la request

        if ($validator->fails()) { //Verificamos que no falle la validacion
            return $this->respond(500,  $validator->errors(), 'validation error', $validator->errors()->first()); //si la validación falla me retorna el error al controlador
        }

        try {
            $store_new_invoice = $this->storeInvoiceDetail($request);//Debido a ésto, idemtificamos que a factura es nueva, procedemos a crearla
            if($store_new_invoice['status'] != 200){ //verificamos que no haya error al crear el item
                return $this->respond(500, [], $store_new_invoice['error'], 'Error al crear detalle de factura'); //si ocurre un error durante la ejecución, retornamos el error, con el estado y el mensaje
            }
            return $this->respond(200, $store_new_invoice, '', 'Detalle de factura actualizado de forma correcta');//retornamos la información, con el estado y el mensaje
        } catch (\Exception $e) {
            return $this->respond(500, [], $e->getMessage(), 'Error al crear detalle de factura'); //si ocurre un error durante la ejecución, retornamos el error, con el estado y el mensaje
        }
    }
}
