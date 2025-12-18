<?php
namespace App\Models;

use App\Database\Database;

class LoadDocument
{
    public static function create(array $data): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            INSERT INTO load_documents
            (load_id, uploaded_by_user_id, document_type, file_path, file_extension)
            VALUES
            (:load_id, :user_id, :type, :path, :ext)
        ");

        $stmt->execute([
            'load_id' => $data['load_id'],
            'user_id' => $data['uploaded_by_user_id'],
            'type'    => $data['document_type'],
            'path'    => $data['file_path'],
            'ext'     => $data['file_extension'],
        ]);
    }
}
