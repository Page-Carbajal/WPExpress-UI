<?php


namespace WPExpress\UI;


use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;
use Twig_Loader_Filesystem;
use Twig_Environment;



final class RenderEngine
{
    protected $type;
    protected $typeExtension;
    protected $templatePath;
    protected $templateFolder;
    protected $useTypeAsExtension;

    public function __construct($templateFolderPath, $type = 'mustache', $useTypeAsExtension = true)
    {
        $this->type = trim(strtolower($type));
        $this->useTypeAsExtension = $useTypeAsExtension;
        $this->typeExtension = sanitize_title($this->type); // TODO: Port this function to Plain PHP
        $this->setTemplatePath( $templateFolderPath )->createDirectoryStructure();
    }

    public function setTemplatePath($templateFolderPath)
    {
        if( ( !empty($templateFolderPath) ) && file_exists( $templateFolderPath ) ){
            $this->templateFolder = $templateFolderPath;
        }else{
            throw new \Exception( 'Error: Template path does not exist! - WPExpress/UI @ RenderEngine' );
        }

        return $this;
    }

    public function setTypeAsExtension($flag)
    {
        $this->useTypeAsExtension = $flag;
    }

    private function createDirectoryStructure()
    {
        $success = true;

        // Create the directory <path-to-template>/mustache/partials
        $partialsPath = $this->getBaseDirectory() . '/partials/';
        if( !file_exists( $partialsPath ) ){
            $success = $this->makeDirectoryFromPath( $partialsPath );
        }
        $cachePath = $this->getBaseDirectory() . '/cache/';
        if( !file_exists( $cachePath ) ){
            $success = $this->makeDirectoryFromPath( $cachePath );
        }

        if(!$success){
            throw new \Exception( 'Error: Cant write in the specified template path. Check for permissions. - WPExpress/UI @ RenderEngine' );
        }

        return $this;
    }

    // This function is a verbatim copy of WordPress wp_is_stream
    private function isURLStream( $path )
    {
        $wrappers = stream_get_wrappers();
        $wrappers_re = '(' . join('|', $wrappers) . ')';

        return preg_match( "!^$wrappers_re://!", $path ) === 1;
    }

    // This function is a verbatim copy of WordPress wp_mkdir_p
    private function makeDirectoryFromPath($target )
    {

        $wrapper = null;

        // Strip the protocol.
        if( $this->isURLStream( $target ) ) {
            list( $wrapper, $target ) = explode( '://', $target, 2 );
        }

        // From php.net/mkdir user contributed notes.
        $target = str_replace( '//', '/', $target );

        // Put the wrapper back on the target.
        if( $wrapper !== null ) {
            $target = $wrapper . '://' . $target;
        }

        /*
         * Safe mode fails with a trailing slash under certain PHP versions.
         * Use rtrim() instead of untrailingslashit to avoid formatting.php dependency.
         */
        $target = rtrim($target, '/');
        if ( empty($target) )
            $target = '/';

        if ( file_exists( $target ) )
            return @is_dir( $target );

        // We need to find the permissions of the parent folder that exists and inherit that.
        $target_parent = dirname( $target );
        while ( '.' != $target_parent && ! is_dir( $target_parent ) ) {
            $target_parent = dirname( $target_parent );
        }

        // Get the permission bits.
        if ( $stat = @stat( $target_parent ) ) {
            $dir_perms = $stat['mode'] & 0007777;
        } else {
            $dir_perms = 0777;
        }

        if ( @mkdir( $target, $dir_perms, true ) ) {

            /*
             * If a umask is set that modifies $dir_perms, we'll have to re-set
             * the $dir_perms correctly with chmod()
             */
            if ( $dir_perms != ( $dir_perms & ~umask() ) ) {
                $folder_parts = explode( '/', substr( $target, strlen( $target_parent ) + 1 ) );
                for ( $i = 1; $i <= count( $folder_parts ); $i++ ) {
                    @chmod( $target_parent . '/' . implode( '/', array_slice( $folder_parts, 0, $i ) ), $dir_perms );
                }
            }

            return true;
        }

        return false;

    }

    public function getBaseDirectory()
    {
        return untrailingslashit($this->templateFolder);
    }

    private function parseFileName($fileName)
    {
        return  ( $this->useTypeAsExtension ? "{$fileName}.{$this->typeExtension}" : $fileName );
    }

    public function getTemplatePath( $fileName )
    {
        $pathToFile = trailingslashit( $this->getBaseDirectory() ) . $this->parseFileName($fileName) ;
        if( file_exists( $pathToFile ) ){
            return $pathToFile;
        }

        return false;
    }

    public function renderTemplate($fileName, $context)
    {
        if( $template = $this->getTemplatePath( $fileName ) ){
            switch($this->type){
                case "twig":
                    $raw = $this->renderTwigTemplate( $fileName, $context );
                    break;
                default:
                    $raw = $this->renderMustacheTemplate( $fileName, $context );
                    break;
            }
            return $raw;
        }

        // TODO: Improve this message
        return "<strong>Not it!</strong>";
    }

    private function renderMustacheTemplate($fileName, $context)
    {
        $options = array();
        $options['cache'] = $this->getBaseDirectory() . '/cache';
        $options['loader'] = new Mustache_Loader_FilesystemLoader( $this->getBaseDirectory() );
        $options['partials_loader'] = new Mustache_Loader_FilesystemLoader( $this->getBaseDirectory() . '/partials' );
        $options['charset'] = 'UTF-8';

        if( function_exists( 'apply_filters' ) ){
            // Prevent the use of WordPress specific functions
            $options = apply_filters( 'wpex_set_mustache_engine_options', $options );
        }

        $mustache = new Mustache_Engine($options);


        return $mustache->render( $fileName, $context );
    }

    private function renderTwigTemplate($fileName, $context)
    {
        $loader = new Twig_Loader_Filesystem( $this->getBaseDirectory() );
        $twig = new Twig_Environment( $loader, array( 'cache' => $this->getBaseDirectory() . '/cache' ) );
        $fileName = $this->parseFileName( $fileName );
        $template = $twig->loadTemplate( $fileName );
        return $template->render( $context );
    }

}