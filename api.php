<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Обработка OPTIONS запроса для CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// JSONBin конфигурация
$JSONBIN_API_KEY = '$2a$10$yVklLNiv63QjxQc8Ucu85OHvkWET7OuBDBh2l2bYINIJS1.NXiOm.';
$JSONBIN_BIN_ID = '699eeaa3ae596e708f4885b3';

// Функция для загрузки пользователей из JSONBin
function loadUsers($apiKey, $binId) {
    $url = "https://api.jsonbin.io/v3/b/{$binId}/latest";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "X-Master-Key: {$apiKey}\r\n"
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return [];
    }
    
    $data = json_decode($response, true);
    return $data['record'] ?? [];
}

// Функция для сохранения пользователей в JSONBin
function saveUsers($users, $apiKey, $binId) {
    $url = "https://api.jsonbin.io/v3/b/{$binId}";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'PUT',
            'header' => "Content-Type: application/json\r\nX-Master-Key: {$apiKey}\r\n",
            'content' => json_encode($users)
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    return $response !== false;
}

// Функция проверки пользователя
function checkUser($username, $password, $apiKey, $binId) {
    $users = loadUsers($apiKey, $binId);
    
    // Ищем пользователя
    $user = null;
    foreach ($users as $u) {
        if (($u['username'] === $username || $u['email'] === $username) && $u['password'] === $password) {
            $user = $u;
            break;
        }
    }
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Invalid credentials'
        ];
    }
    
    // Проверяем подписку
    $subscriptionActive = false;
    $subscriptionEnd = null;
    
    if (isset($user['subscription_end']) && $user['subscription_end']) {
        $endDate = new DateTime($user['subscription_end']);
        $now = new DateTime();
        $subscriptionActive = $endDate > $now;
        $subscriptionEnd = $user['subscription_end'];
    } elseif (isset($user['subscription']) && $user['subscription'] === 'active') {
        $subscriptionActive = true;
    }
    
    return [
        'success' => true,
        'user' => [
            'uid' => $user['uid'] ?? 0,
            'username' => $user['username'],
            'email' => $user['email'],
            'subscription' => [
                'active' => $subscriptionActive,
                'end_date' => $subscriptionEnd,
                'status' => $subscriptionActive ? 'active' : 'inactive'
            ],
            'hwid' => $user['hwid'] ?? '',
            'role' => $user['role'] ?? 'user',
            'registration_date' => $user['registrationDate'] ?? $user['created_at'] ?? null
        ]
    ];
}

// Функция обновления HWID
function updateHWID($username, $hwid, $apiKey, $binId) {
    $users = loadUsers($apiKey, $binId);
    
    // Находим пользователя
    $userIndex = -1;
    for ($i = 0; $i < count($users); $i++) {
        if ($users[$i]['username'] === $username) {
            $userIndex = $i;
            break;
        }
    }
    
    if ($userIndex === -1) {
        return [
            'success' => false,
            'message' => 'User not found'
        ];
    }
    
    // Обновляем HWID
    $users[$userIndex]['hwid'] = $hwid;
    
    // Сохраняем
    if (saveUsers($users, $apiKey, $binId)) {
        return [
            'success' => true,
            'message' => 'HWID updated successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to update database'
        ];
    }
}

// Обработка запросов
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }
        
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'login':
                $username = $input['username'] ?? '';
                $password = $input['password'] ?? '';
                
                if (empty($username) || empty($password)) {
                    throw new Exception('Username and password required');
                }
                
                $result = checkUser($username, $password, $JSONBIN_API_KEY, $JSONBIN_BIN_ID);
                echo json_encode($result);
                break;
                
            case 'update_hwid':
                $username = $input['username'] ?? '';
                $hwid = $input['hwid'] ?? '';
                
                if (empty($username)) {
                    throw new Exception('Username required');
                }
                
                $result = updateHWID($username, $hwid, $JSONBIN_API_KEY, $JSONBIN_BIN_ID);
                echo json_encode($result);
                break;
                
            default:
                throw new Exception('Unknown action');
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Показываем документацию
        echo json_encode([
            'message' => 'Quiet Client API',
            'version' => '1.0',
            'endpoints' => [
                'POST /api.php' => [
                    'login' => [
                        'action' => 'login',
                        'username' => 'string',
                        'password' => 'string'
                    ],
                    'update_hwid' => [
                        'action' => 'update_hwid',
                        'username' => 'string',
                        'hwid' => 'string'
                    ]
                ]
            ],
            'example' => [
                'url' => 'https://your-site.com/api.php',
                'method' => 'POST',
                'body' => [
                    'action' => 'login',
                    'username' => 'd1ago',
                    'password' => '123456'
                ]
            ]
        ], JSON_PRETTY_PRINT);
    } else {
        throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>