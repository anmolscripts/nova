# Installing Nova

> Learn how to install Nova, understand its development workflow, and run your first application.

---

# What You'll Learn

In this chapter you'll learn:

- Nova's system requirements
- Required development tools
- How to create a new Nova project
- Running the development server
- Understanding the build process
- Development vs Production workflow

By the end of this chapter you'll have your first Nova application running locally.

---

# System Requirements

Nova is built on modern PHP and modern frontend tooling.

Before installing Nova, make sure your development environment meets the following requirements.

| Software | Version |
|-----------|---------|
| PHP | 8.3+ (Recommended: Latest Stable) |
| Composer | Latest |
| Node.js | 22+ |
| npm | Latest |
| MySQL / MariaDB | Optional |
| Git | Recommended |

---

# Why Does Nova Use Node.js?

Nova is a server-first framework.

Node.js is **not required in production**.

It is only used during development to compile frontend assets.

Nova uses Node.js for:

- TypeScript
- SCSS
- Vite Development Server
- Production Asset Compilation

After building your project, Node.js is no longer required.

This makes Nova fully compatible with shared hosting.

---

# Install PHP

Download the latest supported version of PHP.

Verify your installation.

```bash
php -v
```

Example output

```text
PHP 8.5.7
```

---

# Install Composer

Verify Composer.

```bash
composer --version
```

Example

```text
Composer version 2.x
```

Composer manages Nova's PHP dependencies.

---

# Install Node.js

Verify Node.js.

```bash
node -v

npm -v
```

Example

```text
Node v24.x

npm 11.x
```

---

# Creating a Nova Project

Create a new project.

```bash
composer create-project nova/framework my-app
```

Move into the project.

```bash
cd my-app
```

---

# Install Frontend Dependencies

Install JavaScript dependencies.

```bash
npm install
```

Nova automatically installs:

- Vite
- TypeScript
- SCSS

No additional setup is required.

---

# Configure Environment

Create your environment file.

```text
.env
```

Copy the example.

```bash
cp .env.example .env
```

Configure your application.

Example

```dotenv
APP_NAME=Nova

APP_ENV=local

APP_DEBUG=true

APP_URL=http://localhost:8000
```

If your application uses a database, configure it now.

Example

```dotenv
DB_DRIVER=mysql

DB_HOST=127.0.0.1

DB_PORT=3306

DB_DATABASE=nova

DB_USERNAME=root

DB_PASSWORD=
```

---

# Start the Development Server

Nova provides a built-in development server.

```bash
php nova serve
```

Example output

```text
Nova Development Server

Application running at

http://localhost:8000
```

---

# Start the Frontend Development Server

Open another terminal.

Run

```bash
npm run dev
```

This starts the Vite development server.

Features include:

- Hot Module Replacement
- SCSS Compilation
- TypeScript Compilation
- Asset Watching

---

# Open Your Browser

Visit

```
http://localhost:8000
```

You should see the default Nova welcome page.

Congratulations!

Your first Nova application is now running.

---

# Understanding the Development Workflow

During development you'll typically have two terminals open.

Terminal 1

```bash
php nova serve
```

Handles:

- Routing
- Pages
- Components
- Server Actions
- Database
- Templates

Terminal 2

```bash
npm run dev
```

Handles:

- TypeScript
- SCSS
- Vite
- Asset Compilation

Nova automatically reloads your application whenever files change.

---

# Building for Production

Before deploying your application, build your frontend assets.

```bash
npm run build
```

Then optimize Nova.

```bash
php nova optimize
```

These commands:

- Compile TypeScript
- Compile SCSS
- Generate production assets
- Build framework caches
- Optimize application startup

---

# Shared Hosting Deployment

One of Nova's biggest advantages is shared hosting compatibility.

After building:

```bash
npm run build

php nova optimize
```

Upload your project to your hosting provider.

Node.js is no longer required.

Only PHP is needed to run your application.

---

# Common Problems

## PHP Version Too Old

Verify PHP.

```bash
php -v
```

Upgrade to a supported version.

---

## Composer Not Found

Verify Composer.

```bash
composer --version
```

Ensure Composer is added to your PATH.

---

## Node Not Found

Verify installation.

```bash
node -v

npm -v
```

Install the latest LTS version.

---

## Vite Doesn't Start

Delete

```text
node_modules
```

Run

```bash
npm install
```

again.

---

## Page Doesn't Load

Ensure both servers are running.

```bash
php nova serve

npm run dev
```

---

# Best Practices

✅ Keep PHP updated.

✅ Use the latest Composer.

✅ Use TypeScript instead of plain JavaScript.

✅ Organize SCSS by feature.

✅ Commit your `package.json` and `composer.json`.

❌ Do not commit `vendor/`.

❌ Do not commit `node_modules/`.

---

# Chapter Summary

In this chapter you learned:

- Nova's system requirements
- Installing PHP, Composer, and Node.js
- Creating a Nova project
- Running the development server
- Compiling frontend assets
- Preparing a production build

Your Nova development environment is now ready.

---

# Behind the Scenes

When you start Nova:

```
Browser
      │
      ▼
PHP Development Server
      │
      ▼
Nova Application
      │
      ▼
Routing Engine
      │
      ▼
Page Discovery
      │
      ▼
Layout Engine
      │
      ▼
Component Engine
      │
      ▼
Latte Template
      │
      ▼
HTML Response
```

Meanwhile, Vite watches your frontend files and recompiles assets whenever they change.

---

# Next Chapter

➡ **Project Structure**

In the next chapter you'll explore every folder inside a Nova application and learn how Nova automatically discovers pages, layouts, components, actions, and assets.