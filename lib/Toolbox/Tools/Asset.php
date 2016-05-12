<?php

namespace Toolbox\Tools;

class Asset {

    var $scriptQueue = array( 'header' => '', 'footer' => '' );

    var $isFrontEnd = FALSE;

    var $isBackEnd = FALSE;

    var $baseUrl = '';

    public function setIsFrontEnd( $isFrontend )
    {
        $this->isFrontEnd = $isFrontend;

        return $this;
    }

    public function setIsBackEnd( $isBackEnd )
    {
        $this->isBackEnd = $isBackEnd;

        return $this;
    }

    public function setBaseUrl( $baseUrl )
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * @param array $nameArray
     * @param array $dependencies
     * @param array $params
     */
    public function appendStylesheetGroup( array $nameArray = array(), $dependencies = array(), $params = array() )
    {
        foreach( $nameArray as $name => $path )
        {
            $this->appendStylesheet( $name, $path, $dependencies, $params);
        }

    }

    /**
     * @param string $name
     * @param string $path
     * @param array  $dependencies
     * @param array  $params
     *
     * @return $this
     */
    public function appendStylesheet( $name = '', $path = '', $dependencies = array(), $params = array() )
    {
        $defaultParams = array(

            'showInFrontEnd' => true,
            'showInBackend' => true,
            'includeInMinify' => true,
            'position' => 'footer',
            'type' => 'text/css',
            'media' => 'screen',
            'rel' => 'stylesheet'

        );

        $params = array_merge( $defaultParams, $params);

        $this->appendFile(

            $name,
            $path,
            $dependencies,
            $params,
            'stylesheet'
        );

        return $this;

    }

    /**
     * @param array $nameArray
     * @param array $dependencies
     * @param array $params
     */
    public function appendScriptGroup( array $nameArray = array(), $dependencies = array(), $params = array() )
    {
        foreach( $nameArray as $name => $path )
        {
            $this->appendScript( $name, $path, $dependencies, $params);
        }

    }

    /**
     * @param string $name
     * @param string $path
     * @param array  $dependencies
     * @param array  $params
     *
     * @return $this
     */
    public function appendScript( $name = '', $path = '', $dependencies = array(), $params = array() )
    {
        $defaultParams = array(

            'showInFrontEnd' => true,
            'showInBackend' => true,
            'includeInMinify' => true,
            'position' => 'footer',
            'type' => 'text/javascript'

        );

        $params = array_merge( $defaultParams, $params);

        $this->appendFile(

            $name,
            $path,
            $dependencies,
            $params,
            'javascript'

        );

        return $this;

    }

    /**
     * @param string $name
     * @param string $path
     * @param array  $dependencies
     * @param array  $params
     * @param        $fileType
     *
     * @return $this
     * @throws \Zend_Exception
     */
    private function appendFile( $name = '', $path = '', $dependencies = array(), $params = array(), $fileType )
    {
        $scriptQueue = $this->scriptQueue;

        $scriptQueue[$params['position']][$name . '-' . $fileType] = array(

            'path'              => $path,
            'dependencies'      => $dependencies,
            'params'            => $params,
            'fileType'          => $fileType

        );

        $this->scriptQueue = $scriptQueue;

        return $this;

    }

    /**
     * @return string
     * @throws \Zend_Exception
     */
    public function getHtmlData()
    {
        if( empty( $this->scriptQueue ) )
        {
            return FALSE;
        }

        $scriptQueue      = $this->scriptQueue;

        if( empty( $scriptQueue ) )
        {
            return FALSE;
        }

        $htmlData = array('header' => '', 'footer' => '' );
        $appendData = array();

        foreach($scriptQueue as $scriptPosition => $scripts)
        {
            if( empty( $scripts ) )
            {
                continue;
            }

            $assetDependencyWatcher = new AssetDependency();

            //for every script name
            foreach($scripts as $scriptName => &$details)
            {
                $p = $details['params'];

                if( ($p['showInFrontEnd'] === FALSE && $this->isFrontEnd ) || ( $p['showInBackend'] === FALSE && $this->isBackEnd )  )
                {
                    continue;
                }

                $deps = array();
                foreach($details['dependencies'] as $depFile)
                {
                    $deps[] = $depFile . '-' . $details['fileType'];
                }

                unset($details['dependencies']);

                $assetDependencyWatcher->add($scriptName, $details, $deps);
            }

            foreach($assetDependencyWatcher->sort() as $dependency) {

                $appendData[$scriptPosition][] = $dependency;

            }

        }

        foreach($appendData as $scriptPosition => $scripts)
        {
            if( !\Pimcore::inDebugMode() && $this->isFrontEnd )
            {
                $htmlData[ $scriptPosition ] = $this->getCompressedHtml( $scripts, $scriptPosition );
            }
            else
            {
                $htmlData[ $scriptPosition ] = $this->getUncompressedHtml( $scripts );
            }
        }

        return $htmlData;

    }

