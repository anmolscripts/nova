# Deployment

> Learn how to prepare, optimize, and deploy a Nova application to production.

---

# What You'll Learn

In this chapter you'll learn:

- Development vs Production
- Building frontend assets
- Optimizing Nova
- Environment configuration
- Shared Hosting deployment
- VPS deployment
- Production checklist
- Common deployment problems
- Best practices

By the end of this chapter you'll know how to deploy any Nova application.

---

# Development vs Production

During development Nova prioritizes:

- Fast reloads
- Debugging
- Hot Module Replacement
- Detailed error pages

Production has different priorities.

Production focuses on:

- Speed
- Security
- Stability
- Caching
- Optimized assets

Never deploy a development environment directly to production.

---

# Development Workflow

While developing you'll normally have two terminals.

Terminal 1

```bash
php nova serve
```

Runs the PHP development server.

---

Terminal 2

```bash
npm run dev
```

Runs Vite.

Provides

- Hot Reload
- TypeScript Compilation
- SCSS Compilation

---

# Production Workflow

Before deployment build your assets.

```bash
npm run build
```

This command:

- Compiles TypeScript
- Compiles SCSS
- Minifies JavaScript
- Optimizes CSS
- Generates production assets
- Creates the Vite manifest

---

# Optimize Nova

Next, optimize the framework.

```bash
php nova optimize
```

Nova prepares your application by:

- Validating configuration
- Building route cache
- Building page cache
- Building component cache
- Compiling Latte templates
- Verifying asset manifests
- Warming framework caches

When finished Nova reports an optimization summary.

---

# Verify the Build

Before uploading, verify everything works locally.

Run

```bash
php nova optimize:status
```

Example output

```text
Configuration Cache     ✓

Route Cache             ✓

Page Cache              ✓

Component Cache         ✓

View Cache              ✓

Asset Manifest          ✓

Framework Ready         ✓
```

If any stage reports an error, resolve it before deployment.

---

# Environment Variables

Production settings belong in `.env`.

Example

```dotenv
APP_NAME=Nova

APP_ENV=production

APP_DEBUG=false

APP_URL=https://example.com
```

Never enable debug mode in production.

```dotenv
APP_DEBUG=false
```

This prevents stack traces and sensitive information from being exposed to users.

---

# Database Configuration

Example

```dotenv
DB_DRIVER=mysql

DB_HOST=localhost

DB_PORT=3306

DB_DATABASE=my_database

DB_USERNAME=my_user

DB_PASSWORD=my_password
```

Use production credentials only on the production server.

Never commit secrets to Git.

---

# Shared Hosting Deployment

Nova is designed to work on shared hosting.

Typical deployment steps:

1. Build the application locally.

```bash
npm run build

php nova optimize
```

2. Upload the project to your hosting provider.

3. Point the web root to:

```
public/
```

4. Upload the `.env` file.

5. Set writable permissions for:

```
storage/
```

6. Import the database.

Your application is now live.

Node.js is **not** required on the hosting server if assets are built locally.

---

# VPS Deployment

Typical VPS deployment:

Clone the project.

```bash
git clone https://github.com/your-company/project.git
```

Install PHP dependencies.

```bash
composer install --no-dev --optimize-autoloader
```

Install Node dependencies.

```bash
npm install
```

Build production assets.

```bash
npm run build
```

Optimize Nova.

```bash
php nova optimize
```

Configure:

- Nginx or Apache
- SSL
- PHP
- Database

Start serving your application.

---

# Updating an Existing Application

Typical deployment workflow.

```bash
git pull

composer install --no-dev

npm install

npm run build

php nova optimize
```

Restart PHP if required by your hosting environment.

---

# Clearing Optimizations

If configuration changes unexpectedly, clear caches.

```bash
php nova optimize:clear
```

Then rebuild.

```bash
php nova optimize
```

---

# Storage Permissions

Nova writes runtime files to:

```
storage/

storage/framework/

storage/uploads/

storage/sessions/
```

Ensure these directories are writable by PHP.

---

# Public Folder

Only expose

```
public/
```

to the internet.

Everything else should remain outside the public web root.

Never expose:

```
app/

framework/

config/

database/

storage/

vendor/
```

---

# Logging

In production Nova logs errors instead of displaying them.

Monitor your logs regularly.

Unexpected exceptions should always be investigated.

---

# Security Checklist

Before going live verify:

✅ APP_DEBUG=false

✅ Production database configured

✅ HTTPS enabled

✅ Storage writable

✅ Public directory configured correctly

✅ Environment variables configured

✅ Latest dependencies installed

✅ Assets built

✅ Nova optimized

---

# Performance Checklist

Before every deployment:

```bash
composer install --no-dev

npm run build

php nova optimize
```

These commands should become part of your deployment pipeline.

---

# Common Deployment Problems

## Styles Not Loading

Cause:

Assets were not built.

Solution:

```bash
npm run build
```

---

## 500 Internal Server Error

Possible causes:

- Missing `.env`
- Incorrect permissions
- Missing database
- Invalid configuration

Check logs for details.

---

## Page Not Found

Verify your web server points to:

```
public/
```

Not the project root.

---

## Database Connection Failed

Verify:

- Host
- Username
- Password
- Database
- Port

---

## Permission Denied

Ensure PHP can write to:

```
storage/
```

---

# Deployment Flow

```
Application

↓

npm run build

↓

php nova optimize

↓

Upload

↓

Configure .env

↓

Configure Database

↓

Configure Web Server

↓

Application Live
```

---

# Best Practices

✅ Build assets before deployment.

✅ Run `php nova optimize`.

✅ Use HTTPS.

✅ Keep secrets in `.env`.

✅ Monitor logs.

✅ Keep backups.

✅ Update dependencies regularly.

---

# Production Checklist

Before releasing your application verify:

- Assets compiled
- Framework optimized
- Environment configured
- Database migrated
- Storage writable
- SSL installed
- Debug disabled
- Logs monitored

If every item is complete, your application is ready for production.

---

# Congratulations!

You have completed the **Learn Nova** guide.

Throughout this guide you've learned how to:

- Create pages
- Understand file-based routing
- Build layouts
- Create reusable components
- Handle Server Actions
- Work with databases
- Authenticate users
- Deploy applications

You now have the knowledge required to build production-ready applications with Nova.

---

# Where to Go Next

Now that you've learned the fundamentals, continue with the next documentation books.

📘 **Nova Handbook**

Learn every feature of the framework in detail.

Topics include:

- Routing
- Components
- Layouts
- Middleware
- Validation
- Storage
- CLI
- Configuration
- Performance
- Optimization

---

📗 **Architecture Guide**

Understand how Nova works internally.

Topics include:

- Request Lifecycle
- Router
- Page Discovery
- Component Discovery
- Server Actions
- Database Engine
- Profiler
- Optimizer

---

📙 **API Reference**

Complete documentation for every helper, class, and CLI command.

---

📕 **Extension Development**

Learn how to build installable Nova packages including:

- Queue
- Mail
- Scheduler
- Storage Drivers
- CLI Commands
- Custom Components

---

# Thank You

Thank you for choosing Nova.

Nova was built around one simple idea:

> Modern PHP development should be simple, enjoyable, and accessible to everyone.

We hope Nova helps you build amazing applications.

Happy coding! 🚀