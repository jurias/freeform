freeform
========

A simple Active Record implementation of an ORM, with relations and finders, focused on being ridiculously easy to use.

Ex.
```
<?php 

include 'Freeform\Autoloader.php';

$config = array('database' => 'db', 'user' => 'user', 'password' => '123');
Freeform\Freeform::connect($config);

class User extends Freeform\Model 
{
}

$user = new User();
$user->name = 'Bob';
$user->save();

User::find(array('name' => 'Bob'));