<?php
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997, 1998, 1999, 2000, 2001 The PHP Group             |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Christian Stocker <chregu@phant.ch>                         |
// +----------------------------------------------------------------------+
//
// $Id$


/**
* svg to image converter.
*
* see README.svg2image for details, examples and warnings ;)
*
* @author   Christian Stocker <chregu@nomad.ch>
* @version  $Id$
* @package  XML
*/
require_once( "PEAR.php") ;

class XML_svg2image  {

    /**
    * xsl-file used in this class
    *
    * @var  string
    */
    var $svg = "";

    /**
    * image-file used in this class
    *
    * @var  string
    */
    var $image = "";

    /**
    * Where the temporary xsl and image files should be stored
    *
    * @var  string
    */
    var $tmpdir = "/tmp";

    /**
    * A prefix for the temporary files
    *
    * @var  string
    */
    var $tmpimageprefix = "svg";

    /**
    * the render Type. At the moment (batik 1.0), possible values are
    * - png
    * - jpeg
    *
    * @var string
    */
    var $renderer = "png";
    
    /**
    * the content-type to be sent if printimage is called.
    *
    * @var contenttype
    " @see printimage()
    */
    var $contenttype = "image/png";
    
    /**
    * the width of the output image, if not set, default values are taken
    *
    * @var imageWidth 
    " @see setImageWidth
    */
    var $imageWidth = False;
    
    /**
    * the height of the output image, if not set, default values are taken
    *
    * @var imageHeight 
    " @see setImageHeight
    */
    var $imageHeight = False;

    /**
    * the quality of the output image, makes only sense with jpegs
    *
    * @var imageQuality
    " @see setImageQuality
    */
    var $imageQuality = 0.8;
    
    /**
    * constructor
    *
    * @access public
    */
    function xml_svg2image ()
    {
    }

    /**
    * Calls the Main Batik-Transcoder-Java-Programm
    *
    * One has to pass an input svg-file
    *  and if the image should be stored permanently, a filename/path for
    *  the image.
    *  if the image is not passed or empty/false, a temporary image-file
    *   will be created
    *
    * @param    string  $svg     file input svg-file
    * @param    string  $image    file output image-file
    * @param    boolean $DelSvg if the svg should be deleted after execution
    * @see runFromString()
    */
    function run($svg, $image = "", $DelSvg = False)
    {
        if (!$image)
            $image = tempnam($this->tmpdir, $this->tmpimageprefix);

        $this->image = $image;
        $this->svg = $svg;
        $options = array( $this->svg,"-".$this->renderer,$this->image);
   
        switch ($this->renderer)
            {
                case "jpeg":
                    $t = new Java("org.apache.batik.transcoder.image.JPEGTranscoder");
                    $q = new Java ("java.lang.Float",$this->imageQuality);
                    $t->addTranscodingHint($t->KEY_QUALITY,$q);
                    break;
                default:
                     $t = new Java("org.apache.batik.transcoder.image.PNGTranscoder");
                     $this->contenttype = "image/png";
            }
        // set the transcoding hints            
        $t->addTranscodingHint($t->KEY_XML_PARSER_CLASSNAME,"org.apache.crimson.parser.XMLReaderImpl");        

        if ($this->imageWidth)
        {
                $width = new Java("java.lang.Float",$this->imageWidth);
                $t->addTranscodingHint($t->KEY_WIDTH, $width);
        }
        
        if ($this->imageHeight)
        {
                $height = new Java("java.lang.Float",$this->imageHeight);
                $t->addTranscodingHint($t->KEY_HEIGHT, $height);
        }
        
        if ($exc = java_last_exception_get()) 
        {             
             java_last_exception_clear();
            return new PEAR_Error($exc->getMessage() ." in ". __FILE__ .":". __LINE__, 11, PEAR_ERROR_RETURN, null, null );
        }

// create the transcoder input
        $svgURI = new Java ("java.io.File",$this->svg);
        $svgURI = $svgURI->toURL();
        $svgURI = $svgURI->toString();
         
        $input = new Java ("org.apache.batik.transcoder.TranscoderInput",$svgURI);

        
        // create the transcoder output
        $ostream = @new Java ("java.io.FileOutputStream",$this->image);;

        if ($exc = java_last_exception_get()) 
        {             
             java_last_exception_clear();
            return new PEAR_Error($exc->getMessage() ." in ". __FILE__ .":". __LINE__, 11, PEAR_ERROR_RETURN, null, null );
        }
        
        $output = new Java("org.apache.batik.transcoder.TranscoderOutput",$ostream);
        
// save the image
        @$t->transcode($input, $output);
         if ($exc = java_last_exception_get()) 
        {             
             java_last_exception_clear();
             return new PEAR_Error($exc->getMessage() ." in ". __FILE__ .":". __LINE__, 11, PEAR_ERROR_RETURN, null, null );
        }       
       
// flush and close the stream then exit
        $ostream->flush();
        $ostream->close();        
        if ($exc = java_last_exception_get()) 
        {             
             java_last_exception_clear();
             return new PEAR_Error($exc->getMessage() ." in ". __FILE__ .":". __LINE__, 11, PEAR_ERROR_RETURN, null, null );
        }      
                        
        if ($DelSvg) {
            $this->deleteSvg($svg);
        }
        
        return True;
    }

