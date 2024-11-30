<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

include('../vars.php');

define('API_KEY', $user_x_api_key);

// Получаем заголовок x-api-key
$headers = getallheaders();
if (!isset($headers['x-api-key']) || $headers['x-api-key'] !== API_KEY) {
    http_response_code(403); // Доступ запрещен
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Обработка запросов
$requestMethod = $_SERVER['REQUEST_METHOD'];

switch ($requestMethod) {
    case 'GET':
        // Получение пользователя по id
        $input = json_decode(file_get_contents('php://input'), true);

        // Экранирование данных для безопасности
        $id = $conn->real_escape_string($input['id']);
        
        // Проверка, передан ли id
        if (!isset($input['id'])) {
            http_response_code(400); // Неверный запрос
            echo json_encode(['error' => 'ID is required']);
            exit();
        }

        // Выполнение запроса
        $sql = "SELECT * FROM user WHERE id = " . intval($input['id']);
        $result = $conn->query($sql);
        
        // Проверка наличия результатов
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            echo json_encode($user);
        } else {
            echo json_encode(['error' => 'User not found']);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        // Экранирование данных для безопасности
        $firstName = $conn->real_escape_string($input['first_name']);
        $secondName = $conn->real_escape_string($input['second_name']);
        $email = $conn->real_escape_string($input['email']);
        $password = $conn->real_escape_string($input['password']);

         // Создание нового пользователя
        
        if (isset($input['first_name']) and isset($input['second_name']) and isset($input['email']) and isset($input['password'])) {

            $sql = "INSERT INTO `user` (`id`, `first_name`, `second_name`, `register_date`, `email`, `password`) VALUES (NULL, '" . $firstName . "', '" . $secondName . "', CURRENT_TIMESTAMP, '" . $email . "', '" . $password . "');";

            if ($conn->query($sql) === TRUE) {    
               $newUser = $conn->insert_id; // Получаем ID нового пользователя
                echo json_encode(['message' => 'User  is added', 'id' => $newUser]);
            } else {
                $newUser = $conn->insert_id; // Получаем ID нового пользователя
                echo json_encode(['message' => 'Something go wrong', 'id' => $newUser]);
            }
        } else {
            echo json_encode(['error' => 'Missing something']);
        }
        break;


    case 'PUT':
    // Обновление информации о пользователе
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['id'])) {
        $id = (int)$input['id']; // Приводим к целому числу

        // Валидация и экранирование входных данных
        $updates = [];
        $params = [];

        if (isset($input['first_name']) && is_string($input['first_name'])) {
            $firstName = $conn->real_escape_string(trim($input['first_name']));
            $updates[] = "`first_name` = ?";
            $params[] = $firstName;
        }
        if (isset($input['second_name']) && is_string($input['second_name'])) {
            $secondName = $conn->real_escape_string(trim($input['second_name']));
            $updates[] = "`second_name` = ?";
            $params[] = $secondName;
        }
        if (isset($input['email']) && filter_var($input['email'])) {
            $email = $conn->real_escape_string(trim($input['email']));
            $updates[] = "`email` = ?";
            $params[] = $email;
        }
        if (isset($input['password']) && is_string($input['password'])) {           
            $password = $conn->real_escape_string(trim($input['password']));
            $updates[] = "`password` = ?";
            $params[] = $password;
        }

        // Если нет параметров для обновления
        if (empty($updates)) {
            echo json_encode(['error' => 'No parameters to update']);
            break;
        }

        // Формируем SQL-запрос
        $sql = "UPDATE `user` SET " . implode(', ', $updates) . " WHERE `id` = ?";
        $stmt = $conn->prepare($sql);

        // Добавляем ID в параметры
        $params[] = $id;

        // Определяем типы параметров
        $types = str_repeat('s', count($params) - 1) . 'i'; // 's' для строк, 'i' для целого числа
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo json_encode(['message' => 'User  updated successfully']);
        } else {
            echo json_encode(['error' => 'Something went wrong', 'details' => $stmt->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['error' => 'Missing user ID']);
    }
    break;


    case 'DELETE':
    // Удаление пользователя
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['id'])) {
        $id = (int)$input['id']; // Приводим к целому числу

        // Формируем SQL-запрос
        $sql = "DELETE FROM `user` WHERE `id` = ?";
        $stmt = $conn->prepare($sql);

        // Связываем параметр
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['message' => 'User  deleted successfully']);
            } else {
                echo json_encode(['error' => 'User  not found']);
            }
        } else {
            echo json_encode(['error' => 'Something went wrong', 'details' => $stmt->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['error' => 'Missing user ID']);
    }
    break;




    default:
        http_response_code(405); // Метод не разрешен
        echo json_encode(['error' => 'Method Not Allowed']);
        break;
}

// Закрытие подключения
$conn->close();
