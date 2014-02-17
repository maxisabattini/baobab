
Objects
=======

### Application

This is a main class. 

General:

	- getInstance()
	- config()

Url and Path:

	- getPath()
	- getUrl()		

Route:

	- getRouteUrl()
	- getRouteParams()	
	- route()
	
View & Controller support:

	- render()
	
Dependencies:

    - getUri()
    - getRouter()
    - getRequest()
    - getResponse()

### Param

This class implement a functionality of common parameter object, and is a optional replacement of stdClass or Array.

Methods:

    - has( name )
    - get( name )    //TODO  if is callable return a result
    - set( key, value ) //TODO
	- toArray()
	- toObject()
	- merge( Array | Object )

### Uri

Hold a complete information of a uri resource.

### Router

Methods:

    - map()
    - get()
    - post()
    - delete()
    - put()
    - patch()
    - getRouteByName()
    - getMatched()
    - getLatestMatched()


### Route

Handle a route from a url given.

Properties:

    - pattern
    - action
    - name
    - params
    - methods ( GET, POST, PUT )

Methods:
    
    - matches( url )
    - makeUrl( params )

### Config

Methods:

	- getInstance()	
	- loadFile()
	- loadArray()   //TODO
	- loadObject()  //TODO
	- has()
	- get()
	- set()
	- drop()

### Controller

Methods:

	- setView( File )
	- render()
	- getVar()
	- getVars()
	- setVar()

### Cache

Memcache wrap class.

Methods:

    - getInstance()
    - has()
	- get()
	- set()
	- drop()

### Session

Session wrap class.

Methods:

    - getInstance()
    - has()
	- get()
	- set()
	- drop()

	
### Cli

Methods:

    - hasParam()
    - getParam()
    - getParams()
    
    - title()   //aka h3
    - line()    //aka br
    - comment() //aka p

    - info()    //aka label 
    - error()   //aka error
    
    - choose()
    - confirm()
    
    - input()
    - inputSecret()
    
    - run()
    - runSilent()

### Log

Methods:

    - info( value)
    - notice( value)
    - warn( value)
    - error( value )
    - debug( value )
    
### LogOutput

Interface

    - lprint( value , level)
    
### Request

Methods:

    - isGet()
    - isPost()    
    - isPut()
    - isDelete()
    
    - isAjax()
    - isSecure()
    
    - getMethod()    
    - getHeader()
    - getVar()
    
    - hasFiles()
    - getFiles()    
    - getServerAddress()
    - getClientAddress()
    - getAcceptableContent()
    
### Response

Methods:

    - getHeader()
    - getHeaders()
    - setContent()
    - setHeader()
    - redirect()
    - setExpires()
    
    

## TODO

    
Cookies

    - has( name )
    - get( name ) 
    - set( name, value )
    
Message

    - _( key, placeholders )
    - get( key, placeholders )
    - exists( key )

The same Config system for each lang, in a messages folder for default.
In the views is $msg->_()


### Others things TODO

Support for optional parameters for routes.
Define public members of Route class.
Implement Response::apply() method.
Move resposabilities from Uri class to Request.
Implement Parameters class toString.
Finish a Cli class refactor.
Better View default parameters system. Use comments maybe.