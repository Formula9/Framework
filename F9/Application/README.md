# The AppFactory and Application Classes

The **AppFactory** and **Application** classes handle the configuration and execution, respectively, of the application. 
After the initial boot sequence, AppFactory::make is used to configure the environment required by the Application, then
instantiate the new Application and return a reference.
   
## AppFactory

The following code is an example of minimal booting for the AppFactory:

	$paths = include 'paths.php';

	// load optional application functions
	include_once $paths['boot'] . 'app.php';
	
	// load optional debugging tools
	include_once $paths['boot.assets'] . 'debug.php';
	
	// the autoloader
	include_once $paths['vendor'] . 'autoload.php';

	// load the environment settings
	(new \Dotenv\Dotenv(__DIR__))->overload();

	// make the application
	$app = AppFactory::make($paths);
   
	$app->run();
	
Subsequent calls to AppFactory::make() return a reference to the previously created Application object.
