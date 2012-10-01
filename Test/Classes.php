<?php

namespace Models;

use Freeform\Model;

class Post extends Model
{
  static $belongs_to = array(
    'user',
    'author' => 
      array(
        'class' => 'Models\User',
        'local_key' => 'user_id',
        'foreign_key' => 'id'
      ),
  ); 

  static $has_many = 'replies'; 
  
  static $many_to_many = 'tags';

  static $validate = array(
    'user_id' => array(
      'integer',
      'not_empty',
    ),
    'title' => array(
      'not_empty',
    )
  );
}

class Reply extends Model
{
  static $belongs_to = array('post', 'user');
}

class User extends Model
{
  static $has_many = array('posts', 'replies');
  static $has_one = 'profile';
}

class Tag extends Model
{
  static $has_and_belongs_to_many = 'Post';
}

class Profile extends Model
{
  static $belongs_to = 'user';
}