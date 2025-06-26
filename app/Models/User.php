<?php
require_once './config/Utils.php';

class User
{
    private $db;
    private $utils;

    public function __construct($db)
    {
        $this->db = $db;
        $this->utils = new Utils();
    }

    public function findUserByPhone($numero)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE numero = ?");
        $stmt->execute([$numero]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // Solo se llama a fetch una vez
    }
    public function findUserByEmailClient($email)
    {
        $stmt = $this->db->prepare("SELECT id, nombres, email FROM users WHERE email LIKE CONCAT('%', ?, '%')");
        $stmt->execute([$email]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findUserIdActive($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM user_admin WHERE id = ? AND status = 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // Solo se llama a fetch una vez
    }
    public function findUserId($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM user_admin WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // Solo se llama a fetch una vez
    }
    public function findUserByDocument($document)
    {
        $stmt = $this->db->prepare("SELECT id FROM customers WHERE document = ?");
        $stmt->execute([$document]);
        return $stmt->fetchColumn(); // Devuelve solo el ID
    }

    public function createCode($data)
    {
        $codigo = rand(100000, 999999); // Genera un código de 6 dígitos
        $fechaColombia = new DateTime('now', new DateTimeZone('America/Bogota'));
        $creado_en = $fechaColombia->format('Y-m-d H:i:s'); // Formato compatible con PostgreSQL

        $stmt = $this->db->prepare("INSERT INTO verificacion (user_id, codigo, activo, creado_en) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data, $codigo, true, $creado_en]);

        return $codigo; // o return $this->db->lastInsertId();
    }

    public function searchCode($userid, $code)
    {
        $query = "SELECT 1 FROM verificacion 
                  WHERE user_id = :userid 
                  AND codigo = :codigo 
                  AND creado_en >= (CURRENT_TIMESTAMP AT TIME ZONE 'America/Bogota') - INTERVAL '20 minutes'
                  AND activo = true
                  LIMIT 1";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':userid' => $userid,
            ':codigo' => $code
        ]);

        $exists = $stmt->fetchColumn();

        return $exists ? true : false;
    }

    public function updateCode($code)
    {
        $query = "UPDATE verificacion
                  SET activo = false
                  WHERE codigo = :code";

        $stmt = $this->db->prepare($query);
        return $stmt->execute([':code' => $code]);
        return $stmt->rowCount() > 0;
    }
    public function getAccountStatus($userid)
    {
        // 1. Obtener datos del usuario (ya validado previamente)
        $stmtUser = $this->db->prepare("SELECT * FROM users WHERE numero = ?");
        $stmtUser->execute([$userid]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        // 2. Obtener transacciones del usuario (pueden no existir)
        $stmtTx = $this->db->prepare("SELECT * FROM transacciones WHERE userid = ?");
        $stmtTx->execute([$user['id']]); // o $userid si así está en tu tabla
        $transacciones = $stmtTx->fetchAll(PDO::FETCH_ASSOC);

        // 3. Devolver todo, incluso si transacciones está vacío
        return [
            'transacciones' => $transacciones
        ];
    }
}
