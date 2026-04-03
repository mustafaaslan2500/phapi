# Cekirdek API - Developer Guide

## Architecture Overview

This is a **custom PHP REST API** built without a traditional framework, using:
- **Manual routing** via `app/Routes/Api.php` with explicit `if/else if` chains
- **Composer autoloading** (PSR-4: `App\` → `app/`)
- **Illuminate/Database** (Eloquent ORM available but rarely used)
- **Raw PDO** for most database operations via `App\Models\Connect`
- **Session-based authentication** (server-side sessions, not JWT)

### Key Architectural Decisions

1. **Dual Database Patterns**: The codebase uses *both* raw PDO (via `Connect::initialize()`) and Eloquent (`Config\Connect::initialize()`). Models like `User`, `Post`, `Item` use **raw PDO** exclusively. Eloquent is configured but underutilized.

2. **No ORM Models**: Models are **static method collections**, not ActiveRecord. Example: `User::getUser($id)` returns `stdClass`, not an Eloquent model.

3. **Manual Routing**: Routes defined in `app/Routes/Api.php` using URI matching. No route parameter extraction (`:id`). Parameters sent via JSON POST body or query strings.

4. **UTF-8 Sanitization**: ALL responses pass through `ApiHelpers::safe_json_response()` which recursively sanitizes strings to prevent `json_encode()` failures with Turkish/invalid UTF-8.

## Project Structure

```
app/
├── Config/Connect.php      # Eloquent setup (rarely used)
├── Controllers/*           # Business logic, session handling
├── Models/Connect.php      # PDO connection factory (PRIMARY)
├── Models/*                # Static methods for data access (raw PDO)
├── Routes/Api.php          # Central routing file
├── Http/Request.php        # JSON body parser (auto-merges $_POST/$_GET)
├── Helpers/
│   ├── ApiHelpers.php      # Response formatting, UTF-8 sanitization
│   ├── Validator.php       # Custom validation (NOT Laravel validator)
│   ├── Lang.php            # Localization loader
│   └── ImageProxy.php      # imgproxy URL signing
└── Lang/tr.php             # Turkish translations
```

## Developer Workflows

### Adding a New Route

1. Edit `app/Routes/Api.php`, add an `else if` block:
   ```php
   } else if ($requestUri === '/your/route' && $requestMethod === 'POST') {
       $controller = new YourController();
       $res = $controller->yourMethod();
   ```

2. Create controller in `app/Controllers/YourController.php` with session check:
   ```php
   public function __construct() {
       if (session_status() !== PHP_SESSION_ACTIVE) {
           session_start();
       }
       $this->locale = $_SESSION['SYSTEM_LANG'] ?? 'tr';
       Lang::setLanguage($this->locale);
       $this->request = new Request();
   }
   ```

3. Return arrays from controller methods (NOT `echo` JSON). The router calls `ApiHelpers::safe_json_response()`.

### Database Access Pattern

```php
// Initialize PDO connection
$pdo = Connect::initialize();

// Prepared statement with named placeholders
$stmt = $pdo->prepare("SELECT * FROM users WHERE u_id = :id");
$stmt->bindParam(':id', $userId, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_OBJ); // Returns stdClass or false
```

**Never use Eloquent models** unless refactoring. All existing models use raw PDO.

### Request Handling

```php
// In controller constructor
$this->request = new Request();

// Get all parameters (auto-merged from JSON body, $_POST, $_GET)
$params = $this->request->all();

// Get specific parameter with default
$userId = $this->request->get('user_id', null);
```

`Request` class parses JSON bodies automatically. No need for `json_decode(file_get_contents('php://input'))`.

### Response Format

Always use `ApiHelpers::show_message()`:
```php
return ApiHelpers::show_message(
    true,                           // status (boolean)
    'Success message',              // message (string)
    ['key' => 'value']              // additional data (optional)
);
```

Output structure:
```json
{
  "status": true,
  "message": "...",
  "key": "value"
}
```

### Session-Based Authentication

Check authentication in controllers:
```php
if (empty($_SESSION['user_id'])) {
    return ApiHelpers::show_message(false, $this->lang['please_login']);
}
```

Login sets `$_SESSION['user_id']`. Token-based auth also available via `User::getUserWithToken()`.

### Validation

Use custom `Validator` class (NOT Laravel's):
```php
$validator = new Validator($params, [
    'email' => 'required|email|max:250',
    'phone' => 'required|digits:10|starts_with:5',
], $customMessages, $attributes);

if ($validator->fails()) {
    return ApiHelpers::show_message(false, implode(', ', $validator->errors()));
}
```

Supported rules: `required`, `email`, `numeric`, `digits:X`, `max:X`, `min:X`, `regex:pattern`, `starts_with:X`, `array`, `boolean`.

### Localization

```php
// In controller constructor
Lang::setLanguage($this->locale);
$this->lang = Lang::importLang("user_page_lang");

// Use in methods
return ApiHelpers::show_message(false, $this->lang['invalid_email']);
```

All messages defined in `app/Lang/tr.php` under nested arrays by domain (e.g., `user_page_lang`, `post_lang`).

## Critical Conventions

1. **Always use prepared statements** with PDO. Never concatenate user input into SQL.

2. **Session initialization**: Every controller checks `session_status() !== PHP_SESSION_ACTIVE` before `session_start()`.

3. **Turkish character handling**: All responses MUST go through `ApiHelpers::safe_json_response()` to prevent encoding issues. Use `ApiHelpers::utf8_sanitize()` for data processing.

4. **Image serving**: Use `ImageProxy::resize()` for serving images through imgproxy. Example:
   ```php
   ImageProxy::resize($imageUrl, 800, 600, 'fill', 85, 3600, 'webp');
   ```

5. **Error handling**: Models return `false` on failure. Controllers check and return error messages via `ApiHelpers::show_message()`.

6. **No direct echo/print**: Controllers return arrays. The router handles JSON output.

7. **Google OAuth**: Use `GoogleClientHelper::createClientWithCredentials()` for pre-configured Google_Client with SSL settings and .env credentials.

## External Dependencies

- **GeoIP2**: `plugins/geo_lib/GeoLite2-*.mmdb` for IP geolocation
- **imgproxy**: External image processing service (URL signing via `ImageProxy` helper)
- **Redis**: Available via Predis (`predis/predis` installed)
- **PHPMailer**: Email sending (`phpmailer/phpmailer`)

## Environment Variables

Required in `.env`:
- `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_CHARSET`, `DB_DRIVER`
- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`
- `IMGPROXY_BASE_URL`, `IMGPROXY_KEY_HEX`, `IMGPROXY_SALT_HEX`

## Testing & Debugging

- **Performance test endpoint**: `POST /api_speed_test` (see `ApiController::test()`)
- **Error reporting**: Enabled in `UserController` (`error_reporting(E_ALL)`)
- **No unit tests**: Manual testing via API requests

## Common Pitfalls

1. **Don't use Eloquent** unless explicitly refactoring—existing code is PDO-based
2. **Don't forget `session_start()` checks**—avoid "headers already sent" errors
3. **Don't return JSON directly**—let the router call `safe_json_response()`
4. **Don't use Laravel validation**—use the custom `Validator` class
5. **Don't skip UTF-8 sanitization**—Turkish characters will break JSON output
