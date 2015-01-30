<?php
namespace UnitTest\Lib\Traits;

trait FileAssertions {

    function _emptyFolder( $path,$emptySubFolders=true ){
		foreach (scandir($path) as $file ) {
			if ( !in_array( $file, array( '.','..') ) ) {
				$currentFile = $path.'/'.$file;
				if ( is_dir( $currentFile ) ) {
                                        if ($emptySubFolders) {
                                            $this->_emptyFolder( $currentFile,$emptySubFolders );
                                            rmdir( $currentFile );
                                        }
				} else {
					unlink( $currentFile );
				}
			}
		}
	}

    function _getFolder( $path ){
    	$files = array();
        foreach (scandir($path) as $file ) {
            if ( !in_array( $file, array( '.','..') ) ) {
                $currentFile = $path.'/'.$file;
                if ( is_dir( $currentFile ) ) {
                    $files= array_merge($files, $this->_getFolder( $currentFile ) );
                } else {
                    $files[] = $currentFile;
                }
            }
        }
        return $files;

    }

    protected function assertFolderContents( $expectedContents, $folder, $message = '' )
    {
        $this->assertEquals( $expectedContents, $this->_getFolder( $folder ), $message );
    }


    protected function assertFileContents( $expectedContent, $fileName, $message = '' )
    {
        $this->assertEquals( $expectedContent, file_get_contents( $fileName ) );
    }
}