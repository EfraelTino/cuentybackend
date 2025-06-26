<?php
// require_once '/app/Models/User.php';
require_once './app/Models/User.php';
require_once './config/Utils.php';

use Firebase\JWT\JWT;

class UserController
{
    private $userModel;
    private $utils;

    public function __construct($db)
    {
        $this->userModel = new User($db);
        $this->utils = new Utils();
    }
    private function validarCampos($data)
    {
        // Verifica si la clave 'body' existe en $data y contiene los campos necesarios
        return isset($data['name'], $data['username'], $data['role'], $data['status'], $data['password'], $data['password_repeat']);
    }
    public function generateCode($request)
    {
        $data = $request['body'];
    
        // Validar que se haya enviado un número
        if (empty($data['userid'])) {
            return $this->jsonResponse(200, [
                'success' => false,
                'error' => 'Ingrese un número de teléfono válido'
            ]);
        }
    
        // Tomar el número y anteponerle el 51 si es necesario
        $numero = $data['userid'];
        if (strpos($numero, '51') !== 0) {
            $numero = '51' . $numero;
        }
    
        // Buscar usuario con el número ya normalizado
        $searchUser = $this->userModel->findUserByPhone($numero);
        if (!$searchUser) {
            return $this->jsonResponse(200, [
                'success' => false,
                'error' => 'Únete a Cuenty ahora',
                'empezar' => true
            ]);
        }
    
        // Generar código y enviarlo
        $insertCode = $this->userModel->createCode($numero);
        if (!$insertCode) {
            return $this->jsonResponse(200, [
                'success' => false,
                'error' => 'Usuario no encontrado'
            ]);
        }
    
        $sendcode = $this->sendCode($numero, $insertCode);
        if (!$sendcode['success']) {
            return $this->jsonResponse(200, [
                'success' => false,
                'error' => 'Error al enviar código de verificación, intenta de nuevo',
                'details' => $sendcode['error']
            ]);
        }
    
        return $this->jsonResponse(200, [
            'success' => true,
            'message' => 'Se ha enviado un código de verificación a tu WhatsApp'
        ]);
    }
    
    private function sendCode($phone, $code)
    {
        $webhookUrl = "https://webhookene.easyautomates.com/webhook/send-code-cuenty";
    
        $payload = json_encode([
            'userid' => $phone,
            'code' => $code
        ]);
    
        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);
    
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);
    
        if ($curlError) {
            error_log("Error al enviar a webhook n8n: " . $curlError);
            return ['success' => false, 'error' => $curlError];
        }
    
        return ['success' => true, 'response' => $response];
    }
    public function verifyCode($request)
    {
        $data = $request['body'];
        $telefono = trim($data['userid'] ?? '');
        $telefono = '51' . ltrim($telefono, '0');
    
        $code = trim(strval($data['code'] ?? ''));
    
        if (empty($telefono)) {
            return $this->jsonResponse(200, [
                'success' => false,
                'error' => 'Ingrese un número de teléfono válido'
            ]);
        }
    
        if (empty($code) || !preg_match('/^\d{6}$/', $code)) {
            return $this->jsonResponse(200, [
                'success' => false,
                'error' => 'Código incorrecto, intenta de nuevo 1'
            ]);
        }
    
        if (!$this->userModel->searchCode($telefono, $code) || 
            !$this->userModel->updateCode($code)) {
            return $this->jsonResponse(200, [
                'success' => false,
                'error' => 'Código incorrecto, intenta de nuevo'. $code.' '. $telefono
            ]);
        }
    
        return $this->jsonResponse(200, [
            'success' => true,
            'message' => 'Bienvenido de nuevo'
        ]);
    }
    

    public function getAccountStatus($request)
    {
        $data = $request['body'];
        $searchStatus = $this->userModel->getAccountStatus($data['userid']);
        if(!$searchStatus){
            return $this->jsonResponse(200, [
                'success' => false,
                'error' => 'Código incorrecto, intenta de nuevo'
            ]);
        }
        return $this->jsonResponse(200, ['success' => true, 'message' => $searchStatus]);

    }
 


    private function jsonResponse($status, $data = [])
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

 
}
