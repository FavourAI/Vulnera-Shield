<?php
class FileType {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM allowed_file_types ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function getActive() {
        $stmt = $this->pdo->query("SELECT extension FROM allowed_file_types WHERE is_active = 1 ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM allowed_file_types WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO allowed_file_types (extension, mime_type, description, is_active)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['extension'],
            $data['mime_type'],
            $data['description'],
            $data['is_active'] ?? 1
        ]);
    }

    public function update($id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE allowed_file_types 
            SET extension = ?, mime_type = ?, description = ?, is_active = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['extension'],
            $data['mime_type'],
            $data['description'],
            $data['is_active'] ?? 1,
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM allowed_file_types WHERE id = ?");
        return $stmt->execute([$id]);
    }

}