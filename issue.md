## Title

Implement User Registration API with Laravel Sanctum SPA Authentication

---

## Description

This issue covers the implementation of the user registration endpoint using Laravel Sanctum in SPA (cookie-based) mode.

When a user submits their registration data, the API must:

1. Validate the incoming request input
2. Create a new user record in the database with a hashed password
3. Automatically log the user in after registration
4. Return a standardized JSON success response

Authentication uses cookie-based sessions managed by Laravel Sanctum. No token is returned in the response body. The browser handles the session automatically.

---

## Scope

This issue covers ONLY the registration endpoint. Login, logout, and route protection are out of scope.

**In scope:**
- `POST /api/auth/register` endpoint
- Form Request validation class (`StoreRegisterRequest`)
- Auth Service method (`registerUser()`)
- User Repository method (`create()`)
- Standardized JSON response via `ApiResponse`
- Automatic login after user creation
- Pest feature test for the registration endpoint

**Out of scope:**
- Login endpoint
- Logout endpoint
- Email verification
- Password reset
- Route protection middleware

---

## Acceptance Criteria

### Endpoint

- [ ] Route `POST /api/auth/register` exists and is publicly accessible (no `auth:sanctum` middleware)
- [ ] Route is defined inside `routes/api.php`
- [ ] Route uses the `AuthController@register` method

### Request Validation

- [ ] The request body must contain: `name`, `email`, `password`, `password_confirmation`
- [ ] `name` is required, string, max 255 characters
- [ ] `email` is required, string, valid email format, max 255 characters, unique in the `users` table
- [ ] `password` is required, string, min 8 characters, confirmed (must match `password_confirmation`)
- [ ] If validation fails, a `422 Unprocessable Entity` response is returned automatically by Laravel

### Business Logic

- [ ] Password is stored hashed (the `User` model already casts `password` to `hashed`)
- [ ] After the user is created, the user is immediately logged in using `Auth::login($user)`
- [ ] No token is returned in the response body

### Response

- [ ] On success, return HTTP `201 Created`
- [ ] Response body must follow this exact structure:
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "created_at": "...",
      "updated_at": "..."
    },
    "message": "Registration successful"
  }
  ```
- [ ] The `password` and `remember_token` fields must NOT appear in the response (already hidden in the `User` model)

### Tests

- [ ] A Pest feature test exists at `tests/Feature/Auth/RegisterTest.php`
- [ ] The test covers the happy path: valid input returns `201` with correct response structure
- [ ] The test covers validation failure: missing `name` returns `422`
- [ ] The test covers validation failure: duplicate `email` returns `422`
- [ ] The test covers validation failure: `password` and `password_confirmation` do not match returns `422`
- [ ] All tests pass with `php artisan test --compact`

---

## Technical Notes

### Architecture

This project follows a strict layered architecture:

```
Controller → Service → Repository → Database
```

Each layer has a single responsibility:

| Layer | Responsibility |
|---|---|
| `Controller` | Receive HTTP request, call Service, return JSON response |
| `Service` | Business logic only (hash check, login call, etc.) |
| `Repository` | Database access only (Eloquent calls) |
| `FormRequest` | Input structure validation only |

### Sanctum SPA Authentication

- Authentication uses **cookie-based sessions**, not tokens
- Do NOT return a token in the response
- After creating the user, call `Auth::login($user)` to establish the session
- Do NOT call `Auth::attempt()` in the register flow — use `Auth::login($user)` directly after creation
- Do NOT add `auth:sanctum` middleware to the registration route

### ApiResponse Helper

Use the existing `ApiResponse` helper class for all responses:

```
ApiResponse::success($data, $message, $statusCode)
```

The success response for registration must use HTTP status code `201`.

### Password Hashing

The `User` model already has `password` cast to `hashed` via:

```php
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
```

and:

```php
protected function casts(): array
{
    return [
        'password' => 'hashed',
    ];
}
```

You do NOT need to manually call `bcrypt()` or `Hash::make()`. Pass the plain-text password from validated data directly. Laravel will hash it automatically.

### Validation Naming Convention

Use array-based rules (not string-based):

```php
'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
```

### Error Handling

- Validation errors are handled automatically by Laravel's Form Request (returns `422`)
- Do NOT wrap the controller method in a try/catch block
- Any other exceptions bubble up and are handled globally

---

## File Structure Guidance

Create the following files (use `php artisan make:` commands where possible):

| File | Path | Command |
|---|---|---|
| Form Request | `app/Http/Requests/Auth/StoreRegisterRequest.php` | `php artisan make:request Auth/StoreRegisterRequest --no-interaction` |
| Controller | `app/Http/Controllers/Auth/AuthController.php` | `php artisan make:controller Auth/AuthController --no-interaction` |
| Service | `app/Services/AuthService.php` | `php artisan make:class app/Services/AuthService --no-interaction` |
| Repository | `app/Repositories/UserRepository.php` | `php artisan make:class app/Repositories/UserRepository --no-interaction` |
| Feature Test | `tests/Feature/Auth/RegisterTest.php` | `php artisan make:test --pest Auth/RegisterTest --no-interaction` |

### Route Registration

Add to `routes/api.php`:

```
POST /api/auth/register → AuthController@register (public, no middleware)
```

### Method Names

| Layer | Method Signature |
|---|---|
| Controller | `public function register(StoreRegisterRequest $request): JsonResponse` |
| Service | `public function registerUser(array $data): User` |
| Repository | `public function create(array $data): User` |

---

## Definition of Done

- [ ] Route `POST /api/auth/register` is registered in `routes/api.php`
- [ ] `StoreRegisterRequest` exists at `app/Http/Requests/Auth/StoreRegisterRequest.php` with correct validation rules
- [ ] `AuthController` exists at `app/Http/Controllers/Auth/AuthController.php` with a `register()` method
- [ ] `AuthService` exists at `app/Services/AuthService.php` with a `registerUser()` method
- [ ] `UserRepository` exists at `app/Repositories/UserRepository.php` with a `create()` method
- [ ] The controller delegates entirely to the service — no business logic in the controller
- [ ] The service delegates DB write to the repository — no `User::create()` directly in the service
- [ ] `Auth::login($user)` is called inside the service after user creation
- [ ] No token is returned in the response body
- [ ] The response uses HTTP `201 Created`
- [ ] The response follows the `ApiResponse` structure: `{ success, data, message }`
- [ ] `password` and `remember_token` are NOT present in the response data
- [ ] Pest feature tests exist in `tests/Feature/Auth/RegisterTest.php`
- [ ] All tests pass: `php artisan test --compact`
- [ ] PHP files are formatted with Pint: `vendor/bin/pint --dirty --format agent`
