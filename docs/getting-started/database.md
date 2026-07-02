# Working with Databases

> Learn how Nova communicates with databases using its lightweight database engine.

---

# What You'll Learn

In this chapter you'll learn:

- Configuring database connections
- Running queries
- Query Builder
- Transactions
- Stored Procedures
- Raw SQL
- Database helpers
- Best practices

By the end of this chapter you'll be comfortable building data-driven applications with Nova.

---

# Why Doesn't Nova Use a Heavy ORM?

Many frameworks encourage developers to think in objects.

```php
$user = User::find(1);
```

While convenient, large ORMs can introduce:

- Hidden queries
- Performance overhead
- Complex relationships
- Difficult debugging

Nova takes a different approach.

**The database is already your data model.**

Nova gives you a lightweight database engine that stays close to SQL while reducing repetitive code.

---

# Database Configuration

Open

```text
config/database.php
```

Example

```php
return [

    'default' => 'mysql',

    'connections' => [

        'mysql' => [

            'driver' => 'mysql',

            'host' => env('DB_HOST'),

            'port' => env('DB_PORT'),

            'database' => env('DB_DATABASE'),

            'username' => env('DB_USERNAME'),

            'password' => env('DB_PASSWORD')

        ]

    ]

];
```

Most applications only need to configure this once.

---

# Environment Variables

Database credentials belong in `.env`.

```dotenv
DB_DRIVER=mysql

DB_HOST=127.0.0.1

DB_PORT=3306

DB_DATABASE=nova

DB_USERNAME=root

DB_PASSWORD=
```

Never commit production credentials to Git.

---

# Selecting Data

The simplest query.

```php
$users = db()

    ->select(

        "SELECT * FROM users"

    );
```

Returns an array of records.

---

# Selecting One Record

```php
$user = db()

    ->first(

        "SELECT * FROM users WHERE id = ?",

        [10]

    );
```

Returns a single row or `null`.

---

# Scalar Values

Useful for counts and totals.

```php
$total = db()

    ->scalar(

        "SELECT COUNT(*) FROM users"

    );
```

---

# Query Builder

Nova includes a lightweight Query Builder.

Example

```php
$users = db()

    ->table('users')

    ->where('status', 'ACTIVE')

    ->orderBy('name')

    ->get();
```

No ORM required.

---

# Selecting Columns

```php
$users = db()

    ->table('users')

    ->select([

        'id',

        'name',

        'email'

    ])

    ->get();
```

---

# Filtering

```php
$users = db()

    ->table('users')

    ->where('department', 'Sales')

    ->where('status', 'ACTIVE')

    ->get();
```

---

# OR Conditions

```php
$users = db()

    ->table('users')

    ->where('status', 'ACTIVE')

    ->orWhere('status', 'PENDING')

    ->get();
```

---

# WHERE IN

```php
$users = db()

    ->table('users')

    ->whereIn(

        'id',

        [1,2,3,4]

    )

    ->get();
```

---

# Sorting

```php
db()

->table('users')

->orderBy('name')

->get();
```

Descending

```php
->orderByDesc('created_at')
```

---

# Limiting Results

```php
db()

->table('users')

->limit(20)

->offset(40)

->get();
```

---

# Inserting Data

```php
db()

->insert(

'users',

[

'name'=>'John',

'email'=>'john@example.com'

]

);
```

---

# Updating Data

```php
db()

->update(

'users',

[

'status'=>'ACTIVE'

],

[

'id'=>10

]

);
```

---

# Deleting Data

```php
db()

->delete(

'users',

[

'id'=>10

]

);
```

---

# Raw Statements

Execute any SQL.

```php
db()

->statement(

"TRUNCATE TABLE users"

);
```

---

# Stored Procedures

Nova fully supports stored procedures.

Example

```php
$result = db()

->procedure(

'sp_users',

[

10,

'ACTIVE'

]

);
```

Returned value

```php
$result['sets'];

$result['first'];

$result['out'];
```

Perfect for enterprise applications.

---

# Transactions

Wrap multiple operations.

```php
db()

->transaction(function($db){

    $db->insert(...);

    $db->update(...);

});
```

If an exception occurs,

Nova automatically rolls back.

---

# Manual Transactions

```php
db()->begin();

try{

    ...

    db()->commit();

}catch(Throwable){

    db()->rollback();

}
```

---

# Multiple Connections

Example

```php
db('reporting')

->table('sales')

->get();
```

Nova automatically switches connections.

---

# Error Handling

Database exceptions become

```php
DatabaseException
```

Catch

```php
try{

}catch(DatabaseException $e){

}
```

---

# Query Logging

Enable

```dotenv
DB_LOG_QUERIES=true
```

Nova logs

- SQL
- Bindings
- Execution Time

Useful during development.

---

# Using Databases in Pages

Example

```php
return [

'users'=>db()

->table('users')

->get()

];
```

Template

```latte
<ul>

{foreach $users as $user}

<li>

{$user['name']}

</li>

{/foreach}

</ul>
```

---

# Using Databases in Server Actions

```php
validate([

'name'=>'required'

]);

db()

->insert(

'users',

[

'name'=>request('name')

]

);

return success();
```

---

# Best Practices

✅ Use Query Builder for common queries.

✅ Use stored procedures for complex business logic.

✅ Always validate input.

✅ Wrap related operations in transactions.

✅ Keep SQL close to the feature that uses it.

---

# Common Mistakes

## Running Queries in Templates

Avoid

```latte
{foreach db()->table('users')->get() as $user}
```

Query the database inside `page.php` or `component.php`, then pass the data to the template.

---

## Ignoring Transactions

If multiple operations must succeed together, always use a transaction.

---

## Building SQL Strings

Avoid string concatenation.

```php
$sql = "SELECT * FROM users WHERE id = ".$id;
```

Instead, use bindings.

```php
db()->first(

"SELECT * FROM users WHERE id = ?",

[$id]

);
```

---

# Behind the Scenes

```
Page

↓

Query Builder

↓

Database Manager

↓

Connection

↓

PDO

↓

Database

↓

Result

↓

Page

↓

Template
```

Nova keeps the path simple and predictable.

---

# Challenge

Create a `users` table and build a page that:

- Lists all users
- Adds a new user
- Updates an existing user
- Deletes a user

Use:

- `page.php`
- `page.latte`
- `actions/save.php`
- `actions/delete.php`

---

# Chapter Summary

In this chapter you learned:

- Database configuration
- Query Builder
- Raw SQL
- Inserts, updates, and deletes
- Transactions
- Stored procedures
- Query logging

You can now build data-driven applications using Nova's lightweight database engine.

---

# Next Chapter

➡ **Authentication**

In the next chapter you'll learn how Nova authenticates users, protects routes, authorizes actions, manages sessions, and implements secure login and logout workflows without unnecessary complexity.