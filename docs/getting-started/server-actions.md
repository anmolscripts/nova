# Server Actions

> Learn how Nova handles form submissions, AJAX requests, and server-side operations using Server Actions.

---

# What You'll Learn

In this chapter you'll learn:

- What Server Actions are
- Why Nova uses Server Actions
- Creating your first action
- Traditional forms
- SPA-like requests using `fetch()`
- Validation
- Returning responses
- Redirects
- Error handling
- Best practices

By the end of this chapter you'll be able to build modern applications without writing controllers or API endpoints.

---

# What are Server Actions?

A Server Action is a PHP file that performs an operation on the server.

Examples include:

- Save User
- Update Product
- Delete Order
- Upload File
- Login
- Logout
- Change Password

Think of them as "functions exposed over HTTP".

Unlike traditional frameworks, Nova does **not** require controllers or route registration.

Simply create a file.

Nova automatically exposes it.

---

# Why Server Actions?

Traditional PHP applications often require this flow.

```
Form

↓

Route

↓

Controller

↓

Validation

↓

Database

↓

Redirect
```

Modern JavaScript frameworks require even more.

```
Component

↓

Fetch

↓

API Route

↓

Controller

↓

Validation

↓

Database

↓

JSON

↓

Browser
```

Nova simplifies everything.

```
Form

↓

Server Action

↓

Database

↓

Response
```

One file.

No boilerplate.

---

# Creating Your First Action

Create

```
app/

users/

actions/

save.php
```

Nova automatically exposes

```
POST

/users/actions/save
```

No configuration required.

---

# Your First Action

```php
<?php

return success([

    'message' => 'User created successfully.'

]);
```

That's it.

You have created an endpoint.

---

# Using HTML Forms

Create a form.

```html
<form

action="/users/actions/save"

method="POST"

>

<input

name="name"

>

<button>

Save

</button>

</form>
```

Nova automatically sends the request to

```
save.php
```

---

# Reading Input

Inside

```
save.php
```

```php
$name = request('name');
```

Or

```php
$email = request('email');
```

Or retrieve all input.

```php
$data = request()->all();
```

---

# Returning Success

```php
return success([

'user'=>$user

],'User created successfully.');
```

For HTML forms Nova automatically:

- redirects back
- flashes the success message
- preserves the session

For JavaScript requests Nova returns JSON.

The same action supports both.

---

# Returning Errors

```php
return error([

'email'=>'Already exists.'

]);
```

For HTML

↓

Redirect back.

Flash errors.

Old input preserved.

For Fetch

↓

JSON response.

No extra work.

---

# Redirecting

```php
return redirect('/users');
```

Nova handles redirects automatically.

---

# Returning JSON

```php
return json([

'status'=>'ok'

]);
```

Useful for custom APIs.

---

# Validation

Validation is built into Server Actions.

Example

```php
validate([

'name'=>'required',

'email'=>'required|email'

]);
```

If validation fails

Nova automatically:

- returns validation errors
- redirects back (HTML)
- returns JSON errors (Fetch)
- preserves old input

---

# Saving to the Database

Example

```php
db()

->table('users')

->insert([

'name'=>request('name'),

'email'=>request('email')

]);
```

Then

```php
return success();
```

---

# Using Fetch

Nova includes a helper.

```ts
import {

action

}

from "@/helpers/action";

await action(

"/users/actions/save",

{

name:"John",

email:"john@example.com"

}

);
```

No manual

- CSRF
- Headers
- JSON parsing

Nova handles everything.

---

# What Happens Internally?

The helper automatically

- sends JSON
- includes CSRF token
- waits for response
- throws ActionError on failure
- returns parsed JSON

Developers write almost no boilerplate.

---

# CSRF Protection

Every Server Action is protected automatically.

HTML Forms

↓

Hidden token

JavaScript

↓

Header

```
X-CSRF-Token
```

No manual configuration.

---

# Uploading Files

Example

```html
<form

enctype="multipart/form-data"

>

<input

type="file"

name="avatar"

>

</form>
```

Retrieve

```php
$file = request()->file('avatar');
```

Store

```php
$file->store(

'avatars'

);
```

---

# Multiple Actions

```
users/

actions/

save.php

update.php

delete.php

export.php
```

Each action becomes

```
/users/actions/save

/users/actions/update

/users/actions/delete

/users/actions/export
```

---

# Organizing Actions

Keep actions close to the feature.

Example

```
users/

actions/

save.php

delete.php

change-password.php

reset-password.php
```

Don't create giant action folders.

---

# Action Responses

Nova supports

```php
success()

error()

redirect()

json()

abort()
```

Choose the response that best matches your use case.

---

# SPA-like Experience

Server Actions make applications feel like Single Page Applications.

Example.

User clicks

```
Save
```

↓

JavaScript sends request

↓

Server Action executes

↓

Database updated

↓

JSON returned

↓

Page updates

↓

No full reload

Everything still runs on the server.

---

# Error Handling

If an exception occurs

Nova automatically

- logs the error
- returns JSON for Fetch
- renders an error page for HTML

No extra code required.

---

# Best Practices

✅ One action = One responsibility

✅ Keep actions small

✅ Validate every request

✅ Return helper responses

✅ Keep business logic close to the feature

---

# Common Mistakes

## Creating Controllers

Server Actions replace controllers.

Don't create

```
UserController.php
```

Create

```
actions/save.php
```

instead.

---

## Creating APIs

If the consumer is your own frontend,

use Server Actions.

Reserve APIs for external integrations.

---

## Writing AJAX Boilerplate

Nova already provides

```
action()
```

Use it.

---

# Action Lifecycle

```
Browser

↓

Form / Fetch

↓

Server Action

↓

Validation

↓

Database

↓

Response Helper

↓

HTML / JSON

↓

Browser
```

Everything happens automatically.

---

# Challenge

Create the following actions.

```
save.php

update.php

delete.php

login.php

logout.php
```

Use both

- HTML forms
- JavaScript

Observe how the same action supports both approaches.

---

# Chapter Summary

In this chapter you learned

- What Server Actions are
- Creating actions
- HTML forms
- JavaScript requests
- Validation
- Responses
- Database operations
- SPA-like behaviour

You can now build interactive applications without writing controllers or APIs.

---

# Behind the Scenes

When you call

```
action('/users/actions/save')
```

Nova performs

```
Fetch Request

↓

CSRF Verification

↓

Locate Action

↓

Execute PHP

↓

Validation

↓

Database

↓

Response Adapter

↓

HTML or JSON

↓

Browser
```

The same action serves both traditional forms and modern JavaScript requests.

---

# Next Chapter

➡ **Working with the Database**

In the next chapter you'll learn how Nova interacts with databases using its lightweight database engine.

You'll discover how to:

- Query data
- Insert records
- Update rows
- Delete records
- Execute transactions
- Call stored procedures
- Build maintainable data access without a heavy ORM

By the end of the chapter you'll be comfortable building data-driven applications with Nova.