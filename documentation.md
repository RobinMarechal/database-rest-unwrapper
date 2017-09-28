#Documentation

### **First step:**

For each table in your database, you should create a Model and a Controller class.
For example, if you have a table names `users`, you should have a model named `User`, and a controller named `UsersController` (the `s` is important. Later, it will be possible to remove it with a configuration file).

You're not forced to create a model and a controller for your pivot tables, unless you want to retrieve data from them, or insert/update data. 

### **GET requests:**

As an RESTful API, you can retrieve data from the database using a HTTP GET request.
All your request should be preceded by `/api/` in your URL.
For example, if you have a table named `users`, you should one of these request:

-	Get all users
```
yourdomain.com/api/users/ 
```
-	Get the user with the id *{user_id}*
```
yourdomain.com/api/users/{user_id}
```
-	Get related data of the user with id *{user_id}*
```
yourdomain.com/api/users/{user_id}/{relation_name}
```
For the last one, imagine you have a 1:n or n:n relation with a table named `articles`, to get all the articles that belongs to the user of id 5, the URL should be;
``` 
yourdomain.com/api/users/5/articles
```

#### **URL parameters:**

The package allows you to pass additionnal parameters to the URL:
``` 
.../api/users/{id?}/{relation?}?param1=value1&param2=value2&...
```

#### **The list of all the parameters your can use:**

**Ordering:**

You add an OrderBy clause to your SQL request.
```
/api/users/{id?}/{relation?}?orderby=firstname
```
You can, of course, order it descending, or ascending:
```
/api/users/{id?}/{relation?}?orderby=firstname&order=DESC
/api/users/{id?}/{relation?}?orderby=firstname&order=ASC
```
But you can also specify both in the parameter `orderby`:

```
/api/users/{id?}/{relation?}?orderby=firstname,DESC
/api/users/{id?}/{relation?}?orderby=firstname,ASC
```


**Limit & Offset:**

If you want the get only 5 rows:

```
/api/users/{id?}/{relation?}?limit=5
```
You can specify an offset:
```
/api/users/{id?}/{relation?}?limit=5&offset=15
```
But, as above, you can also specify both in the parameter `limit`:

```
/api/users/{id?}/{relation?}?limit=5,15
```


**From & To:**

You can retrieve data from a certain data and/or to a certain date.

First of all, you should specify on which field the request whould refer to. 
To do so, add the the `public` attribute `$temporalField` to your models.

For example, in the `User` model, you should maybe add the line:
```
public $temporalField = 'created_at'
```

So the request will be based on the `created_at` column of the `users` table

 *⇒ If you have another date/datetime column in your table, you can of course use it.*

Then, you will be allowed to perform the following requests:

```
/api/users?from={date}
/api/users?to={date}
/api/users?from={date}&to={date}
```

This uses `nestbot/carbon` package's date management. This means that the date needs to be parsable by the `Carbon` class. Basically, the format is `yyyy-mm-dd`, or `yyyy-mm-dd HH:ii:ss`. But, this also means that you can use all the format that can be parsed by this package, for example:

```
/api/users?from=first monday of june 2017
/api/users?from=first monday of september 2016&to=first monday of september 2017
```

**Conditions (Where):**

You can pass additional conditions to your request, using the `where` parameter. 
For example, if you want all users that are older than 20:
```
...&where=age > 20
```

You can add multiple conditions, separated by a `;`:

```
...&where=age > 20;country = "FRANCE"
```

Also, you can use SQL function in the `where` parameters, such as:

```
...&where=age > 20;country = "FRANCE";CONCAT(firstname," ", lastname) = "John Doe"
```

**Distinct:**
If you want to perform a `SELECT DISTINCT` query, you should set the value of the parameter `distinct` to `true`:
```
...&distinct=true
```

**Selecting only some fields, and/or apply SQL functions (SELECT):**

You may want to get only one, or some columns. It's possible using the parameter `select`. For example, if you want to get only the columns `firstname` and `lastname`:
```
...&select=firstname;lastname
```
This will return:
```
[
	{
		"firstname": "...",
		"lastname": "...",
	},
	{
		"firstname": "...",
		"lastname": "...".
	},
	...
]
```

It's possible to alias a field. To do this, just add `{alias}=` before the field. For example:

```
...&select=fn=firstname;lastname
```
Will return:
```
[
	{
		"fn": "...",
		"lastname": "...",
	},
	{
		"fn": "...",
		"lastname": "...".
	},
	...
]
```

Finally, it's possible to use SQL functions in your `SELECT` clause.  Here are some examples:
```
...&select=*;concat(firstname, " ", lastname)
...&select=nb_of_users=count(*)
...&select=id;name=concat(firstname, " ", lastname)
```

**Getting related data:**

It's possible to retrieve data with relations. For example, in Laravel, were use the `with()` method on the query builder. It's possible to do so, using the `with` parameter.

For example, if you want to get the users, with their posts:
```
.../api/users?with=posts
```

You can also ask for multiple relations, separated with a `;`, or nested relations, like in Laravel:
```
.../api/users?with=posts.comments;posts.likes;permissions
```

Of course, following this example, you should have the relations `posts` and `permissions` in the `User` model, `comments` and `likes` in the `Post` model:

```
class User extends Model{
	
	public function posts()
	{
		return $this->hasMany('App\Post');
	}

	public function permissions()
	{
		return $this->hasMany('App\Permission');
	}
}

class Post extends Model{

	public function comments()
	{
		return $this->hasMany('App\Comment');
	}

	public function likes()
	{
		return $this->hasMany('App\Like');
	}
}
```

> The relations wanted will work provided that you wrote the relation
> functions in your models


 Unfortunately, the `select` parameter works pretty bad with the `with` parameter. You shouldn't select only some parameters with a `with` parameter, it will not select the other fields, even in the relations. Moreover, selecting in related table (eg: `posts.title`) doesn't work at the moment.
 
However, you can perform this kind of query:
```
.../api/users?with=posts.comments&select=*,name=concat(firstname," ",lastname)
```
That will get all the users, with their posts, with their comments. It will select the result of the function CONCAT, aliased as `name`, in addition to all fields, of all the concerned tables (thanks to `*`)