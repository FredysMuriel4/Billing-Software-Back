<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InvoiceHeader extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'invoice_headers';
    protected $fillable = [
        'invoice_number',
        'invoice_date',
        'invoice_time',
        'invoice_transmitter_id',
        'invoice_receiver_id',
        'invoice_value_before_vat',
        'invoice_vat',
        'invoice_total_value'
    ];

    public function scopeInvoiceNumber($query, $value)
    {
        if (!is_null($value)) {
            return $query->where('invoice_number', 'like', '%'.$value.'%');
        }
    }


    public function getTransmitter()
    {
        return $this->belongsTo(User::class, 'invoice_transmitter_id')->select('id','name', 'nit');
    }

    public function getAllTransmitter()
    {
        return $this->belongsTo(User::class, 'invoice_transmitter_id');
    }

    public function getReceiver()
    {
        return $this->belongsTo(User::class, 'invoice_receiver_id')->select('id','name', 'nit');
    }

    public function getAllReceiver()
    {
        return $this->belongsTo(User::class, 'invoice_receiver_id');
    }

    public function getItems()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id')->select('id', 'invoice_id' ,'product_id', 'quantity', 'total');
    }

    public function getAllItems()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    //************************************************************* LOGICA *************************************************************

    public function respond($status, $data, $error, $message)
    {
        return json_encode([
            'status' => $status, //Código HTTP de la respuesta
            'data' => $data, //Datos solicitados
            'error' => $error, //Error ocurrido
            'message' => $message //Mensaje para el usuario
        ]);
    }

    public function validateRequest($request)
    {
        return Validator::make(
            $request->all(),
            [
                'transmitter.name' => 'required|string',
                'transmitter.nit' => 'required|string',
                'receiver.name' => 'required|string',
                'receiver.nit' => 'required|string',
                'total_before_vat' => 'required|numeric',
                'vat' => 'required|numeric',
                'total' => 'required|numeric',
                'items' => 'required|array',
            ]
        );
    }

    public function getInvoices()
    {
        try {
            $invoices = $this->with(['getItems.getProduct', 'getTransmitter', 'getReceiver'])//LLamamos todas las facturas registradas
                ->InvoiceNumber(request('query')) //Filtramos por numero de factura utilizando la función Scope del orm eloquent de laravel para comparar las facturas y obtener la que cumpla con la condición
                ->orderBy('id', (request('order') == 'asc' || request('order')==null) ? 'asc' : 'desc' ) //Ordenamos las facturas de forma como el usuario lo pida (ascendente o descendente), según el valor que venga por la request
                ->get(['id', 'invoice_date' ,'invoice_number', 'invoice_transmitter_id', 'invoice_receiver_id']); //Utilizando el metodo GET del ORM eloquent, que contienen los modelos, traemos todas las facturas con sus respectivos detalles<
            return $this->respond(200, $invoices, '', 'Facturas'); //Retornamos la respuesta
        } catch (\Exception $e) {
            return $this->respond(500, [], $e->getMessage(), 'Error al cargar facturas'); //Si hay algún error retornamos ese error
        }
    }

    public function storeInvoice($request)
    {
        $validator = $this->validateRequest($request); //Validamos la información que viene por la request

        if ($validator->fails()) { //Verificamos que no falle la validación
            return $this->respond(500,  $validator->errors(), 'validation error', $validator->errors()->first()); //si la validación falla me retorna el error al controlador
        }

        try {
            DB::beginTransaction(); //Iniciamos una transacción ya que vamos a insertar en mas de una tabla
                $transmitter = $this->search($request->transmitter, 2); //obtenemos la id del emisor que nos retorna la función search pasando como parametro el array de transmitter, y el rol
                $receiver = $this->search($request->receiver, 3); //obtenemos la id del receptor que nos retorna la función search pasando como parametro el array de receiver, y el rol
                $invoice_number = $this->invoiceNumber($transmitter, $request->transmitter); //Guardamos el numero de factura que se crea en la función invoiceNumber, pasando como parametros la id del emisor y la información del emisor
                $date = Carbon::now('America/Bogota');  //Creamos un nuevo datetime para obtener la fecha y hora actual

                $invoice = $this->create([ //creamos una nueva factura haciendo uso del metodo create del orm eloquent para los modelos
                    'invoice_number' => $invoice_number, //invoice_number le asignamos el numero de factura calculado
                    'invoice_date' => explode(' ', $date)[0], //invoice_date le asignamos la fecha, dividiendo y tomando la posoción 0 de $date ya que este es el que almacena la fecha
                    'invoice_time' => explode(' ', $date)[1], //invoice_time le asignamos la hora, dividiendo y tomando la posoción 1 de $date ya que este es el que almacena la hora
                    'invoice_transmitter_id' => $transmitter, //invoice_transmitter_id le asignamos la id del emisor calculada
                    'invoice_receiver_id' => $receiver, //invoice_receiver_id le asignamos la id del receptor calculada
                    'invoice_value_before_vat' => $request->total_before_vat, //invoice_value_before_vat le asignamos el valor de total_before_vat que viene por la request
                    'invoice_vat' => $request->vat, //invoice_vat le asignamos el valor de vat que viene por la request
                    'invoice_total_value' => $request->total //invoice_total_value le asignamos el valor de total que viene por la request
                ]);

                $invoice_detail = new InvoiceItem(); //Instanciamos el modelo InvoiceItem, que contiene los detalles de cada factura

                foreach ($request->items as $key) { //Como los items de la factura llegan en un array, debemos recorrerlos e ir almacenandolos
                    $key['invoice_id']=$invoice->id; //Al item en la posición key, que es en la que nos encontramos actualmente, le agregamos el id de la factura
                    $response_invoice_detail = $invoice_detail->storeInvoiceDetail(collect($key)); //llamamos la funcion storeInvoiceDetail del modelo InvoiceItem,la cual nos permitirá almacenar el detalle de la factura por posición, pasandole como parametro el request en forma de colección para que no lo tome como array plano
                    if($response_invoice_detail['status'] != 200){ //Consultamos si hay algun error
                        return $response_invoice_detail['message']; //Si hay un error al guardar el detalle, retornamos el mensaje
                        DB::rollBack(); //Hacemos rollback para no guardar la factura ya que hay error en sus detalle
                    }
                }
                DB::commit(); //Si no ocurre ningun error hacemos el commit para que se ejecuten los guardados de forma permantente

                return $this->respond(200, $invoice, '', 'Factura creada de forma correcta'); //retornamos la información, con el estado y el mensaje
        } catch (\Exception $e) {
            return $this->respond(500, [], $e->getMessage(), 'Error al crear factura'); //si ocurre un error durante la ejecución, retornamos el error, con el estado y el mensaje
        }
    }

    public function getInvoiceData($id)
    {
        try {
            $invoice = $this->with(['getAllItems.getAllProduct', 'getAllTransmitter', 'getAllReceiver'])->findOrFail($id); //Implementamos el método findOrFail del ORM eloquent para los modelos, para que busque la factura, y si no la encuentra, diga que no la encontró, trayendo consigo el detalle de la factura a través de la relación entre Factura - Detalle Factura
            return $this->respond(200, $invoice, '', 'Facturas'); //retornamos la información obtenida, con el status y el mensaje
        } catch (\Exception $e) {
            return $this->respond(500, [], $e->getMessage(), 'Error al cargar facturas'); //si ocurre algun error durante la ejecución, retornamos ese error, con el mensaje y el status
        }
    }

    public function updateInvoice($request, $id)
    {
        $validator = $this->validateRequest($request); //Validamos la información que viene por la request

        if ($validator->fails()) { //Verificamos que no falle la validación
            return $this->respond(500,  $validator->errors(), 'validation error', $validator->errors()->first()); //si la validación falla me retorna el error al controlador
        }

        try {
            $invoice = $this->findOrFail($id); //Implementamos el método findOrFail del ORM eloquent para los modelos, para que busque la factura, y si no la encuentra, diga que no la encontró
            DB::beginTransaction(); //Iniciamos una transacción ya que vamos a insertar en mas de una tabla
                $transmitter = $this->search($request->transmitter, 2); //Como el emisor se puede modificar, buscamos su existencia con la función search y la id que retorna se la asignamos a la variable $transmitter
                $receiver = $this->search($request->receiver, 3); //Como el receptor se puede modificar, buscamos su existencia con la función search y la id que retorna se la asignamos a la variable $receiver

                $invoice->update([ //A la facura obtenida cuando buscamos por la id le actualizamos los campos
                    'invoice_transmitter_id' => $transmitter, //Emisor id por si cambia
                    'invoice_receiver_id' => $receiver, //receptor id por si cambia
                    'invoice_value_before_vat' => $request->total_before_vat, //Valor antes del iva por si cambia
                    'invoice_vat' => $request->vat, // IVA por si cambia
                    'invoice_total_value' => $request->total //Total por si cambia
                ]);//SI NO CAMBIAN LOS VALORES, ESTOS PERMANECEN IGUAL

                $invoice_detail = new InvoiceItem(); //Instanciamos el modelo InvoiceItem

                $delete_invoice_items = $invoice_detail->where('invoice_id', $invoice->id)->delete();
                if(!$delete_invoice_items){ //verificamos que no haya error al crear el item
                    return $this->respond(500, [], $delete_invoice_items['error'], 'Error al crear detalle de factura'); //si ocurre un error durante la ejecución, retornamos el error, con el estado y el mensaje
                    DB::rollBack(); //
                }
                foreach ($request->items as $key) { //Como los items de la factura llegan en un array, debemos recorrerlos e ir actualizandolos
                    $key['invoice_id']=$invoice->id; //Al item en la posición key, que es en la que nos encontramos actualmente, le agregamos el id de la factura
                    $response_invoice_detail = $invoice_detail->updateInvoiceDetail(collect($key), $key['id']); //llamamos la funcion updateInvoiceDetail del modelo InvoiceItem,la cual nos permitirá actualizar el detalle de la factura por posición, pasandole como parametro el request en forma de colección para que no lo tome como array plano y adicional, el id del item de factura a actualizar
                    if($response_invoice_detail['status'] != 200){ //Consultamos si hay algun error
                        return $response_invoice_detail['error'];//Si hay un error al acualizar el detalle, retornamos el mensaje
                        DB::rollBack();//Hacemos rollback para no guardar la factura ya que hay error en sus detalle
                    }
                }
            DB::commit();  //Si no ocurre ningun error hacemos el commit para que se ejecuten los guardados de forma permantente
            return $this->respond(200, $invoice, '', 'Factura actualizada exitosamente'); //retornamos la información, con el estado y el mensaje
        } catch (\Exception $e) {
            return $this->respond(500, [], $e->getMessage(), 'Error al actualizar factura'); //si ocurre un error durante la ejecución, retornamos el error, con el estado y el mensaje
        }
    }

    public function search($query, $role)
    {
        $user = User::where('nit', $query['nit'])->where('role_id', $role)->first(); //Buscamos el usuario cuyo nit sea query en la posición nit, ya que query es un array, y el rol sea el pasado como parametro
        if(!$user){ //Si no existe
            $user = User::create([ //Se crea un nuevo usuario
                'name' => $query['name'], //Almacenando nombre
                'nit' => $query['nit'], //nit
                'role_id' => $role //y rol que se pasó por parametro
            ]);
            return $user->id; //Retornamos la id de ese usuario creado
        }
        $user->update([
            'name' => $query['name'] //Actualice nombre por si cambia el nombre del receptor
        ]);
        return $user->id; //Si existe retornamos la id del usuario encontrado
    }

    public function invoiceNumber($query, $request)
    {
        $latest_transmitter_invoice = $this->where('invoice_transmitter_id', $query)->latest()->first(); //Buscamos la ultima factura que tenga registrada el emisor, consultando por la id del receptor recibida
        if (empty($latest_transmitter_invoice)) { //Si está vacío, quiere decir que no hay facturas de ese emisor
            $invoice_number = str_replace(' ','-', $request['name'])."_1"; //Para crear el numero de factura reemplazamos los espacios en el nombre del emisor por un -, y al final le concatenamos _1,ya que es la primera factura de este emisor
            return $invoice_number; //retornamos el numero de factura creado
        }
        //Si ese emisor ya tiene facturas
        $number = explode('_',$latest_transmitter_invoice->invoice_number)[1] + 1; //Dividimos el numero de la ultima factura registrada de ese emisor por _ ya que éste es el que divide el nombre con el numero, y al numero le sumamos 1
        $invoice_number = str_replace(' ', '-',$request['name'])."_".($number); //luego reemplazamos los espacios por - y le concatenamos el _ + el numero calculado
        return $invoice_number; //retornamos el numero de factura creado
    }
}
