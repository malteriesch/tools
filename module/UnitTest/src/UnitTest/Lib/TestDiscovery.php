<?php

namespace UnitTest\Lib;

class TestDiscovery {

    protected $extension;
    public function __construct($extension="php") {
        $this->extension = $extension;
    }

    protected $folderCollection = array();

    public function addFolder($folderName) {
        $this->folderCollection[] = $folderName;
    }

    public function getFilesStripped($stripPath){
        $files = $this->getTestFiles();
        array_walk($files,function(&$file)use($stripPath){
            $file = str_replace($stripPath,'',$file);
        });
        return $files;
    }
    
    public function getTestFiles() {
        $tests = array();
        foreach ($this->folderCollection as $folderName) {
            $tests = array_merge($tests, $this->getTestsForFolder($folderName));
        }
        return $tests;
    }

    public function getTestsForFolder($folderName) {
        $fileCollection = array();
        foreach ($this->_getFolder($folderName) as $fileName) {

            if (is_dir($folderName . '/' . $fileName)) {
                if ($this->_isIncludableFolder($folderName . '/' . $fileName)) {
                    $fileCollection = array_merge($fileCollection, $this->GetTestsForFolder($folderName . '/' . $fileName));
                }
            } elseif ($this->_isIncludableFile($folderName, $fileName)) {
                $fileCollection[] = "{$folderName}/{$fileName}";
            }
        }
        return $fileCollection;
    }

    protected function _getFolder($folderName) {
        $folder         = scandir($folderName);
        $fileCollection = array();
        foreach ($folder as $fileName) {
            if (!in_array($fileName, array('.', '..'))) {
                $fileCollection[] = $fileName;
            }
        }
        return $fileCollection;
    }

    protected function _pathContains($path, $needle) {
        return strpos(strtolower($path), strtolower($needle)) !== false;
    }

    protected function _isIncludableFolder($folderName) {
        return !$this->_pathContains($folderName, '.svn') && !$this->_pathContains($folderName, '_files');
    }

    protected function isJavascriptSpecFile($fileName)
    {
        
        return $this->extension=="js" && strpos($fileName,".js")!==false;
    }    
    
    protected function _isIncludableFile($folderName, $fileName) {
        $baseFileName = strtolower(basename($fileName, '.'.$this->extension));
        return ( (strpos($baseFileName, 'test') === 0 || strpos(strrev($baseFileName), strrev('test')) === 0 || $this->isJavascriptSpecFile($fileName)) && $baseFileName != "testhelper" );
    }

    protected function _getContainingFolder($folderName) {
        return basename($folderName);
    }

}

?>