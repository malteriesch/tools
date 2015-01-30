<?php

namespace UnitTest\Lib\Traits;

trait HtmlAssertions
{

    private function normaliseHtml($html){
        $html = $this->replaceWhitespace($html);
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;   
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        
        libxml_clear_errors();
        
        return $dom->saveHTML();
    }

    protected function replaceWhitespace($html)
    {
        $html = preg_replace('/^\s*/im','',$html);
        $html = preg_replace('/\s{2,}/','',$html);
        return $html;
    }

    public function assertHtml($expected,$actual,$message='')
    {
        $this->assertEquals($this->normaliseHtml($expected), $this->normaliseHtml($actual),$message);
    }

    public function assertHtmlContains($expected, $actual, $message='')
    {
        $this->assertContains($this->replaceWhitespace($expected), $this->normaliseHtml($actual),$message);
    }


    public function removeNewLines($html)
    {
        return str_replace("\n",'',$html);
    }
    
    public function normalise($html)
    {
        return $this->removeNewLines($this->replaceWhitespace($html));
    }
}