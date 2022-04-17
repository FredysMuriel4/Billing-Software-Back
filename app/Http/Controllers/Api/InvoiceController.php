<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InvoiceHeader;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    protected $modInvoiceHeader; //Encapsulamos una variable que nos trae la información del modelo
    public function __construct() {
        $this->modInvoiceHeader = new InvoiceHeader(); //Instanciamos el modelo InvoiceHeader en la variable encapsulada anteriormente
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $response = $this->modInvoiceHeader->getInvoices(); //Accedemos al metodo getInvoices a través de la instancia del modelo InvoideHeader y guardamos la respuesta.json
        return $response; //Retornamos la respuesta.json
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(is_null($request->vat)){
            $request->merge(['vat' => 0]);
        }
        $response = $this->modInvoiceHeader->storeInvoice($request); //Accedemos al metodo storeInvoice a través de la instancia del modelo InvoideHeader, y pasamos como parametro la información que viene por la request. Guardamos la respuesta.json
        return $response; //Retornamos la respuesta.json
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $response = $this->modInvoiceHeader->getInvoiceData($id); //Accedemos al metodo getInvoiceData del a través de la instancia del modelo InvoideHeader, pasando como parametro la id de la factura a buscar y guardamos la respuesta.json
        return $response; //Retornamos la respuesta.json
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $response = $this->modInvoiceHeader->updateInvoice($request, $id); //Accedemos al metodo updateInvoice del a través de la instancia del modelo InvoideHeader, pasando como parametro la id de la factura a actualizar y la información que llega por la request; Posteriormente guardamos la respuesta.json
        return $response; //Retornamos la respuesta.json
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
