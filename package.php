<?php
/**
 * Script to generate package.xml file
 *
 * Taken from PEAR::Log, thanks Jon ;)
 */
require_once 'PEAR/PackageFileManager.php';
require_once 'Console/Getopt.php';

$version = '0.2';

$notes = <<<EOT
* various CS fixes
EOT;

$description = <<<EOT
Converts a svg  file to a png/jpeg image with the help of apache-batik
(java-program), needs therefore a php with ext/java compiled-in and
the batik files from http://xml.apache.org/batik
EOT;

$package = new PEAR_PackageFileManager();

$result = $package->setOptions(array(
    'package'           => 'XML_svg2image',
    'summary'           => 'Converts a svg  file to a png/jpeg image',
    'description'       => $description,
    'version'           => $version,
    'state'             => 'beta',
    'license'           => 'PHP License',
    'filelistgenerator' => 'cvs',
    'ignore'            => array('package.php', 'package.xml'),
    'notes'             => $notes,
    'changelogoldtonew' => true,
    'simpleoutput'      => true,
    'baseinstalldir'    => '/XML/svg2image/',
    'packagedirectory'  => './',
    'dir_roles'         => array('tests'              => 'test')
));

if (PEAR::isError($result)) {
    echo $result->getMessage();
    die();
}

$package->addMaintainer('chregu',  'lead',        'Christian Stocker',      'chregu@php.net');

$package->addDependency('Java',        false, 'has', 'ext', true);

if (isset($_GET['make']) || $_SERVER['argv'][2] == 'make') {
    $result = $package->writePackageFile();
} else {
    $result = $package->debugPackageFile();
}

if (PEAR::isError($result)) {
    echo $result->getMessage();
    die();
}