    /**
    * If the svg is a string, not a file, use this.
    *
    * If you generate the svg dynamically (for example with a
    *  xsl-stylesheet), you can use this method
    *
    * The Batik-Java program needs a file as an input, so a
    *  temporary svg-file is created here (and will be deleted
    *  in the run() function.)
    *
    * @param    string  $svgstring   svg input svg-string
    * @param    string  $image        file output image-file
    * @see run()
    */
    function runFromString($svgstring, $image = "")
    {
        $svg = tempnam($this->tmpdir, $this->tmpimageprefix);
        $fp = fopen($svg, "w+");
        fwrite($fp, $svgstring);
        fclose($fp);
        return $this->run($svg, $image, True);
    }
    /**
    * A wrapper to run for better readabilty
    *
    * This method just calls run....
    *
    * @param    string  $svg     svg input svg-string
    * @param    string  $image    file output image-file
    * @see run()
    */
    function runFromFile($svg, $image = "")
    {
        return $this->run($svg, $image);
    }

    /**
    * Deletes the created image
    *
    * If you dynamically create images and you store them
    *  for example in a Cache, you don't need it afterwards.
    * If no image is given, the one generated in run() is deleted
    *
    * @param    string  $image    file output image-file
    * @access public
    */
    function deleteImage($image = "")
    {
        if (!$image)
            $image = $this->image;
        unlink ($image);
    }

    /**
    * Deletes the created svg
    *
    * If you dynamically create svgs, you don't need it afterwards.
    * If no svg-file is given, the one generated in run() is deleted
    *
    * @param    string  $svg  file input svg-file
    */
    function deleteSvg($svg = "")
    {
        if (!$svg)
            $svg = $this->svg;

        unlink ($svg);
    }

    /**
    * Prints the content header and the generated image to the output
    *
    * If you want to dynamically generate images and return them directly
    *  to the browser, use this.
    * If no image-file is given, the generated from run() is taken.
    *
    * @param    string  $image    file output image-file
    * @see returnimage()
    * @access public    
    */
    function  printImage($image = "")
    {
        $image = $this->returnImage($image);
        Header("Content-type: ".$this->contenttype."\nContent-Length: " . strlen($image));
        print $image;
    }

    /**
    * Returns the image
    *
    * If no image-file is given, the generated from run() is taken.
    *
    * @param    string  $image    file output image-file
    * @return   string image
    * @see run()    
    */
    function returnImage($image = "")
        {
       if (!$image)
           $image = $this->image;

       $fd = fopen($image, "r");
       $content = fread( $fd, filesize($image) );
       fclose($fd);
       return $content;
    }
    
    /**
    * sets the rendertype
    *
    * @param    string  $renderer    the type of renderer which should be used
    * @param    string  $overwriteContentType if the contentType should be set to a approptiate one
    * @see $this-renderer
    * @access public
    */  
    
    function setRenderer($renderer = "png",$overwriteContentType = True)
    {
        $this->renderer = $renderer;
        if ($overwriteContentType)
        {
            switch ($renderer)
            {
                case "jpeg":
                    $this->contenttype = "image/jpeg";
                    break;
                case "png":
                     $this->contenttype = "image/png";
                     break;
            }
        }
                    
    }

    /**
    * sets the content-type
    *
    * @param string $contenttype the content-type for the http-header
    * @see $contenttype
    * @access public
    */  
    function setContentType($contenttype = "image/png")
    {
        $this->contenttype = $contenttype;
    }

    /**
    * sets the width of the output image
    *
    * @param string $width the width of the image in pixel
    * @see $setImageWidth
    * @access public
    */  
    function setImageWidth($width)
    {
        $this->imageWidth = $width;
    }

    /**
    * sets the height of the output image
    *
    * @param string $height the height of the image in pixel
    * @see $setImageHeight
    * @access public
    */  
    function setImageHeight($height)
    {
        $this->imageHeight = $height;
    }

    /**
    * sets the quality of the output image
    *
    * @param string $quality the quality of the image in percent (max = 1 min = 0)
    * @see $setImageQuality
    * @access public
    */  
    function setImageQuality($quality)
    {
        $this->imageQuality = $quality;
    }
        
}
?>
