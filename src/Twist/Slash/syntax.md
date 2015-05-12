##Some ideas for syntax


**Number**|**View**|**Becomes**
----------|--------|-----------
1|`{% for $key : $value in $array %}` | `<?php foreach ($array as $key => $value): ?>`
2|`{% for $book in $books %}` | `<?php foreach ($books as $book): ?>`
3|`{% for $i in 3000 %}` | `<?php for ($i = 0; $i <= 3000; $i++): ?>`
4|`{{{ $name }}}` | `<?php echo htmlentities($name, ENT_QUOTES, "UTF-8"); ?>`
5|`{{ $name }}` | `<?php echo $name; ?>`
6|`$books.length` | `count($books)`
7|`{# Comment! #}` | Won't show up
8|`{% if $books.length > 10 %}` | `<?php if (count($books) > 10): ?>`
9|`{% if $books.length %}` | `<?php if (count($books)): ?>`
10|`{{ $name or 'Max' }}` | `<?php echo isset($name) ? $name : 'Max'; ?>`
11| `{% forif %} and {% or %}` | For loop, except if there is nothing to loop, the {% or %} block will be run.

##Notes
1. Foreach loop.
2. Foreach loop without keys.
3. For loop, possible becouse PHP variables can't start with number.
4. Escaped echo
5. regular echo
6. shortcut for count function for arrays / objects.
7. Comment, doesn't show up in result
8. If statement
9. If statement without operator
10. Ternary operator to have a fallback
11.
```
{% forif result in results %}
	{{ result }}
{% or %}
	There are no results :(
```

Would be:

```php

<?php if (! empty($results)) foreach ($results as $result): ?>
	<?php echo $result; ?>
<?php endforeach; ?>
<?php else: ?>
	There are no results :(
<?php endif; ?>
```

## Indentations

If I can pull it of, I'll try to make it simular to python, where unindenting ends a statement:

```
{% for $i in 20 %}
	Hey I am number {{ $i }}
	This is the {{ $i }}<sup>th</sup> iteration

{% for $title : context in books %}
	<div class="title">{{ $title }}</div>
	<div class="context">
		{% for $key : $value in $context %}
			<div class="{{ key }}">
				{{{ $context }}}
			</div>
	</div>
```

In PHP code, this would be:

```php
<?php for ($i = 0; $i <= 20; $i+=): ?>
	Hey I am number <?php echo $i; ?>
	This is the <?php echo $i; ?><sup>th</sup> iteration
<?php endfor; ?>

<?php foreach ($books as $title => $context): ?>
	<div class="title"><?php echo $title; ?></div>
	<div class="context">
		<?php foreach ($context as $key => $value): ?>
			<div class="<?php echo $key; ?>">
				<?php echo htmlentities($context, ENT_QUOTES, "UTF-8"); ?>
			</div>
		<?php endforeach; ?>
	</div>
<?php endforeach; ?>
```

Possible will have something to specify an indentation pattern at runtime or when creating the compiler:

```
---- beginning of the page ----
{% indent '\t| {4}' %}

```

```php
$compiler = new Slash\Compiler();
$compiler->setIndentPattern('...');
```

Default will be, like the example above, a tab or 4 spaces.


##Furthermore

Every operaion statement will be {% stuff %},
every comment will be {#  #} and
every print statement will be {{  }}

The whitespace directly after the { and right before } should not matter, it's optional and just for
making it look clean.