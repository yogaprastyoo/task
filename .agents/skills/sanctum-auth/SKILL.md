---

name: sanctum-auth
description: Handles authentication using Laravel Sanctum (cookie-based). Use when implementing login, register, logout, and protecting API routes.
---

# Sanctum Auth Skill

## When To Use

Use this skill when:

* Registering a new user
* Logging in user
* Logging out user
* Protecting routes with authentication
* Accessing authenticated user (auth()->user())

---

## Core Concept

Authentication uses Laravel Sanctum (SPA mode):

* Uses HttpOnly cookies
* No token returned in response body
* Browser automatically handles session

---

## Core Rules

* MUST use Sanctum for authentication
* MUST use cookie-based session (not token manually)
* MUST protect routes using auth:sanctum middleware
* MUST use auth()->user() to get current user

---

## Decision Tree

### Register

→ validate input
→ create user (hashed password)
→ login user automatically
→ return success response

---

### Login

→ validate credentials
→ attempt login

IF success:
→ create session
→ return success

ELSE:
→ THROW error

---

### Logout

→ destroy session
→ return success

---

### Access Protected Route

IF user NOT authenticated:
→ return 401

ELSE:
→ allow access

---

## Execution Steps

### Register

1. Validate name, email, password
2. Hash password (bcrypt)
3. Create user via repository
4. Log user in
5. Return response

---

### Login

1. Validate email & password
2. Attempt login using Auth::attempt()
3. If success → session created
4. Return success response

---

### Logout

1. Call Auth::logout()
2. Invalidate session
3. Return success response

---

## Example (Best Practice)

```php id="l2l7r3"
use Illuminate\Support\Facades\Auth;

public function login(array $data): void
{
    if (!Auth::attempt($data)) {
        throw new \Exception('Invalid credentials');
    }
}
```

---

## Controller Example

```php id="y2kt6r"
public function login(LoginRequest $request)
{
    $this->service->login($request->validated());

    return ApiResponse::success(null, 'Login successful');
}
```

---

## Route Protection

```php id="yok3ox"
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/workspaces', ...);
});
```

---

## Access Auth User

```php id="ct0h4c"
$user = auth()->user();
```

---

## Constraints

* Do not return token manually
* Do not use JWT (for MVP)
* Do not store auth logic in controller
* Do not bypass Sanctum middleware

---

## Anti-Patterns

### Manual Token Handling

```php id="7p1zdf"
return ['token' => $token];
```

---

### Logic in Controller

```php id="bbz9mf"
Auth::attempt(...)
```

---

### Skipping Middleware

```php id="l0l2a3"
// route without auth:sanctum
```

---

## Expected Behavior

* User authenticated via cookies
* All protected routes require login
* Session handled automatically by browser