    private function getUncompressedHtml( $scripts )
    {
        $html = '';

        foreach($scripts as $scriptData)
        {
            $p = $scriptData->data['params'];

            if( $scriptData->data['fileType'] == 'javascript')
            {
                $html .= '<script type="' . $p['type'] . '" src="' . $scriptData->data['path'] . '"></script>' . PHP_EOL;
            }
            else if( $scriptData->data['fileType'] == 'stylesheet')
            {
                $html .= '<link href="' .$scriptData->data['path'] . '" media="' . $p['media'] . '" rel="' . $p['rel'] . '" type="' . $p['type'] . '">' . PHP_EOL;
            }
        }

        return $html;

    }

    private function getCompressedHtml( $scripts, $scriptPosition)
    {
        $html = '';

        $jsFiles = array();
        $cssFiles = array();

        $absoluteJs = array();
        $absoluteCss = array();

        $jsFilePaths = array();
        $rawFiles = array();

        $cssFilePaths = array();

        foreach($scripts as $scriptName => $scriptData)
        {
            $p = $scriptData->data['params'];

            if( $scriptData->data['fileType'] == 'javascript')
            {
                if( $p['includeInMinify'])
                {
                    $jsFiles[] = $scriptData->data['path'];
                }
                else
                {
                    $rawFiles[] = $scriptData;
                }

            }
            else if( $scriptData->data['fileType'] == 'stylesheet')
            {

                if( $p['includeInMinify'])
                {
                    $cssFiles[] = $scriptData->data['path'];
                }
                else
                {
                    $rawFiles[] = $scriptData;
                }
            }
        }

        foreach( $jsFiles as $jsFile)
        {
            if( strpos($jsFile, '/plugins') !== FALSE)
            {
                $absoluteJs[] = PIMCORE_PLUGINS_PATH . str_replace('/plugins', '', $jsFile );
            }
            else
            {
                $absoluteJs[] = PIMCORE_WEBSITE_PATH . str_replace('/website', '', $jsFile );
            }

        }

        foreach( $cssFiles as $cssFile)
        {
            if( strpos($cssFile, '/plugins') !== FALSE)
            {
                $absoluteCss[] = PIMCORE_PLUGINS_PATH . str_replace('/plugins', '', $cssFile );
            }
            else
            {
                $absoluteCss[] = PIMCORE_WEBSITE_PATH . str_replace('/website', '', $cssFile );
            }

        }

        $jsFileName = 'data-' . $scriptPosition . '.js';
        $cssFileName = 'style-' . $scriptPosition . '.css';

        $serveController = new \Toolbox\Controller\Minify\Builder();

        //Serve Javascript
        $serveController->setAssets( $absoluteJs, 'js', PIMCORE_TEMPORARY_DIRECTORY, $jsFileName );
        $jsFilePaths[] = $jsFileName;

        //Serve CSS
        $serveController->setAssets( $absoluteCss, 'css', PIMCORE_TEMPORARY_DIRECTORY, $cssFileName );
        $cssFilePaths[] = $cssFileName;

        if( !empty( $cssFilePaths ) )
        {
            $cssFilePaths = array_reverse($cssFilePaths);
            foreach( $cssFilePaths as $cssFilePath)
            {
                $html .= '<link href="' . $this->getFilePath($cssFilePath, 'css'). '" rel="stylesheet" type="text/css">' . PHP_EOL;
            }
        }

        if( !empty( $jsFiles ) )
        {
            $jsFilePaths = array_reverse($jsFilePaths);
            foreach( $jsFilePaths as $jsFilePath)
            {
                $html .= '<script type="text/javascript" src="' . $this->getFilePath($jsFilePath, 'js') . '"></script>' . PHP_EOL;
            }

        }

        if( !empty( $rawFiles ) )
        {
            $html .= $this->getUncompressedHtml( $rawFiles );

        }
        return $html;

    }

    private function getFilePath($file, $fileType)
    {
        if( preg_match("~^(?:f|ht)tps?://~i", $file) )
        {
            return $file;
        }
        if( substr( $file, 0, 8 ) === '/website' )
        {
            return $file;
        }
        return $this->baseUrl . '/website/static/' . $fileType .'/' . $file;

    }
}