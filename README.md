STI
===
This extension provides realization of **single table inheritance** 

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist bigdropinc/yii2-sti "*"
```

or add

```
"bigdropinc/yii2-sti": "*"
```

to the require section of your `composer.json` file.


Usage
-----

You can implement STI into your models by following with two steps:

* Add STI column (by default type) into database
* Extend your model from  ```bigdropinc\sti\ActiveRecord```

More detailed usage can be found below.

###Create a migration

Firstly to implement STI into database you should have STI column in database table. 
By default it named **type**, but you can choose whatever you want. 
Also you may want to add an index for this column.
Your migration will looks like code below. 

```
    public function safeUp()
    {
        $this->addColumn(User::tableName(), 'type', $this->string(20));
        $this->createIndex('idx-users-type', User::tableName(), 'type');
    }

    public function safeDown()
    {
        $this->dropColumn(User::tableName(), 'role');
    }
```

###Active Record

Model witch should implement STI should extends from ```bigdropinc\sti\ActiveRecord```. 
The better way is to create an base ActiveRecord class for whole your project and extend all models from it.
  
  ```
  <?php 
  
  namespace common\models;
  
  class ActiveRecord extends \bigdropinc\sti\ActiveRecord
  {
  
  }
  ```
Now STI mechanism will automatically switch on if STI column was found.
To disable this behavior you may override ```protected static function isStiEnabled()``` method.
You may set default value of STI column by overriding ```protected static function getStiColumn()```

By default STI column is a string and it's contain a class name. 
You may override ```protected static function getStiValue($className = null)``` to change this behavior
  
  

### Finding models

For example you have some STI classes

```
class User extend bigdropinc\sti\ActiveRecord
{
}
```

```
class Client extend User
{
}
```

```
class Manager extend User
{
}
```

You can simply save any instance of this models and STI column will be automatically saved into database.

By calling ```User::find()``` your will get models of all types *User*, *Client* and *Manager*
Each record will be populated into an appropriate class according it STI column (type)

By calling ```Client::find()``` or ```Manager::find()``` you will get only models of certain class. 


### ActiveQuery

Mechanism of finding records using the STI is implements by ```bigropinc\sti\ActiveQuery``` class.
If you need to use your own ActiveQuery class with some logic with STI you should follow two simple steps:
  * Extend your ActiveQuery class from ```bigdropinc\sti\ActiveQuery```
  * Override ```protected static function getActiveQuery()``` in model you need and return your ActiveQuery class name

###Becomes

You can convert any model according STI scheme as in example below.
 
```

$user = User::findOne($id);
$client = $user->becomes($client::class);

```

Pay attention that after one object becomes to another it should not be used anymore.