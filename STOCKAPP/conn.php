<?php
class connector {
    private $conn;

    public function __construct($host, $user, $pswd, $dbname) {
        // Use mysqli's built-in exception reporting
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        
        try {
            $this->conn = new mysqli($host, $user, $pswd, $dbname);
            $this->conn->set_charset("utf8mb4"); // Important for compatibility
        } catch (Exception $e) {
            // If accessing directly, show full error for debugging
            if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
                header('Content-Type: text/plain');
                die("DB Connection Error: " . $e->getMessage());
            }
            // Otherwise return JSON for the API so the app handles it gracefully
            die(json_encode(['success' => false, 'error' => "DB Connection Failed"]));
        }
    }

    public function query($stmt) {
        return $this->conn->query($stmt);
    }

    public function prepare($stmt) {
        return $this->conn->prepare($stmt);
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Inicializace globálního připojení
$db = new connector("db.r4.websupport.cz", "marekbartosStock", "Pxfcy8ua7drv+", "stock_app");

// Diagnostic: Only show success message if accessing this file directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header('Content-Type: text/plain');
    echo "Successfully connected to the database!";
}
?>