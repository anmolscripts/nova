# Creating Your First Page

> Learn how Nova turns folders into web pages using file-based routing.

---

# What You'll Learn

In this chapter you'll learn:

- How file-based routing works
- The minimum files required to create a page
- How Nova discovers pages automatically
- Returning data from the server
- Rendering a Latte template
- Page lifecycle
- Dynamic routing

By the end of this chapter you'll understand one of Nova's most important concepts.

---

# Before You Begin

You should already have:

- Nova installed
- Development server running
- Vite development server running

If not, complete the previous chapter first.

---

# The Nova Way

Traditional PHP frameworks usually require multiple files just to display a page.

For example:

```
Route

↓

Controller

↓

View

↓

Response
```

Nova removes almost all of this.

Instead...

Create a folder.

Create a page.

Done.

---

# Your First Route

Create the following structure.

```
app/

about/

page.php
```

Congratulations.

You just created the route

```
/about
```

No configuration.

No routes.

No controllers.

---

# Why page.php?

Every route in Nova begins with **page.php**.

Think of it as the server-side entry point for a page.

Without it, Nova ignores the folder.

This is the **only required file**.

---

# Returning HTML

The simplest page.

```php
<?php

echo "<h1>About Nova</h1>";
```

Visit

```
/about
```

You'll immediately see the output.

Although this works, it isn't the recommended approach.

---

# Returning Data

Normally, a page returns data.

Example

```php
<?php

return [

    'title' => 'About',

    'version' => '1.0'

];
```

Nova automatically passes this data to the page template.

---

# Adding a Template

Create

```
app/

about/

page.latte
```

Example

```latte
<h1>{$title}</h1>

<p>

Nova Version

{$version}

</p>
```

Visit

```
/about
```

Nova renders the template automatically.

---

# Understanding the Flow

When you visit

```
/about
```

Nova performs these steps.

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

about/page.php

      │

      ▼

Returns Data

      │

      ▼

about/page.latte

      │

      ▼

HTML

      │

      ▼

Browser
```

Everything happens automatically.

---

# Adding Styles

Create

```
page.scss
```

Example

```scss
h1{

    color: royalblue;

}
```

Nova automatically loads this stylesheet for the page.

No registration required.

---

# Adding TypeScript

Create

```
page.ts
```

Example

```ts
console.log("About page loaded.");
```

Nova automatically includes the script.

---

# Complete Page Structure

```
about/

page.php

page.latte

page.ts

page.scss
```

This is the most common structure for a page.

---

# Which Files Are Required?

| File | Required |
|------|----------|
| page.php | ✅ Yes |
| page.latte | Optional |
| page.ts | Optional |
| page.scss | Optional |

Nova loads optional files only when they exist.

---

# Returning Different Responses

A page is not limited to HTML.

For example

```php
return json([

    'status' => 'ok'

]);
```

Or

```php
return redirect('/dashboard');
```

Or

```php
abort(404);
```

A page can return any valid Nova response.

---

# Dynamic Routes

Create

```
app/

users/

[id]/

page.php
```

Nova automatically creates

```
/users/{id}
```

Visit

```
/users/15
```

---

# Reading Route Parameters

Use the helper

```php
route('id');
```

Example

```php
$id = route('id');

return [

    'id'=>$id

];
```

Template

```latte
<h1>

User

{$id}

</h1>
```

Visiting

```
/users/25
```

Displays

```
User 25
```

---

# Multiple Parameters

Folders

```
products/

[category]/

[product]/

page.php
```

Matches

```
/products/books/php
```

Read values

```php
route('category');

route('product');
```

---

# Catch-All Routes

Create

```
docs/

[...slug]/

page.php
```

Matches

```
/docs

/docs/php

/docs/php/framework

/docs/php/framework/routing
```

Retrieve

```php
$segments = route('slug');
```

Returns

```php
[

'php',

'framework',

'routing'

]
```

---

# Optional Catch-All

Create

```
blog/

[[...slug]]/

page.php
```

Matches

```
/blog

/blog/php

/blog/php/framework
```

---

# Best Practices

✅ Keep business logic inside `page.php`

✅ Keep presentation inside `page.latte`

✅ Keep JavaScript inside `page.ts`

✅ Keep styling inside `page.scss`

Each file should have a single responsibility.

---

# Common Mistakes

## Creating Controllers

Nova doesn't use controllers.

Everything belongs to the page.

---

## Registering Routes

Nova discovers routes automatically.

There is no route registration.

---

## Mixing HTML and PHP

Avoid

```php
echo "<table>...";
```

Prefer

```latte
page.latte
```

Templates are easier to maintain.

---

# Behind the Scenes

When Nova starts, it scans

```
app/
```

Every folder containing

```
page.php
```

becomes a route.

The routing manifest is cached in production, so discovery happens only when necessary.

---

# Challenge

Create the following pages yourself.

```
/

About

Contact

Dashboard

Products

Users
```

Try adding:

- page.latte

- page.scss

- page.ts

Observe how Nova discovers each file automatically.

---

# Chapter Summary

You learned:

- How Nova creates routes
- Why `page.php` is required
- Returning data
- Rendering templates
- Dynamic routing
- Route parameters
- Page assets

You can now create complete pages without writing a single route definition.

---

# What's Next?

In the next chapter you'll learn about **Layouts**.

You'll build a shared application shell with a header, navigation, sidebar and footer that multiple pages can inherit automatically.

By the end of the next chapter you'll understand how Nova eliminates duplicated HTML while keeping each page focused on its own responsibility.