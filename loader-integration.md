# 🔌 Интеграция лоадера с Quiet Client

## Обзор
Этот документ описывает как подключить ваш лоадер к сайту Quiet Client для проверки подписок, получения UID, HWID и других данных пользователей.

## 🌐 API Endpoints

### Base URL
```
https://your-github-pages-site.com/
```

### Доступные файлы:
- `api.html` - JavaScript API (рекомендуется)
- `api.php` - PHP API (если нужен серверный API)

## 📡 API Методы

### 1. Проверка пользователя (Login)

**Endpoint:** `POST /api.html` или `POST /api.php`

**Параметры:**
```json
{
  "action": "login",
  "username": "d1ago",
  "password": "123456"
}
```

**Ответ при успехе:**
```json
{
  "success": true,
  "user": {
    "uid": 7,
    "username": "d1ago",
    "email": "d1ago@example.com",
    "subscription": {
      "active": true,
      "end_date": "2024-12-31T23:59:59.000Z",
      "status": "active"
    },
    "hwid": "PC-ABC123-DEF456",
    "role": "user",
    "registration_date": "2024-02-25T10:30:00.000Z"
  }
}
```

**Ответ при ошибке:**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

### 2. Обновление HWID

**Endpoint:** `POST /api.html` или `POST /api.php`

**Параметры:**
```json
{
  "action": "update_hwid",
  "username": "d1ago",
  "hwid": "PC-ABC123-DEF456"
}
```

**Ответ:**
```json
{
  "success": true,
  "message": "HWID updated successfully"
}
```

## 💻 Примеры кода для лоадера

### C# (.NET)
```csharp
using System;
using System.Net.Http;
using System.Text;
using System.Threading.Tasks;
using Newtonsoft.Json;

public class QuietAPI
{
    private static readonly HttpClient client = new HttpClient();
    private const string API_URL = "https://your-site.com/api.php";

    public class LoginRequest
    {
        public string action { get; set; } = "login";
        public string username { get; set; }
        public string password { get; set; }
    }

    public class LoginResponse
    {
        public bool success { get; set; }
        public User user { get; set; }
        public string message { get; set; }
    }

    public class User
    {
        public int uid { get; set; }
        public string username { get; set; }
        public string email { get; set; }
        public Subscription subscription { get; set; }
        public string hwid { get; set; }
        public string role { get; set; }
    }

    public class Subscription
    {
        public bool active { get; set; }
        public string end_date { get; set; }
        public string status { get; set; }
    }

    public static async Task<LoginResponse> CheckUser(string username, string password)
    {
        try
        {
            var request = new LoginRequest
            {
                username = username,
                password = password
            };

            var json = JsonConvert.SerializeObject(request);
            var content = new StringContent(json, Encoding.UTF8, "application/json");

            var response = await client.PostAsync(API_URL, content);
            var responseString = await response.Content.ReadAsStringAsync();

            return JsonConvert.DeserializeObject<LoginResponse>(responseString);
        }
        catch (Exception ex)
        {
            return new LoginResponse
            {
                success = false,
                message = "Connection error: " + ex.Message
            };
        }
    }

    public static async Task<bool> UpdateHWID(string username, string hwid)
    {
        try
        {
            var request = new
            {
                action = "update_hwid",
                username = username,
                hwid = hwid
            };

            var json = JsonConvert.SerializeObject(request);
            var content = new StringContent(json, Encoding.UTF8, "application/json");

            var response = await client.PostAsync(API_URL, content);
            var responseString = await response.Content.ReadAsStringAsync();
            var result = JsonConvert.DeserializeObject<dynamic>(responseString);

            return result.success == true;
        }
        catch
        {
            return false;
        }
    }
}

// Использование в лоадере:
public async void LoginUser()
{
    string username = usernameTextBox.Text;
    string password = passwordTextBox.Text;

    var result = await QuietAPI.CheckUser(username, password);

    if (result.success)
    {
        if (result.user.subscription.active)
        {
            // Пользователь имеет активную подписку
            MessageBox.Show($"Добро пожаловать, {result.user.username}!\nUID: {result.user.uid}\nПодписка активна до: {result.user.subscription.end_date}");
            
            // Обновляем HWID если нужно
            string currentHWID = GetComputerHWID();
            if (result.user.hwid != currentHWID)
            {
                await QuietAPI.UpdateHWID(username, currentHWID);
            }
            
            // Запускаем основной функционал лоадера
            StartLoader();
        }
        else
        {
            MessageBox.Show("У вас нет активной подписки!");
        }
    }
    else
    {
        MessageBox.Show("Неверный логин или пароль!");
    }
}
```

