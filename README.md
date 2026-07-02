<div align="center">

# ✨ Nova Framework

### A Modern PHP Framework Inspired by Next.js

Build fast, server-first applications with file-based routing, server actions, TypeScript, SCSS, Latte templates and a modern developer experience.

---

⚡ Lightweight • 📁 File Based Routing • 🎨 Latte • 🚀 SPA-like UX • 🏠 Shared Hosting Friendly

---

> **Status:** 🚧 Under Active Development (Nova Core v1)

</div>

---

# Why Nova?

Nova was created with one simple idea.

> **PHP development should feel as modern as Next.js while remaining simple enough to deploy on shared hosting.**

Instead of forcing developers to choose between traditional server-rendered applications and heavy JavaScript SPAs, Nova combines both worlds.

With Nova you get:

- 📁 File-based routing
- 🎨 Latte templates
- ⚡ Server Actions
- 🔄 SPA-like page interactions
- 🎯 Functional development
- 🧩 Component architecture
- 💾 Database-first design
- 🚀 Zero configuration
- 📦 Tiny core
- 🔌 Extension-first ecosystem

---

# Philosophy

Nova follows five principles.

## 1. Convention over Configuration

Most things should work automatically.

Create a page.

Nova finds it.

Create a component.

Nova discovers it.

Create an action.

Nova registers it.

No configuration.

---

## 2. Server First

The server is the source of truth.

Rendering happens on the server.

Business logic stays on the server.

The browser only enhances the experience.

---

## 3. Progressive Enhancement

Applications should work without JavaScript.

When JavaScript is available, Nova upgrades interactions automatically.

Forms become AJAX.

Buttons become actions.

Pages update without refresh.

---

## 4. Functional Development

Nova encourages functions instead of large class hierarchies.

Instead of

```php
class UserController
{
    ...
}
```

Nova prefers

```php
return success([
    'user' => db()->first(...)
]);
```

---

## 5. Lightweight Core

Nova Core stays small.

Everything else is an extension.

Need Queue?

Install Queue.

Need Mail?

Install Mail.

Need Scheduler?

Install Scheduler.

Only install what you need.

---

# Features

- ✅ File Based Routing
- ✅ Dynamic Routing
- ✅ Layout Engine
- ✅ Component Engine
- ✅ Latte Templates
- ✅ TypeScript
- ✅ SCSS
- ✅ Server Actions
- ✅ Validation
- ✅ Authentication
- ✅ Authorization
- ✅ Database Engine
- ✅ Query Builder
- ✅ Storage Engine
- ✅ CLI
- ✅ Build Pipeline
- ✅ Performance Profiler
- ✅ Shared Hosting Friendly

---

# Project Structure

```text
app/
bootstrap/
components/
config/
database/
framework/
public/
resources/
routes/
storage/
tests/
```

Every folder has a purpose.

The framework automatically discovers pages, layouts, components and actions.

---

# Your First Page

Create

```text
app/

about/

page.php
```

Now visit

```
/about
```

That's it.

No route registration.

No controller.

No configuration.

---

# A Typical Page

```
app/

users/

page.php

page.latte

page.ts

page.scss
```

Each file has a responsibility.

| File | Purpose |
|------|---------|
| page.php | Server logic |
| page.latte | UI |
| page.ts | Client behaviour |
| page.scss | Styling |

Every file is optional except **page.php**.

---

# Server Actions

Server Actions replace traditional controllers.

Create

```text
app/

users/

actions/

save.php
```

Submit

```html
<form action="/users/actions/save" method="POST">
```

or

```ts
await action('/users/actions/save',{
    name:'John'
});
```

The same action handles both requests.

---

# Components

Reusable UI lives in

```text
components/

Button/

component.latte
```

Use

```latte
{component 'Button'}
```

Local page components override global components automatically.

---

# Database

Nova provides a lightweight database layer.

```php
$users = db()
    ->table('users')
    ->where('status','ACTIVE')
    ->get();
```

Or call stored procedures.

```php
db()->procedure(
    'sp_users',
    [
        10,
        'ACTIVE'
    ]
);
```

---

# Build

Development

```bash
npm run dev

php nova serve
```

Production

```bash
npm run build

php nova optimize
```

Upload to shared hosting.

Done.

---

# Roadmap

## Nova Core

- Foundation
- Routing
- Layouts
- Components
- Server Actions
- Validation
- Authentication
- Database
- Storage
- CLI
- Profiler

## Stabilization

- Production Optimizer
- DevTools
- Security Audit
- Documentation
- Testing

## Nova v1.0

First stable release.

## Ecosystem

- Queue
- Mail
- Notifications
- Scheduler
- Redis
- S3
- AI
- GraphQL

All shipped as installable extensions.

---

# 📚 Documentation

Start here if you're new to Nova.

- 📖 [Learn Nova (Zero to Hero)](docs/getting-started/README.md)
- 📘 [Nova Handbook](docs/handbook/README.md)
- 🏗️ [Architecture Guide](docs/architecture/README.md)
- 📑 [API Reference](docs/api/helpers.md)
- 🔌 [Extension Development](docs/extension-development/README.md)
---

# Contributing

Nova is built with simplicity in mind.

Every contribution should respect the following principles.

- Server First
- Functional Development
- Convention over Configuration
- Lightweight Core
- Extension First

---

# License

MIT

---

<div align="center">

## Nova

### Modern PHP for Modern Developers.

⭐ If you like Nova, consider starring the repository.

</div>