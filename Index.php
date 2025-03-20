<?php
session_start(); 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "", "guestbook");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
        die("Invalid request!");
    }

    $admin_password = "securepassword123";
    if (!isset($_POST['admin_password']) || $_POST['admin_password'] !== $admin_password) {
        die("Invalid admin password!");
    }

    $delete_id = $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        header("Location: index.php");
        exit();
    } else {
        echo "Error deleting message: " . $stmt->error;
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['delete'])) {
    if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
        die("Invalid request!");
    }

    $name = isset($_POST["name"]) ? trim($_POST["name"]) : '';
    $message = isset($_POST["message"]) ? trim($_POST["message"]) : '';

    $name = preg_replace('/[<>&]/', '', htmlspecialchars($name, ENT_QUOTES, 'UTF-8'));
    $message = preg_replace('/[<>&]/', '', htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

    if (empty($name) || empty($message)) {
        echo "Name and message cannot be empty!";
    } elseif (strlen($name) > 100 || strlen($message) > 1000) {
        echo "Name or message too long!";
    } elseif (preg_match('/<script\b[^>]*>(.*?)<\/script>/i', $name . $message)) {
    echo "Script tags are not allowed!";
    } else {
        $stmt = $conn->prepare("INSERT INTO messages (name, message) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $message);
        if ($stmt->execute()) {
            header("Location: index.php"); 
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}


$sql = "SELECT id, name, message, created_at FROM messages ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guestbook</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="stars">
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
    </div>

    <h1>Guestbook</h1>
    <form action="index.php" method="POST">
        <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">
        <label for="name">Name:</label><br>
        <input type="text" id="name" name="name" required><br>
        <label for="message">Message:</label><br>
        <textarea id="message" name="message" required></textarea><br>
        <input type="submit" value="Submit">
    </form>
    <h2>Messages</h2>
    <div id="messages">
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<div class='message'>";
                echo "<strong>" . $row["name"] . "</strong> (" . $row["created_at"] . ")<br>";
                echo $row["message"];
                echo "<form action='index.php' method='POST' style='margin-top: 5px;'>";
                echo "<input type='hidden' name='delete_id' value='" . $row["id"] . "'>";
                echo "<input type='hidden' name='token' value='" . $_SESSION['token'] . "'>";
                echo "<input type='password'name='admin_password' placeholder='Admin Password' style='padding: 5px; border-radius: 4px; margin-right; 5px;'>";
                echo "<input type='submit' name='delete' value='Delete' style='background-color: #ff4444; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;'>";
                echo "</form>";
                echo "</div>";
            }
        } else {
            echo "No messages yet!";
        }
        $conn->close();
        ?>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const messages = document.querySelectorAll(".message"); messages.forEach(message=> {
                    setTimeout(() => {
                        message.style.transition = "opacity 1s ease-out"; message.style.opacity = "0";
                        setTimeout(() => { message.style.display = "none"; }, 1000);
                    }, 10000);
                    });
                });
            </script>
    </div>
</body>
</html>