<?php

include_once "C:\wamp\www\Freeform\Autoloader.php";
include_once "C:\wamp\www\Freeform\Test\Classes.php";

Freeform\Freeform::connect(array('database' => 'orm_test'));

test('instantiating model', function() {
  $post = new \Models\Post;

  test('should have properties from database schema', function() use($post) {
    $properties = array('id', 'user_id', 'title', 'body', 'created', 'modified');
    verify(array_keys(get_object_vars($post)))->is($properties);
  });

  test('should have null values', function() use($post) {
    verify($post->id)->is(null);  
  });
});

test('find', function(){
  test('with no arguments', function(){
    $result = \Models\Post::find();

    test('should return array', function() use($result) {
      verify($result)->is_array();  
      verify(count($result))->is_greater_than(0);

      test('with results of appropriate class', function() use($result) {
        verify(get_class(pos($result)))->is('Models\Post');
      });
    });
  });

  test('with primary key argument', function(){
    $result = \Models\Post::find(2);

    test('should return object', function() use($result) {
      verify($result)->is_object();

      test('with id same as argument', function() use($result) {
        verify($result->id)->is("2");
      });
    });
  });

  test("with parameters array should return array of results", function() {
    $result = \Models\Post::find(array('id'=>2));
    verify($result)->is_array();
    verify(count($result))->is_greater_than(0);

    test("that contains the Posts of the appropriate class", function() use($result) {
      verify(get_class($result[0]))->is('Models\Post');
    });
  });

  test('should return empty array for no results', function(){
    $result = \Models\Post::find(array('id' => 'non_existent'));
    verify($result)->is_empty();
  });

});



test('crud operations:', function(){
  $post = new \Models\Post();
  $body = uniqid();
  $post->user_id = 1;
  $post->title = 'Title';
  $post->body = $body;

  test('should create new record', function() use($post){
    $result = $post->save();
    verify($result)->is_true();
  
    test('and set new id', function() use($post){
      verify((int) $post->id)->is_greater_than(0);
    
      test('and retrieve record using that id', function() use($post){
        $result = \Models\Post::find($post->id);
        verify($result)->is_object();
        verify($result->id)->is($post->id);
      });
    });
  });

  test('should create new record with array of values', function(){
    $values = array('user_id' => 2, 'body' => 'Post Body', 'title' => 'Title');
    $post = new \Models\Post($values);
    verify($post->save())->is_true();
  });

  test('should delete record', function() use($post){
    $id = $post->id;
  
    $result = $post->delete();
    verify($result)->is_true();

    test('and find should return empty for that id', function() use($id){
      $result = \Models\Post::find($id);
      verify($result)->is_empty();
    });
  });
});

test('search should return array', function(){
  $result = \Models\Post::search(array('id' => '%1%', 'user_id' => '%1%'));
  verify($result)->count()->is_greater_than(0);
});

// Validators
test('validate', function(){
  test('should return for failed save', function(){
    $post = new \Models\Post;
    $post->user_id = 'string';
    verify($post->validate())->is(false);
  });
});


test("belongs_to", function(){
  test("should return single result with parent class", function(){
    $result = \Models\Post::find(1)->user;
    verify($result)->is_class('Models\User');
  });

  test("with non-standard fields should return result", function(){
    $result = \Models\Post::find(1)->author;
    verify($result)->is_class('Models\User');
  });
});

test('complex relations example', function(){
  $result = \Models\User::find();
  $result = $result[0]->posts[0]->replies[0]->user;
  verify($result)->is_class('Models\User');
});


test("has_many", function(){
  $result = \Models\User::find(1)->posts;

  test("should return an array", function() use($result) {
    verify($result)->is_array();
    verify(count($result))->is_greater_than(0);
    
    test('with elements of child class', function() use($result) {
      verify(get_class($result[0]))->is('Models\Post');
    });
  });
});

test("has_many_through", function(){
  $result = \Models\Post::find(1)->tags;

  test("should return an array", function() use($result) {
    verify($result)->is_array();
    verify(count($result))->is_greater_than(0);
    
    test('with elements of child class', function() use($result) {
      verify(get_class($result[0]))->is('Models\Tag');
    });
  });
});