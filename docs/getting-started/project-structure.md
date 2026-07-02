# Understanding the Project Structure

> Learn how Nova organizes applications and how the framework automatically discovers pages, layouts, components, actions, and assets.

---

# What You'll Learn

In this chapter you'll learn:

- Every folder inside a Nova project
- Which folders are required
- Which files are optional
- How file discovery works
- How routing is generated automatically
- How to organize large applications
- Best practices for scalable projects

By the end of this chapter, you'll understand the complete Nova project structure.

---

# Nova Philosophy

One of Nova's biggest differences from traditional PHP frameworks is that **you don't tell Nova where things are**.

Instead...

**Nova discovers everything automatically.**

If you follow Nova's conventions, there is almost zero configuration.

---

# Root Directory

A fresh Nova project looks like this.

```text
app/
bootstrap/
components/
config/
database/
framework/
public/
resources/
storage/
tests/

composer.json
package.json
.env
```

Every folder has a specific responsibility.

---

# Overview

| Folder | Purpose |
|---------|---------|
| app | Application pages |
| bootstrap | Framework bootstrap |
| components | Global reusable components |
| config | Configuration |
| database | Database files |
| framework | Nova Core |
| public | Public web root |
| resources | Assets |
| storage | Runtime storage |
| tests | Tests |

---

# app/

The **app** directory is the heart of your application.

Everything a user visits starts here.

Example

```text
app/

about/

dashboard/

users/

products/
```

Each folder represents a route.

Example

```text
app/

users/

page.php
```

becomes

```
/users
```

---

# Required File

Every page folder must contain

```text
page.php
```

Without this file Nova ignores the folder.

Example

```text
app/

users/

page.php
```

---

# Optional Files

A page folder may also contain

```text
page.latte
page.ts
page.scss
layout.php
layout.latte
layout.ts
layout.scss
middleware.php
```

Everything is discovered automatically.

---

# Complete Example

```text
app/

users/

page.php
page.latte
page.ts
page.scss

layout.php
layout.latte

middleware.php

actions/

save.php
delete.php

components/

UserCard/

UserTable/
```

Nova automatically understands this structure.

No configuration required.

---

# page.php

Required

Responsible for

- Business Logic
- Database
- Returning data

Example

```php
return [
    'users' => db()->table('users')->get()
];
```

Think of it as the server-side logic for a page.

---

# page.latte

Optional

Responsible for UI.

Example

```latte
<h1>Users</h1>
```

If omitted,

Nova can still return JSON or another response from `page.php`.

---

# page.ts

Optional.

Contains page-specific TypeScript.

Loaded only for this page.

Perfect for

- Charts
- Tables
- Client interactions

---

# page.scss

Optional.

Contains page-specific styles.

Loaded only when the page is used.

---

# layout.php

Optional.

Provides server-side logic shared by all child pages.

Example

```text
dashboard/

layout.php

users/

page.php

products/

page.php
```

Both users and products inherit the dashboard layout.

---

# layout.latte

Optional.

Provides shared HTML.

Example

```latte
<html>

<body>

{$content}

</body>

</html>
```

---

# middleware.php

Optional.

Protects a page.

Example

```php
return [

'auth'

];
```

Nova automatically applies middleware.

---

# actions/

Optional.

Contains Server Actions.

Example

```text
actions/

save.php

delete.php

update.php
```

Automatically exposed as

```
/users/actions/save
```

No routes required.

---

# components/

Optional.

Contains page-local components.

Example

```text
components/

StatsCard/

component.latte

component.php
```

Local components override global components automatically.

---

# Global Components

Located here

```text
components/
```

Example

```text
components/

Button/

Card/

Modal/
```

These are available everywhere.

---

# resources/

Contains frontend assets.

```text
resources/

scss/

ts/

fonts/

images/
```

Shared across the application.

---

# public/

Public web root.

Contains

```text
index.php

assets/

favicon.ico
```

Only this folder should be publicly accessible.

---

# storage/

Nova stores runtime files here.

Example

```text
storage/

framework/

sessions/

logs/

uploads/
```

Do not edit these files manually.

---

# config/

Contains framework configuration.

Example

```text
config/

app.php

database.php

auth.php

storage.php

view.php
```

---

# database/

Contains database-related files.

Example

```text
migrations/

procedures/

seeders/

functions/

triggers/
```

---

# framework/

Contains Nova itself.

Normally,

developers never modify this folder.

---

# Auto Discovery

Nova automatically discovers

✅ Pages

✅ Layouts

✅ Components

✅ Server Actions

✅ Middleware

✅ Assets

Nothing needs registration.

---

# Routing Example

Folder

```text
app/

users/

page.php
```

↓

URL

```
/users
```

---

Dynamic Route

```text
app/

users/

[id]/

page.php
```

↓

```
/users/25
```

---

Catch All

```text
app/

docs/

[...slug]/

page.php
```

↓

```
/docs/php/routing/files
```

---

Optional Catch All

```text
app/

blog/

[[...slug]]/

page.php
```

Matches

```
/blog

/blog/php

/blog/php/nova
```

---

# File Priority

Nova loads files in the following order.

```
page.php

↓

layout.php

↓

page.latte

↓

page.ts

↓

page.scss
```

Additional files are loaded only if they exist.

---

# Required vs Optional

| File | Required |
|------|----------|
| page.php | ✅ |
| page.latte | Optional |
| page.ts | Optional |
| page.scss | Optional |
| layout.php | Optional |
| layout.latte | Optional |
| layout.ts | Optional |
| layout.scss | Optional |
| middleware.php | Optional |
| actions | Optional |
| components | Optional |

---

# Best Practices

✅ Organize code by feature.

✅ Keep actions inside the page.

✅ Use local components whenever possible.

✅ Use global components only when reused.

✅ Keep page-specific assets inside the page.

---

# Common Mistakes

❌ Creating controllers.

Nova doesn't use controllers.

---

❌ Creating route files.

Routing is automatic.

---

❌ Sharing unrelated components globally.

Prefer local components.

---

❌ Putting business logic inside Latte templates.

Keep templates focused on presentation.

---

# Behind the Scenes

When a request arrives:

```
Browser
      │
      ▼
Router
      │
      ▼
Page Discovery
      │
      ▼
Find page.php
      │
      ▼
Load Layout
      │
      ▼
Load Components
      │
      ▼
Compile Latte
      │
      ▼
Response
```

Every step happens automatically.

---

# Chapter Summary

In this chapter you learned:

- Every Nova folder
- Required files
- Optional files
- Automatic discovery
- File-based routing
- Feature-oriented architecture

You should now understand how Nova organizes an application without requiring manual routing or controller registration.

---

# Next Chapter

➡ **Creating Your First Page**

In the next chapter you'll build your first real page, understand how `page.php` and `page.latte` work together, and see how Nova transforms folders into routes automatically.