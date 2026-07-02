# Authentication & Authorization

> Learn how Nova authenticates users, protects pages, authorizes actions, and manages sessions securely.

---

# What You'll Learn

In this chapter you'll learn:

- Authentication
- Authorization
- Sessions
- Login
- Logout
- Remember Me
- Protected Pages
- Middleware
- Policies
- Password Hashing
- Current User Helpers

By the end of this chapter you'll be able to secure any Nova application.

---

# Authentication vs Authorization

These two concepts are often confused.

Authentication answers:

> **Who are you?**

Authorization answers:

> **What are you allowed to do?**

Nova treats them as separate concerns.

---

# Authentication Flow

```
User

↓

Login Form

↓

Server Action

↓

Credentials Verified

↓

Session Created

↓

Authenticated User

↓

Protected Pages
```

---

# User Provider

Nova authenticates users using a User Provider.

The default provider reads users from the configured database.

Example

```php
config/auth.php
```

```php
return [

    'provider' => DatabaseUserProvider::class,

];
```

Developers can replace the provider with a custom implementation.

---

# Password Hashing

Never store plain text passwords.

Hash passwords before saving.

```php
$password = hash_password(

request('password')

);
```

Nova uses PHP's native password hashing.

---

# Verifying Passwords

```php
verify_password(

$requestPassword,

$storedHash

);
```

Normally, you won't call this directly because `attempt()` handles verification for you.

---

# Login

A typical login action.

```php
if(

attempt([

'email'=>request('email'),

'password'=>request('password')

])

){

    return redirect('/dashboard');

}

return error([

'email'=>'Invalid credentials.'

]);
```

---

# Logout

Logging out is simple.

```php
logout();

return redirect('/login');
```

Nova clears:

- Authentication session
- Remember Me cookie
- User state

---

# Remember Me

Enable Remember Me.

```php
attempt(

$request,

true

);
```

Nova automatically stores a secure remember token.

The next visit restores the user's session.

---

# Current User

Retrieve the authenticated user.

```php
$user = user();
```

Check whether a user is logged in.

```php
check();
```

Guest?

```php
guest();
```

Current ID

```php
id();
```

---

# Login State

```php
if(check()){

    echo user()['name'];

}
```

Or

```php
if(guest()){

}
```

---

# Protecting Pages

Create

```
middleware.php
```

Example

```php
return [

'auth'

];
```

Every request to this page now requires authentication.

---

# Guest Pages

Example

```php
return [

'guest'

];
```

Authenticated users are redirected automatically.

Useful for:

- Login
- Register
- Forgot Password

---

# Middleware Flow

```
Request

↓

Router

↓

Page Discovery

↓

Middleware

↓

Authentication

↓

Page
```

---

# Authorization

Authentication tells Nova **who** the user is.

Authorization decides **what** they can do.

---

# Policies

Policies live in

```
app/

policies/
```

Example

```
ProductPolicy.php
```

```php
public function update(

$user,

$product

){

    return

    $user['id']

    ===

    $product['created_by'];

}
```

---

# Checking Permissions

```php
if(

can(

'update',

$product

)

){

}
```

Or

```php
authorize(

'update',

$product

);
```

If authorization fails,

Nova automatically returns

```
403 Forbidden
```

---

# Blade...

Nova uses Latte.

Inside templates

```latte
{if can('update',$product)}

<button>

Edit

</button>

{/if}
```

Simple.

---

# User Available Everywhere

Every Latte template receives

```
$user
```

Automatically.

Example

```latte
Hello

{$user['name']}
```

---

# Login Page Example

```
login/

page.php

page.latte

actions/

login.php
```

Simple.

Everything related to login stays together.

---

# Register Page

```
register/

page.php

page.latte

actions/

register.php
```

No controllers.

No route files.

---

# Forgot Password

```
forgot-password/

page.php

actions/

send.php
```

---

# Reset Password

```
reset-password/

page.php

actions/

reset.php
```

Everything follows the same convention.

---

# Session

Nova stores

```
User ID

Login Time

Remember Token

Flash Messages

CSRF Token
```

Sessions are managed automatically.

---

# Authentication Helpers

Nova provides

```php
auth()

user()

check()

guest()

id()

login()

logout()

attempt()

authorize()

can()

cannot()
```

These helpers cover almost every authentication scenario.

---

# Security Best Practices

✅ Always hash passwords.

✅ Protect forms with CSRF.

✅ Use middleware.

✅ Authorize sensitive actions.

✅ Validate login input.

✅ Keep business rules in policies.

---

# Common Mistakes

## Checking Roles Everywhere

Avoid

```php
if(

$user['role']

==

'admin'

)
```

Create a policy instead.

---

## Trusting Client Input

Never trust hidden fields.

Always retrieve the current user from

```php
user()
```

---

## Storing Passwords

Never store plain text passwords.

Always hash.

---

# Behind the Scenes

```
Login Form

↓

Server Action

↓

User Provider

↓

Password Verify

↓

Session

↓

Request

↓

Middleware

↓

Current User

↓

Page
```

Everything happens automatically.

---

# Challenge

Build

- Login
- Logout
- Register
- Forgot Password

Protect

```
Dashboard

Users

Products
```

using

```
auth
```

middleware.

Create one policy that allows users to edit only their own records.

---

# Chapter Summary

In this chapter you learned:

- Authentication
- Authorization
- Sessions
- Login
- Logout
- Middleware
- Policies
- Current User
- Security Best Practices

You can now build secure Nova applications.

---

# What's Next?

➡ **Deployment**

In the final chapter of this guide you'll prepare your application for production.

You'll learn how to:

- Build frontend assets
- Optimize Nova
- Deploy to shared hosting
- Configure production settings
- Troubleshoot common deployment issues

By the end of the guide you'll have everything you need to publish a real Nova application.