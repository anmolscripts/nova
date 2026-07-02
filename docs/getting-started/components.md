# Components

> Learn how to build reusable UI using Nova Components.

---

# What You'll Learn

In this chapter you'll learn:

- What components are
- Global vs Local components
- Component discovery
- Passing data (props)
- Server-side components
- Component assets
- Component lifecycle
- Best practices

By the end of this chapter you'll know how to build reusable UI while keeping your application clean and maintainable.

---

# What is a Component?

A component is a reusable piece of UI.

Instead of copying the same HTML multiple times, you create it once and reuse it everywhere.

Examples include:

- Buttons
- Cards
- Tables
- Navigation
- Sidebar
- Alerts
- Badges
- Modals
- User Cards
- Product Cards

Think of a component as a self-contained mini application.

---

# Why Components?

Imagine you have 40 pages.

Every page contains this button.

```html
<button class="btn btn-primary">

Save

</button>
```

If your design changes...

You must edit 40 files.

Instead

Create one component.

Use it everywhere.

---

# Global Components

Global components live here.

```
components/

Button/

Card/

Modal/

DataTable/

Alert/
```

Global components are available everywhere.

---

# Local Components

Sometimes a component belongs to only one feature.

Example

```
app/

dashboard/

components/

SalesChart/

component.latte

component.php
```

Only dashboard pages can use this component.

Nova always searches local components first.

---

# Component Structure

A complete component may contain

```
Button/

component.php

component.latte

component.ts

component.scss
```

Every file has one responsibility.

---

# Required Files

Only one file is required.

```
component.latte
```

Everything else is optional.

---

# component.latte

Responsible for UI.

Example

```latte
<button>

{$text}

</button>
```

---

# Rendering a Component

Use

```latte
{component 'Button'}
```

Nova discovers the component automatically.

---

# Passing Data

Components accept properties.

Example

```latte
{component

'Button',

text: 'Save',

color: 'primary'

}
```

Inside

```
component.latte
```

```latte
<button

class="btn btn-{$color}"

>

{$text}

</button>
```

---

# Default Values

Components should provide sensible defaults.

Example

```php
return [

'color'=>'primary',

'size'=>'md'

];
```

Then

```latte
{component 'Button'}
```

still renders correctly.

---

# Server Component

If

```
component.php
```

exists,

Nova executes it before rendering.

Example

```php
<?php

return [

'time'=>date('H:i')

];
```

Template

```latte
Current Time

{$time}
```

---

# Data Flow

Component data comes from two places.

```
component.php

↓

Props

↓

Merged

↓

component.latte
```

Props override defaults automatically.

---

# Component Assets

A component may have

```
component.ts

component.scss
```

Nova automatically includes them.

Assets load only once per request.

Even if the component is rendered 100 times.

---

# Component Discovery

Nova automatically scans

```
components/

app/**/components/
```

No registration.

No imports.

No configuration.

---

# Local Override

Imagine

Global

```
components/

Button/
```

and

Local

```
app/

dashboard/

components/

Button/
```

Inside dashboard pages,

Nova uses

```
Dashboard Button
```

Outside dashboard,

Nova uses

```
Global Button
```

This allows features to customize components without affecting the rest of the application.

---

# Nested Components

Components may contain other components.

Example

```latte
{component 'Card'}

{component 'Button'}

{/component}
```

Nova resolves dependencies automatically.

---

# Real Example

```
components/

Card/

component.latte

component.php

component.scss

component.ts
```

Template

```latte
<div class="card">

<h2>

{$title}

</h2>

{$slot}

</div>
```

Usage

```latte
{component

'Card',

title:'Monthly Sales'

}

Sales Chart

{/component}
```

---

# Slots

Components may expose slots.

Example

```latte
<Card>

Content

</Card>
```

Nova inserts the slot inside

```
{$slot}
```

This makes components extremely flexible.

---

# Best Practices

✅ Keep components small.

✅ One responsibility per component.

✅ Prefer local components.

✅ Move repeated HTML into components.

✅ Keep server logic inside component.php.

---

# Common Mistakes

## Creating Huge Components

Avoid

```
Dashboard

Everything

Inside

One Component
```

Instead

Split

```
Navigation

Sidebar

StatsCard

Chart

Activity

Table
```

---

## Business Logic Inside Templates

Avoid

```latte
{foreach db()->table(...) as $user}
```

Database queries belong inside

```
component.php
```

or

```
page.php
```

---

## Global Everything

Don't make every component global.

Use local components whenever possible.

---

# Component Lifecycle

When Nova renders

```
{component 'Button'}
```

it performs

```
Find Component

↓

component.php

↓

Merge Props

↓

component.latte

↓

Load Assets

↓

Return HTML
```

Assets are injected only once.

---

# Component Discovery Order

Nova searches

```
Current Page

↓

Parent Pages

↓

Global Components
```

The first matching component wins.

---

# Performance

Component discovery is cached.

Component assets are cached.

Assets are included only once.

Repeated rendering is extremely fast.

---

# Challenge

Create

```
Button

Card

Alert

StatsCard

UserCard
```

Use them in several pages.

Notice how changing one component updates every page automatically.

---

# Chapter Summary

You learned

- Global components
- Local components
- Props
- Server-side components
- Assets
- Discovery
- Slots
- Component lifecycle

You can now build reusable UI without repeating HTML.

---

# Behind the Scenes

When Nova encounters

```latte
{component 'Card'}
```

it performs

```
Template

↓

Component Discovery

↓

Find component.php

↓

Execute

↓

Merge Props

↓

Render component.latte

↓

Inject Assets

↓

Return HTML
```

Every step is automatic.

---

# Folder Structure Example

```
components/

Button/
│
├── component.php
├── component.latte
├── component.ts
└── component.scss

Card/
│
├── component.php
├── component.latte
├── component.ts
└── component.scss

Modal/
│
├── component.php
├── component.latte
├── component.ts
└── component.scss
```

This keeps your UI modular, reusable, and easy to maintain.

---

# Next Chapter

➡ **Server Actions**

In the next chapter you'll discover one of Nova's most powerful features.

You'll learn how to build modern, SPA-like applications **without writing APIs, AJAX boilerplate, or controllers**.

By the end of the chapter you'll be creating forms, updating data, deleting records, and refreshing only the necessary parts of the page—all while keeping your business logic on the server.