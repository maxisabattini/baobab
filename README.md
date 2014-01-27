
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

	- map()
	- get()
	- post()
	- getRouteUrl()
	- getRouteParams()
	- route()


### Param

This class implement a functionality of common parameter object, and is a optional replacement of stdClass or Array.

Methods:

    - has( name )   //TODO
    - get( key )    //TODO  if is callable return a result
    - set( key, value ) //TODO
	- toArray()
	- toObject()
	- merge( Array | Object )

### Uri

Hold a complete information of a uri resource.

### Route

Handle a route from a url given.

Properties:

    - pattern
    - params
    - methods ( GET, POST, PUT )

Methods:
    
    - matches( url )

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
	
## TODO

Log

    - log( value, level )
    
LogOutput

    - lprint( value )

-Request

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
    
Response

    - getHeader()
    - getHeaders()
    - setContent()
    - setHeader()
    - redirect()
    - setExpires()
    
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