### Python
```python
import requests
import json

class QuietAPI:
    API_URL = "https://your-site.com/api.php"
    
    @staticmethod
    def check_user(username, password):
        try:
            data = {
                "action": "login",
                "username": username,
                "password": password
            }
            
            response = requests.post(QuietAPI.API_URL, json=data)
            return response.json()
        except Exception as e:
            return {
                "success": False,
                "message": f"Connection error: {str(e)}"
            }
    
    @staticmethod
    def update_hwid(username, hwid):
        try:
            data = {
                "action": "update_hwid",
                "username": username,
                "hwid": hwid
            }
            
            response = requests.post(QuietAPI.API_URL, json=data)
            result = response.json()
            return result.get("success", False)
        except:
            return False

# Использование:
def login_user(username, password):
    result = QuietAPI.check_user(username, password)
    
    if result["success"]:
        user = result["user"]
        if user["subscription"]["active"]:
            print(f"Добро пожаловать, {user['username']}!")
            print(f"UID: {user['uid']}")
            print(f"Подписка активна до: {user['subscription']['end_date']}")
            return True
        else:
            print("У вас нет активной подписки!")
            return False
    else:
        print("Неверный логин или пароль!")
        return False
```

### JavaScript (Node.js)
```javascript
const axios = require('axios');

class QuietAPI {
    static API_URL = 'https://your-site.com/api.php';
    
    static async checkUser(username, password) {
        try {
            const response = await axios.post(this.API_URL, {
                action: 'login',
                username: username,
                password: password
            });
            
            return response.data;
        } catch (error) {
            return {
                success: false,
                message: 'Connection error: ' + error.message
            };
        }
    }
    
    static async updateHWID(username, hwid) {
        try {
            const response = await axios.post(this.API_URL, {
                action: 'update_hwid',
                username: username,
                hwid: hwid
            });
            
            return response.data.success;
        } catch (error) {
            return false;
        }
    }
}

// Использование:
async function loginUser(username, password) {
    const result = await QuietAPI.checkUser(username, password);
    
    if (result.success) {
        if (result.user.subscription.active) {
            console.log(`Добро пожаловать, ${result.user.username}!`);
            console.log(`UID: ${result.user.uid}`);
            console.log(`Подписка активна до: ${result.user.subscription.end_date}`);
            return true;
        } else {
            console.log('У вас нет активной подписки!');
            return false;
        }
    } else {
        console.log('Неверный логин или пароль!');
        return false;
    }
}
```

## 🔧 Настройка

1. **Загрузите файлы API** на ваш сервер или GitHub Pages
2. **Замените URL** в примерах кода на ваш реальный URL
3. **Тестируйте API** используя `api.html` (откройте в браузере для тестирования)

## 📋 Поля пользователя

| Поле | Тип | Описание |
|------|-----|----------|
| `uid` | number | Уникальный ID пользователя (1, 2, 3...) |
| `username` | string | Имя пользователя |
| `email` | string | Email пользователя |
| `subscription.active` | boolean | Активна ли подписка |
| `subscription.end_date` | string | Дата окончания подписки (ISO format) |
| `subscription.status` | string | Статус: "active" или "inactive" |
| `hwid` | string | HWID компьютера пользователя |
| `role` | string | Роль: "user" или "admin" |
| `registration_date` | string | Дата регистрации |

## 🛡️ Безопасность

- API использует HTTPS для безопасной передачи данных
- Пароли хранятся в зашифрованном виде
- HWID привязка предотвращает использование аккаунта на разных ПК
- Все запросы логируются для мониторинга

## 🧪 Тестирование

Откройте `api.html` в браузере для интерактивного тестирования API.

## 📞 Поддержка

Если у вас есть вопросы по интеграции, обратитесь к администратору сайта.