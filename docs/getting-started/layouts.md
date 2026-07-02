# Layouts

> Learn how Nova layouts work, how they're discovered automatically, and how they help you build consistent applications with minimal duplication.

---

# What You'll Learn

In this chapter you'll learn:

- What a layout is
- Why layouts exist
- Nested layouts
- Layout discovery
- Sharing data with child pages
- Layout assets
- Layout hierarchy
- Best practices

By the end of this chapter you'll know how to build complete application shells that are shared automatically across multiple pages.

---

# What is a Layout?

A layout is a shared wrapper around one or more pages.

Instead of repeating your navigation, sidebar, footer and scripts on every page, you place them inside a layout.

Example

```
Dashboard
│
├── Sidebar
├── Navigation
├── Footer
└── Content
```

Every page inside the dashboard automatically inherits this structure.

---

# Why Layouts?

Imagine building this structure.

```
Dashboard

├── Users

├── Products

├── Customers

├── Orders

├── Reports
```

Without layouts, every page would duplicate

- HTML
- Navigation
- Sidebar
- Footer
- Scripts
- Styles

Nova removes this duplication.

---

# Creating Your First Layout

Create

```
app/

dashboard/

layout.php

layout.latte
```

Congratulations.

Every page inside

```
app/dashboard/
```

now automatically uses this layout.

No configuration required.

---

# Layout Structure

```
app/

dashboard/

layout.php

layout.latte

users/

page.php

products/

page.php

reports/

page.php
```

All child pages inherit the dashboard layout.

---

# layout.php

This file contains the server-side logic shared by every child page.

Example

```php
<?php

return [

    'appName' => 'Nova CRM',

    'year' => date('Y')

];
```

Every child page automatically receives this data.

---

# layout.latte

This file defines the shared HTML.

Example

```latte
<!DOCTYPE html>

<html>

<head>

<title>{$appName}</title>

</head>

<body>

<header>

Navigation

</header>

<aside>

Sidebar

</aside>

<main>

{$content}

</main>

<footer>

© {$year}

</footer>

</body>

</html>
```

The variable

```
{$content}
```

is replaced with the rendered child page.

---

# Folder Example

```
dashboard/

layout.php

layout.latte

users/

page.php

page.latte

products/

page.php

page.latte
```

Visiting

```
/dashboard/users
```

renders

```
layout

↓

users page
```

---

# How Nova Finds Layouts

Nova searches upward.

Example

```
app/

dashboard/

layout.latte

users/

details/

page.php
```

Nova walks upward

```
details

↓

users

↓

dashboard

↓

layout found
```

The first layout found becomes the active layout.

---

# Nested Layouts

Layouts can be nested.

Example

```
app/

layout.latte

dashboard/

layout.latte

users/

layout.latte

page.latte
```

Hierarchy

```
Root Layout

↓

Dashboard Layout

↓

Users Layout

↓

Page
```

Each layout wraps the next one.

---

# Layout Assets

Layouts can have their own assets.

```
layout.ts

layout.scss
```

Nova loads them automatically.

Useful for

- Sidebar behaviour
- Navigation
- Global dashboard styles

---

# Layout Components

Layouts can use components exactly like pages.

Example

```latte
{component 'Sidebar'}

{component 'Navigation'}

{$content}

{component 'Footer'}
```

This keeps layouts clean and reusable.

---

# Sharing Data

Everything returned by

```
layout.php
```

is available to every child page.

Example

```php
return [

'appName'=>'Nova',

'user'=>user()

];
```

Then

```latte
Welcome

{$user['name']}
```

works in every page.

---

# Page Data vs Layout Data

Nova merges data automatically.

Example

Layout

```php
return [

'appName'=>'Nova'

];
```

Page

```php
return [

'title'=>'Dashboard'

];
```

Template receives

```php
$appName

$title
```

Both are available.

---

# Layout Discovery

Nova automatically discovers

```
layout.php

layout.latte

layout.ts

layout.scss
```

No registration.

No inheritance.

No configuration.

---

# Complete Example

```
dashboard/

layout.php

layout.latte

layout.ts

layout.scss

users/

page.php

page.latte

products/

page.php

page.latte

reports/

page.php

page.latte
```

This creates a complete dashboard application.

---

# Best Practices

✅ Create one layout per feature.

✅ Keep layouts focused on shared UI.

✅ Use components inside layouts.

✅ Keep business logic minimal.

✅ Put page-specific logic inside `page.php`.

---

# Common Mistakes

## Putting page logic inside layouts

Avoid

```php
$orders = db()->table('orders')->get();
```

inside

```
layout.php
```

Layouts should only contain data shared across multiple pages.

---

## Deeply Nested Layouts

Avoid

```
Root

↓

Admin

↓

CRM

↓

Sales

↓

Reports

↓

Monthly
```

Two or three levels are usually enough.

---

## Duplicating Navigation

Create one navigation component.

Reuse it.

Don't copy HTML across layouts.

---

# Behind the Scenes

When a request arrives

```
/dashboard/users
```

Nova performs

```
Router

↓

Page Discovery

↓

Find page.php

↓

Search Parent Folders

↓

Collect Layouts

↓

Merge Layout Data

↓

Render Page

↓

Wrap Layouts

↓

Return HTML
```

Everything happens automatically.

---

# Challenge

Create this structure.

```
app/

dashboard/

layout.latte

users/

page.latte

products/

page.latte

reports/

page.latte
```

Add

- Navigation
- Sidebar
- Footer

Notice how every page automatically shares the same UI.

---

# Chapter Summary

In this chapter you learned

- What layouts are
- Automatic layout discovery
- Nested layouts
- Sharing layout data
- Layout assets
- Layout hierarchy

You can now build complete application shells without duplicating HTML.

---

# Best Practice Example

A typical business application might look like this.

```
app/

layout.*

login/

page.*

dashboard/

layout.*

users/

page.*

products/

page.*

customers/

page.*

reports/

layout.*

sales/

page.*

inventory/

page.*
```

Each section has its own layout while still sharing the root application layout.

This structure scales well from small projects to enterprise applications.

---

# Next Chapter

➡ **Components**

In the next chapter you'll learn how to build reusable UI components.

You'll create buttons, cards, modals, tables and reusable widgets that can be shared across pages and layouts while keeping your application modular and easy to maintain.