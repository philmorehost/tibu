<?php
/**
 * Custom Database Session Handler
 * Enables Load Balancing by storing sessions in the database instead of local files.
 */

class DatabaseSessionHandler implements SessionHandlerInterface {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function open($savePath, $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read($id): string {
        $stmt = $this->pdo->prepare("SELECT data FROM sessions WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ? $result['data'] : '';
    }

    public function write($id, $data): bool {
        $last_access = time();
        $stmt = $this->pdo->prepare("REPLACE INTO sessions (id, data, last_access) VALUES (?, ?, ?)");
        return $stmt->execute([$id, $data, $last_access]);
    }

    public function destroy($id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function gc($maxlifetime) {
        $old = time() - $maxlifetime;
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE last_access < ?");
        $stmt->execute([$old]);
        return $stmt->rowCount();
    }
}

// Initialize session if not already started
if (isset($pdo)) {
    $handler = new DatabaseSessionHandler($pdo);
    session_set_save_handler($handler, true);
    // Implement persistent sessions: set lifetime to 30 days
    ini_set('session.gc_maxlifetime', 2592000);
    if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
        session_set_cookie_params([
            'lifetime' => 2592000,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        session_set_cookie_params(2592000, '/; samesite=Lax', '', isset($_SERVER['HTTPS']), true);
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
