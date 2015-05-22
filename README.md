# Twist - Ez win, Ez life

A microframework for PHP, easy to set up, easy to use.


## Slash templating

Twist has its custom templating engine called 'Slash'. It's designed to compile mostly
down to native PHP, therefor being super fast.

### Syntax

```php
// Simple echo statement:
{{ 'Hey I am using slash' }}

// Comment (multiline, there is no single line comment)
{# Hey I am a comment #}

```

Now for some logic statements:

```php
// For loop
{% for $i in 300 %}

( the above becomes <?php for $i = 1; $i <= 300; $i++): ?> )

// Foreach loop
{% for $i in $results %}

// while loop
{% while $counter-- %}

// If statement
{% if $counter == 0 %}

{% elseif $counter == 2 %}

{% else %}

```

All of the above should be ended with a `{% end %}` tag. For example:

```php
{% for $user in $users %}

	You are user {{ $user->id }} with the username {{ $user->username }} and your date of birth is {% $user->dob %}

	{% if $user->admin === true %}

		You are also an admin. Congratulations.

	{% end %}

{% end %}
```

### The Good stuff, inheritance.

When using slash, you may take advantage of its inheritance abilities. You can define blocks in templates, and extend them.

A simple example:

(`app.slash`)
```php
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>{% yield 'title', 'Twist PHP - Framework for lazy people' %}</title>
	<link rel="stylesheet" href="css/style.css">
	{% yield 'extra-style' %}
</head>
<body>
	<div class="sidebar">
		{% yield 'sidebar' %}
	</div>

	<div class="content">
		{% yield 'content' %}
	</div>
</body>
</html>
```

Say this is your base layout for your wonderful site. Now we can extend this layout, and use all of its logic in a breeze.

(`home.slash`)
```php
{% extends 'app' %}

{% block 'title', 'Homepage - Twist PHP' %}

{% block 'sidebar' %}
	This is our wonderful sidebar. It has, however, nothing in it.
{% end %}

{% block 'content' %}
	This is our main content. Here you will be able to find some Lorem ipsum dolor sit amet, consectetur adipisicing elit. Sint delectus voluptatibus sunt itaque dolorem accusamus saepe in ex vitae. Minus?
{% end %}
```

Looks cool right? Let's make a marketing page, with the exact same content, but with a different stylesheet instead!

(`marketing.slash`)
```php
{% extends 'home' %}
{% block 'extra-style', '<link rel="stylesheet" href="css/marketing.css">' %}
```

Alright, so now that you're all excited, let's get to it now.

## How to use Slash?

Well first of, you have to clone this repo. Slash is currently not a standalone repo.

I assume you know how to do such things. So to continue, on your landing page, create a new Twist\Slash\SlashCompiler instance

like so:

```php

$compiler = new Twist\Slash\SlashCompiler();
```

Then, create a Twist\Slash\Slash instance and pass in the compiler and a path to a cache directory. Make sure this directory is writeable, slash will write its cache files to it.
It is recommended to make all of your paths absolute.
You can do so with realpath(); Directories should not have a trailing slash.
```php

$slash = new Twist\Slash\Slash($compiler, realpath(__DIR__).'/compiled');
```

You are now ready to create your first view, but before we do so, let's define a place where we store those things.
Again, it is recommended you use absolute paths for this.

```php
$views = realpath(__DIR__) . '/views';
```

This creates a path to the directory views within the directory of file file you're working in.

Now for creating and naming your views. There are a few things you should keep in mind:

* All slash views must have the extension .slash
* Instead of a / to go a level down, into a directory, we use a . (so `layout.app` will be `layout/app.slash`)

Now you are ready to make your first view. You can do so using the examples given above. Once you're done, you can get the contents of your view by using the make method on the Slash instance.

```php

echo $slash->make($views . '/home');

```

If you called your view home, this should print out the rendered version of your view. You may also notice that there is a new file in your cache directory. This is the rendered version of the view you just wrote in slash.
So if you're curious how slash works, and don't want to crack your brain on the regular expressions, just write a view, and look at the compiled version. You'll understand it in a second.