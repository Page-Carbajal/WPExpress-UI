# WPExpress-UI

HTML Builder and Render Engine for WPExpress.
 

##Change Log


###TO DO


- Create Formatting class a port of WordPress functions to sanitize titles and file names


###Version 0.5.1 - Fixed: Exception on renderMustacheTemplate

- Added exception for cache directory on renderTwigTemplate method
- Added warning if missing partials directory
- Added warning if missing cache directory
- Fixed createDirectoryStructure exception
- Adopted [Semantic Versioning](http://semver.org)


###Version 0.5

- Fixed errors on the RenderEngine class
- Made the RenderEngine class final
- Simplified the constructor
- Added two error throwing exceptions
- Added the RenderEngine/setTemplatePath method 
- Added the RenderEngine/createDirectoryStructure method
- Remove WordPress specific functions dependencies

###Version 0.4

- Last stable version