<?php

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
require 'vendor/autoload.php';

class Utils
{
    protected $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }
    public function jsonResponse($status, $data = [])

    //  public function jsonResponse($status, $data = [], $success = false)
    {
        // Verificar si $status es un array y contiene la clave 'status'
        if (is_array($status) && isset($status['status'])) {
            $statusCode = $status['status'];
        } else {
            // Si $status no es un array o no contiene la clave 'status', usar $status directamente
            $statusCode = $status;
        }

        // Crear la respuesta combinando el estado y los datos
        $response = array_merge(['status' => $statusCode], $data);

        // Establecer el código de estado HTTP
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($response); // Enviar el JSON con el estado incluido
        exit; // Finaliza el script después de enviar la respuesta
    }
    public function validateField($data, $field)
    {
        if (!isset($data[$field]) || trim($data[$field]) === "") {
            return $this->jsonResponse(200, ['success' => false, 'message' => "El campo '$field' es obligatorio"]);
        }
        return $this->jsonResponse(200, ['success' => true, 'message' => "El campo '$field' es válido"]);
    }
    public function validateArrays($data)
    {
        if (!is_array($data) || empty($data)) {
            return ["status" => false, "message" => "El dato proporcionado debe ser un array y no puede estar vacío."];
        }

        $errors = [];
        foreach ($data as $index => $item) {
            if (!is_array($item) || empty($item)) {
                $errors[] = "El elemento en la posición #$index no puede estar vacío.";
                continue;
            }

            foreach ($item as $subKey => $value) {
                if (!isset($value) || (is_string($value) && trim($value) === "")) {
                    $errors[] = "El campo '$subKey' en la posición #$index no puede estar vacío.";
                }
            }
        }

        return !empty($errors)
            ? ["status" => false, "message" => "Error, los campos no pueden estar vacios"]
            : ["status" => true, "message" => "Todos los campos son válidos."];
    }



    public function validateAllFields($data)
    {
        foreach ($data as $key => $value) {
            if (!isset($value) || trim($value) === "") {
                return ["status" => false, "message" => "El campo '$key' es obligatorio"];
            }
        }
        return ["status" => true, "message" => "Todos los campos son válidos"];
    }


    public function dataNow()
    {
        date_default_timezone_set('America/Bogota');
        return date('Y-m-d H:i:s');
    }
    public function dateFetch (){
        date_default_timezone_set('America/Bogota');
        return date('d-m-Y');
    }
    public function validateExactField($data, $field, $quantity)
    {


        if (!isset($data) || strlen($data) != $quantity) {
            return ["status" => false, "message" => "El campo $field debe de tener $quantity caracteres"];
        }
        return ["status" => true, "message" => "El campo '$field' es válido"];
    }
    public function convertirImagen($imagen)
    {
        if (is_array($imagen) && isset($imagen['tmp_name'])) {
            $imagen = $imagen['tmp_name'];
        }
    
        $img = $this->manager->read($imagen); // Leer la imagen correctamente
    
        if ($img->width() > 400 || $img->height() > 400) {
            $img->resize(400, 400, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }
    
        $nombreArchivo = uniqid() . '.webp';
        $rutaCarpeta = __DIR__ . '/../image/'; // Ruta absoluta
        $rutaDestino = $rutaCarpeta . $nombreArchivo;
    
        // ✅ Verificar si la carpeta existe y crearla si no
        if (!file_exists($rutaCarpeta)) {
            mkdir($rutaCarpeta, 0777, true);
        }
    
        $img->save($rutaDestino, 50, 'webp');
    
        return $nombreArchivo;
    }
    
    
}
